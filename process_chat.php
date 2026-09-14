<?php
header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // For Nginx streaming

$ollama_url = 'http://localhost:11434/api/generate';
$model_name = 'qwen2.5-coder:7b'; // Change if needed

$user_instruction = $_POST['user_instruction'] ?? '';
$context_path = $_POST['context_path'] ?? '';

if (empty($user_instruction)) {
    echo "Error: No instruction provided.";
    exit;
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

$ch = curl_init($ollama_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, false); // Important for streaming
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

// Handle streaming chunk callback
$buffer = '';
curl_setopt($ch, CURLOPT_WRITEFUNCTION, function($ch, $chunk) use (&$buffer) {
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
}

curl_close($ch);
