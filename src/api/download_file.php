<?php
// src/api/download_file.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gridfs_native.php';

// Check if user is authenticated
try {
    $payload = require_auth_api();
    $user_id = $payload['user_id'];
} catch (Exception $e) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Authentication failed']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Content-Type: application/json');
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$file_id = $_GET['file_id'] ?? '';

if (empty($file_id)) {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['error' => 'File ID required']);
    exit;
}

try {
    // Download file
    $result = download_file($file_id, $user_id);

    if ($result['success']) {
        // Set headers for file download
        header('Content-Type: ' . $result['mime_type']);
        header('Content-Disposition: attachment; filename="' . addslashes($result['filename']) . '"');
        header('Content-Length: ' . $result['size']);
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        echo $result['data'];
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => $result['error']]);
    }
} catch (Exception $e) {
    error_log("Download exception: " . $e->getMessage());
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode(['error' => 'Download failed']);
}
