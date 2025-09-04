<?php
// src/api/get_my_file_shares.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/gridfs_env.php';
header('Content-Type: application/json');

$payload = require_auth_api();
$userId = $payload['user_id'];

try {
    $shareRequestsNs = $DB_NAME . '.file_share_requests';

    // Get file share requests I've sent
    $requests = mongo_find($shareRequestsNs, [
        'from_user_id' => $userId
    ], ['sort' => ['created_at' => -1]]);

    $requestList = [];
    foreach ($requests as $request) {
        $statusColor = [
            'pending' => '#fbbf24',  // yellow
            'accepted' => '#10b981', // green  
            'rejected' => '#ef4444'  // red
        ];

        $requestList[] = [
            'id' => (string)$request->_id,
            'file_id' => $request->file_id,
            'filename' => $request->filename,
            'file_size' => (int)$request->file_size,
            'file_size_formatted' => format_bytes($request->file_size),
            'mime_type' => $request->mime_type,
            'file_icon' => get_file_icon($request->mime_type),
            'to_user_id' => $request->to_user_id,
            'to_username' => $request->to_username,
            'status' => $request->status,
            'status_color' => $statusColor[$request->status] ?? '#6b7280',
            'created_at' => $request->created_at->toDateTime()->format(DATE_ATOM),
            'created_at_formatted' => $request->created_at->toDateTime()->format('M j, Y g:i A'),
            'accepted_at' => isset($request->accepted_at) ?
                $request->accepted_at->toDateTime()->format('M j, Y g:i A') : null,
            'rejected_at' => isset($request->rejected_at) ?
                $request->rejected_at->toDateTime()->format('M j, Y g:i A') : null
        ];
    }

    echo json_encode([
        'success' => true,
        'shares' => $requestList
    ]);
} catch (Exception $e) {
    error_log("Get my file shares error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to load file shares']);
}
