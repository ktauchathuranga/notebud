<?php
// src/api/register.php

// Load .env file (same logic as db.php)
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k);
        $v = trim($v);
        if ($k !== '' && getenv($k) === false) putenv("$k=$v");
    }
}

require_once __DIR__ . '/db.php';
header('Content-Type: application/json');

// reCAPTCHA verification
$recaptchaSecret = getenv('RECAPTCHA_SECRET_KEY') ?: '';
$recaptchaResponse = $_POST['g-recaptcha-response'] ?? '';
if (!$recaptchaSecret) {
    http_response_code(500);
    echo json_encode(['error' => 'Server CAPTCHA configuration error']);
    exit;
}
if (!$recaptchaResponse) {
    http_response_code(400);
    echo json_encode(['error' => 'Captcha is required']);
    exit;
}
$verify = file_get_contents(
    "https://www.google.com/recaptcha/api/siteverify?secret={$recaptchaSecret}&response={$recaptchaResponse}"
);
$captchaSuccess = json_decode($verify);
if (!$captchaSuccess->success) {
    http_response_code(400);
    echo json_encode(['error' => 'Captcha verification failed']);
    exit;
}

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
