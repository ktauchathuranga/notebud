<?php
// src/api/get_files.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gridfs_env.php';

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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

try {
    // Get user files and storage info
    $files = get_user_files($user_id);
    $storage_info = get_user_storage_info_env($user_id);

    echo json_encode([
        'success' => true,
        'files' => $files,
        'storage_usage' => $storage_info['storage_used'],
        'storage_limit' => $storage_info['storage_limit'],
        'storage_usage_formatted' => $storage_info['storage_used_formatted'],
        'storage_limit_formatted' => $storage_info['storage_limit_formatted'],
        'storage_percentage' => $storage_info['storage_percentage'],
        'file_count' => $storage_info['file_count'],
        'file_limit' => $storage_info['file_limit'],
        'max_file_size' => $storage_info['max_file_size'],
        'max_file_size_formatted' => $storage_info['max_file_size_formatted']
    ]);
} catch (Exception $e) {
    error_log("Get files error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load files']);
}
