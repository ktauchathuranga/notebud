<?php
// src/api/get_file_share_requests.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gridfs_env.php';
header('Content-Type: application/json');

$payload = require_auth_api();
$userId = $payload['user_id'];

try {
    $shareRequestsNs = $DB_NAME . '.file_share_requests';

    // Get pending file share requests for current user
    $requests = mongo_find($shareRequestsNs, [
        'to_user_id' => $userId,
        'status' => 'pending'
    ], ['sort' => ['created_at' => -1]]);

    $requestList = [];
    foreach ($requests as $request) {
        $requestList[] = [
            'id' => (string)$request->_id,
            'file_id' => $request->file_id,
            'filename' => $request->filename,
            'file_size' => (int)$request->file_size,
            'file_size_formatted' => format_bytes($request->file_size),
            'mime_type' => $request->mime_type,
            'file_icon' => get_file_icon($request->mime_type),
            'from_user_id' => $request->from_user_id,
            'from_username' => $request->from_username,
            'created_at' => $request->created_at->toDateTime()->format(DATE_ATOM),
            'created_at_formatted' => $request->created_at->toDateTime()->format('M j, Y g:i A')
        ];
    }

    echo json_encode([
        'success' => true,
        'requests' => $requestList
    ]);
} catch (Exception $e) {
    error_log("Get file share requests error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load file share requests']);
}
