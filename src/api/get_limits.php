<?php
// src/api/get_limits.php
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
    $limits = get_current_limits();
    echo json_encode([
        'success' => true,
        'limits' => $limits
    ]);
} catch (Exception $e) {
    error_log("Get limits error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to get limits']);
}
