<?php
// src/api/login.php

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
require_once __DIR__ . '/auth.php';
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
$isPermanent = isset($_POST['permanent']) && $_POST['permanent'] === 'on';

if (empty($username) || empty($password)) {
    http_response_code(400);
    echo json_encode(['error' => 'Username and password are required']);
    exit;
}

try {
    $usersNs = $DB_NAME . '.users';

    $user = mongo_find_one($usersNs, ['username' => $username]);

    if (!$user || !password_verify($password, $user->password_hash)) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid username or password']);
        exit;
    }

    // Clean up expired sessions
    cleanup_expired_sessions();

    // Generate session ID
    $sessionId = bin2hex(random_bytes(32));
    $userId = (string)$user->_id; // Ensure it's a string

    // Create session in database
    $sessionsNs = $DB_NAME . '.sessions';
    $session = [
        'user_id' => $userId,
        'session_id' => $sessionId,
        'permanent' => $isPermanent,
        'created_at' => new MongoDB\BSON\UTCDateTime(),
        'last_activity' => new MongoDB\BSON\UTCDateTime()
    ];

    if (!$isPermanent) {
        // Temporary session expires in 4 hours
        $expiresAt = time() + (4 * 60 * 60);
        $session['expires_at'] = new MongoDB\BSON\UTCDateTime($expiresAt * 1000);
    }

    mongo_insert_one($sessionsNs, $session);

    // Create JWT payload - IMPORTANT: Make sure this matches what Rust expects
    $jwtPayload = [
        'user_id' => $userId,     // String, not ObjectId
        'username' => $user->username,  // Add username field
        'session_id' => $sessionId,
        'permanent' => $isPermanent
        // iat and exp will be added by jwt_encode
    ];

    // Set cookie
    set_auth_cookie($jwtPayload, $isPermanent);

    // DEBUG: Let's see what we're creating
    error_log("Login: Created JWT for user_id=" . $userId . ", username=" . $user->username);

    echo json_encode([
        'success' => true,
        'user_id' => $userId,
        'username' => $user->username,
        'permanent' => $isPermanent
    ]);
} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Login failed. Please try again.']);
}
