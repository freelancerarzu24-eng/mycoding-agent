<?php
header('Content-Type: application/json');

// Security and basic setup
$upload_dir = __DIR__ . '/uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Check if file was uploaded
if (!isset($_FILES['projectZip']) || $_FILES['projectZip']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'error' => 'No file uploaded or upload error.']);
    exit;
}

$file_info = pathinfo($_FILES['projectZip']['name']);
if (strtolower($file_info['extension']) !== 'zip') {
    echo json_encode(['success' => false, 'error' => 'Only ZIP files are allowed.']);
    exit;
}

// Generate a safe unique directory for extraction
$safe_dir_name = uniqid('project_', true);
$target_dir = $upload_dir . $safe_dir_name . '/';
mkdir($target_dir, 0755, true);

$zip_path = $upload_dir . $safe_dir_name . '.zip';

if (!move_uploaded_file($_FILES['projectZip']['tmp_name'], $zip_path)) {
    echo json_encode(['success' => false, 'error' => 'Failed to move uploaded file.']);
    exit;
}

// Extract ZIP
$zip = new ZipArchive;
if ($zip->open($zip_path) === TRUE) {
    $zip->extractTo($target_dir);
    $zip->close();

    // Optionally delete the zip file to save space
    unlink($zip_path);

    echo json_encode([
        'success' => true,
        'message' => 'Project extracted successfully.',
        'context_path' => $safe_dir_name
    ]);
} else {
    echo json_encode(['success' => false, 'error' => 'Failed to extract ZIP file.']);
}
