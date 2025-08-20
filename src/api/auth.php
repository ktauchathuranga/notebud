<?php
// src/api/auth.php
// Helpers to authenticate using JWT stored in HttpOnly cookie

require_once __DIR__ . '/jwt.php';

$JWT_SECRET = getenv('JWT_SECRET') ?: 'replace_this_with_a_long_random_secret_here';
$COOKIE_SECURE = (strtolower(getenv('COOKIE_SECURE') ?: 'false') === 'true');

function require_auth_or_redirect() {
    global $JWT_SECRET, $COOKIE_SECURE;
    $token = $_COOKIE['token'] ?? null;
    if (!$token) {
        header('Location: /login.html');
        exit;
    }
    $payload = jwt_decode($token, $JWT_SECRET);
    if (!$payload || !isset($payload['user_id'])) {
        // invalid token
        // remove cookie
        setcookie('token', '', [
            'expires' => time() - 3600,
            'httponly' => true,
            'secure' => $COOKIE_SECURE,
            'path' => '/'
        ]);
        header('Location: /login.html');
        exit;
    }
    return $payload;
}

function require_auth_api() {
    global $JWT_SECRET, $COOKIE_SECURE;
    $token = $_COOKIE['token'] ?? null;
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Not authenticated']);
        exit;
    }
    $payload = jwt_decode($token, $JWT_SECRET);
    if (!$payload || !isset($payload['user_id'])) {
        // remove cookie
        setcookie('token', '', [
            'expires' => time() - 3600,
            'httponly' => true,
            'secure' => $COOKIE_SECURE,
            'path' => '/'
        ]);
        http_response_code(401);
        echo json_encode(['error' => 'Invalid token']);
        exit;
    }
    return $payload;
}

// Helper to set JWT cookie after successful login
function set_auth_cookie($payload) {
    global $JWT_SECRET, $COOKIE_SECURE;
    $token = jwt_encode($payload, $JWT_SECRET, 4 * 60 * 60); // 4 hours
    // set HttpOnly cookie
    setcookie('token', $token, [
        'expires' => time() + 4 * 60 * 60,
        'httponly' => true,
        'secure' => $COOKIE_SECURE,
        'samesite' => 'Lax',
        'path' => '/'
    ]);
}
?>