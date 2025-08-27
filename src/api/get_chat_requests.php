<?php
// src/api/get_chat_requests.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

$payload = require_auth_api();
$userId = $payload['user_id'];

$ns = $DB_NAME . '.chat_requests';

// Get pending requests
$requests = mongo_find($ns, [
    'to_user_id' => $userId,
    'status' => 'pending'
], ['sort' => ['created_at' => -1]]);

$requestList = [];
foreach ($requests as $request) {
    $requestList[] = [
        'from_user_id' => $request->from_user_id,
        'from_username' => $request->from_username,
        'created_at' => $request->created_at->toDateTime()->format(DATE_ATOM)
    ];
}

echo json_encode(['requests' => $requestList]);
