<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // For Nginx streaming

$ollama_url = 'http://localhost:11434/api/generate';
$model_name = 'qwen2.5-coder:7b'; // Change if needed

$user_instruction = $_POST['user_instruction'] ?? '';
$context_path = $_POST['context_path'] ?? '';
$reference_url = $_POST['reference_url'] ?? '';

if (empty($user_instruction)) {
    echo "Error: No instruction provided.";
    exit;
}

$images = [];
if (isset($_FILES['ui_image']) && $_FILES['ui_image']['error'] === UPLOAD_ERR_OK) {
    // Read the image file and convert to base64
    $image_data = file_get_contents($_FILES['ui_image']['tmp_name']);
    $base64_image = base64_encode($image_data);
    $images[] = $base64_image;

    // Switch to a vision model if an image is provided
    $model_name = 'llava';
}

if (!empty($reference_url)) {
    if (filter_var($reference_url, FILTER_VALIDATE_URL)) {
        $parsed_url = parse_url($reference_url);
        $host = $parsed_url['host'] ?? '';
        $scheme = $parsed_url['scheme'] ?? '';

        // Basic SSRF mitigation: only allow http/https and reject localhost/private IPs
        if (in_array($scheme, ['http', 'https']) && !preg_match('/^(localhost|127\.0\.0\.1|192\.168\.|10\.|172\.(1[6-9]|2[0-9]|3[0-1])\.|169\.254\.)/', $host)) {
            $user_instruction .= "\n\nReference URL provided: " . $reference_url . "\n";
            $url_ch = curl_init($reference_url);
            curl_setopt($url_ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($url_ch, CURLOPT_TIMEOUT, 5);
            curl_setopt($url_ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($url_ch, CURLOPT_MAXREDIRS, 3);
            $url_content = curl_exec($url_ch);
            curl_close($url_ch);

            if ($url_content) {
                $text_content = strip_tags($url_content);
                $text_content = substr($text_content, 0, 5000); // Limit size
                $user_instruction .= "Here is some extracted text from the URL:\n" . $text_content . "\n";
            }
        }
    }
}

// Load System Prompt
$system_prompt = file_get_contents(__DIR__ . '/config/system_prompt.txt');
if ($system_prompt === false) {
    $system_prompt = "You are a helpful AI coding assistant.";
}

// Build Project Context
$project_context = "";
if (!empty($context_path)) {
    $dir_to_scan = realpath(__DIR__ . '/uploads/' . basename($context_path));
    if ($dir_to_scan && strpos($dir_to_scan, realpath(__DIR__ . '/uploads/')) === 0) {
        $project_context = "Here is the existing project codebase:\n\n";

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir_to_scan));
        $valid_extensions = ['php', 'html', 'css', 'js', 'sql', 'json', 'txt', 'md', 'env.example'];

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $ext = strtolower(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                if (in_array($ext, $valid_extensions)) {
                    // Limit file size to prevent memory exhaustion (e.g. 100KB per file max)
                    if ($file->getSize() < 100000) {
                        $content = file_get_contents($file->getPathname());
                        // Make path relative to extraction dir
                        $relative_path = str_replace($dir_to_scan . '/', '', $file->getPathname());
                        $project_context .= "==================== FILE: {$relative_path} ====================\n";
                        $project_context .= $content . "\n\n";
                    }
                }
            }
        }
    }
}

$full_prompt = $project_context . "\n\nUSER INSTRUCTION:\n" . $user_instruction;

$data = [
    'model' => $model_name,
    'system' => $system_prompt,
    'prompt' => $full_prompt,
    'stream' => true,
    'options' => [
        'temperature' => 0.1,
        'num_ctx' => 16384
    ]
];
if (!empty($images)) {
    $data['images'] = $images;
}

$ch = curl_init($ollama_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Important for streaming
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Handle streaming chunk callback
$buffer = '';
$full_response = '';
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use (&$buffer, &$full_response) {
    $buffer .= $chunk;
    // Ollama streams JSON objects separated by newlines
    while (($pos = strpos($buffer, "\n")) !== false) {
        $line = substr($buffer, 0, $pos);
        $buffer = substr($buffer, $pos + 1);
        $line = trim($line);
        if (!empty($line)) {
            $json = json_decode($line, true);
            if ($json !== null && isset($json['response'])) {
                echo $json['response'];
                $full_response .= $json['response'];
                flush(); // Force output buffer to send
            }
        }
    }
    return strlen($chunk);
});

curl_exec($ch);

if (curl_errno($ch)) {
    echo "\n\n[Error connecting to local AI: " . curl_error($ch) . "]";
    echo "\nPlease make sure Ollama is installed and running in the background!";
} else {
    // Process auto-file creation after streaming is complete
    if (strpos($full_response, '<file path="') !== false) {
        // Use a more secure random ID to prevent directory guessing
        $project_id = uniqid(bin2hex(random_bytes(4)) . '_', true);
        $project_dir = __DIR__ . '/generated_projects/project_' . $project_id . '/';
        mkdir($project_dir, 0755, true);

        // Extract files using regex
        preg_match_all('/<file path="([^"]+)">(.*?)<\/file>/s', $full_response, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $file_path = trim($match[1]);
            $file_content = trim($match[2]);

            // Strictly prevent directory traversal by normalizing path and checking bounds
            $file_path = str_replace('\\', '/', $file_path); // normalize slashes
            $path_parts = explode('/', $file_path);
            $safe_parts = [];
            foreach ($path_parts as $part) {
                if ($part === '' || $part === '.' || $part === '..') {
                    continue;
                }
                // allow only safe characters in filenames
                if (preg_match('/^[a-zA-Z0-9_\-\.]+$/', $part)) {
                    $safe_parts[] = $part;
                }
            }

            if (empty($safe_parts)) {
                continue; // skip invalid paths
            }

            $safe_file_path = implode('/', $safe_parts);
            $full_path = $project_dir . $safe_file_path;

            $dir = dirname($full_path);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }

            file_put_contents($full_path, $file_content);
        }
    }
}

curl_close($ch);
