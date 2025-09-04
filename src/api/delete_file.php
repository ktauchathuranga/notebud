<?php
// src/api/delete_file.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gridfs_native.php';

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

$input = json_decode(file_get_contents('php://input'), true);
$file_id = $input['file_id'] ?? '';

if (empty($file_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'File ID required']);
    exit;
}

try {
    // Delete file
    $result = delete_file($file_id, $user_id);

    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => $result['message']
        ]);
    } else {
        http_response_code(400);
        echo json_encode(['error' => $result['error']]);
    }
} catch (Exception $e) {
    error_log("Delete exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Delete failed']);
}
