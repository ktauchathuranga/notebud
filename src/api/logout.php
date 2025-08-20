<?php
// src/api/logout.php
require_once __DIR__ . '/auth.php';
header('Content-Type: application/json');

// Remove cookie
setcookie('token', '', [
    'expires' => time() - 3600,
    'httponly' => true,
    'secure' => (strtolower(getenv('COOKIE_SECURE') ?: 'false') === 'true'),
    'samesite' => 'Lax',
    'path' => '/'
]);

echo json_encode(['success' => true]);