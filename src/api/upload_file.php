<?php
// src/api/upload_file.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gridfs_env.php';

// Suppress warnings for clean JSON output
error_reporting(E_ERROR);

header('Content-Type: application/json');

// Check if user is authenticated
try {
    $payload = require_auth_api();
    $user_id = $payload['user_id'];
} catch (Exception $e) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No file uploaded']);
    exit;
}

$file = $_FILES['file'];

// Check for upload errors
if ($file['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit (' . format_bytes(get_max_file_size()) . ')',
        UPLOAD_ERR_FORM_SIZE => 'File exceeds form upload limit',
        UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Server error: no temporary directory',
        UPLOAD_ERR_CANT_WRITE => 'Server error: cannot write file',
        UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
    ];

    http_response_code(400);
    echo json_encode(['error' => $error_messages[$file['error']] ?? 'Unknown upload error']);
    exit;
}

// Get file data
$file_data = file_get_contents($file['tmp_name']);
if ($file_data === false) {
    http_response_code(400);
    echo json_encode(['error' => 'Failed to read uploaded file']);
    exit;
}

$filename = basename($file['name']);
$mime_type = $file['type'] ?: 'application/octet-stream';

// Validate filename
if (empty($filename) || strlen($filename) > 255) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid filename']);
    exit;
}

// Upload file with environment-controlled limits
try {
    $result = upload_file_env($user_id, $file_data, $filename, $mime_type);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'file' => $result
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => $result['error']]);
    }
} catch (Exception $e) {
    error_log("Upload exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Upload failed: ' . $e->getMessage()]);
}
