<?php
// src/api/login.php
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password required']);
    exit;
}

$ns = $DB_NAME . '.users';
$user = mongo_find_one($ns, ['username' => $username]);
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// $user is BSON document (stdClass)
$hash = $user->password_hash ?? null;
if (!$hash || !password_verify($password, $hash)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
}

// Create token with user_id (string of ObjectId)
$userId = (string)$user->_id;
$payload = ['user_id' => $userId];

set_auth_cookie($payload);

echo json_encode(['success' => true]);