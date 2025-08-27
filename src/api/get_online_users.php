<?php
// src/api/get_online_users.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

$payload = require_auth_api();
$userId = $payload['user_id'];

$ns = $DB_NAME . '.users';

// Get online users (excluding current user)
$users = mongo_find($ns, [
    'online' => true,
    '_id' => ['$ne' => new MongoDB\BSON\ObjectId($userId)]
], ['sort' => ['username' => 1]]);

$userList = [];
foreach ($users as $user) {
    $userList[] = [
        'user_id' => (string)$user->_id,
        'username' => $user->username,
        'last_seen' => isset($user->last_seen) ? $user->last_seen->toDateTime()->format(DATE_ATOM) : null
    ];
}

echo json_encode(['users' => $userList]);
