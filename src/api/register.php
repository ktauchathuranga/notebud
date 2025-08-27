<?php
// src/api/register.php
require_once __DIR__ . '/db.php';
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
if (strlen($username) > 150 || strlen($password) > 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Input too long']);
    exit;
}

// Check if username exists
$ns = $DB_NAME . '.users';
$existing = mongo_find_one($ns, ['username' => $username]);
if ($existing) {
    http_response_code(409);
    echo json_encode(['error' => 'Username already exists']);
    exit;
}

// Generate refresh token
$refreshToken = bin2hex(random_bytes(32)); // 64 character hex string
$hash = password_hash($password, PASSWORD_DEFAULT);

$doc = [
    'username' => $username,
    'password_hash' => $hash,
    'refresh_token' => $refreshToken,
    'token_used' => false,
    'createdAt' => new MongoDB\BSON\UTCDateTime()
];

$id = mongo_insert_one($ns, $doc);

echo json_encode([
    'success' => true, 
    'refresh_token' => $refreshToken,
    'message' => 'Account created successfully! Save this refresh token - you can use it with your username to reset your password if needed.'
]);
?>
