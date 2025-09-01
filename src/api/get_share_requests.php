<?php
// src/api/get_share_requests.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

$payload = require_auth_api();
$userId = $payload['user_id'];

$ns = $DB_NAME . '.note_share_requests';

// Get pending share requests for current user
$requests = mongo_find($ns, [
    'to_user_id' => $userId,
    'status' => 'pending'
], ['sort' => ['created_at' => -1]]);

$requestList = [];
foreach ($requests as $request) {
    $requestList[] = [
        'id' => (string)$request->_id,
        'note_id' => $request->note_id,
        'from_user_id' => $request->from_user_id,
        'from_username' => $request->from_username,
        'title' => $request->title ?? 'Untitled Note',
        'created_at' => $request->created_at->toDateTime()->format(DATE_ATOM)
    ];
}

echo json_encode(['requests' => $requestList]);
