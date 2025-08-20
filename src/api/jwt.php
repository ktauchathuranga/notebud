<?php
// src/api/jwt.php
// Minimal JWT implementation (HS256)

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    $remainder = strlen($data) % 4;
    if ($remainder) $data .= str_repeat('=', 4 - $remainder);
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_sign($headerb64, $payloadb64, $secret) {
    $signature = hash_hmac('sha256', "$headerb64.$payloadb64", $secret, true);
    return base64url_encode($signature);
}

function jwt_encode($payload, $secret, $expSeconds = 14400) {
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $now = time();
    $payload['iat'] = $now;
    $payload['exp'] = $now + $expSeconds;

    $headerb64 = base64url_encode(json_encode($header));
    $payloadb64 = base64url_encode(json_encode($payload));
    $sig = jwt_sign($headerb64, $payloadb64, $secret);
    return "$headerb64.$payloadb64.$sig";
}

function jwt_decode($token, $secret) {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    [$headerb64, $payloadb64, $sigb64] = $parts;
    $expected_sig = jwt_sign($headerb64, $payloadb64, $secret);
    if (!hash_equals($expected_sig, $sigb64)) return null;
    $payloadJson = base64url_decode($payloadb64);
    $payload = json_decode($payloadJson, true);
    if (!$payload) return null;
    if (isset($payload['exp']) && time() > (int)$payload['exp']) return null;
    return $payload;
}
?>