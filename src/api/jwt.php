<?php
// src/api/jwt.php
// Enhanced JWT implementation with better compatibility

function base64url_encode($data)
{
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data)
{
    $remainder = strlen($data) % 4;
    if ($remainder) $data .= str_repeat('=', 4 - $remainder);
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_sign($headerb64, $payloadb64, $secret)
{
    $signature = hash_hmac('sha256', "$headerb64.$payloadb64", $secret, true);
    return base64url_encode($signature);
}

function jwt_encode($payload, $secret, $expSeconds = 14400)
{
    $header = ['alg' => 'HS256', 'typ' => 'JWT'];
    $now = time();

    // Ensure payload has required fields for Rust server
    $payload['iat'] = $now;
    $payload['exp'] = $now + $expSeconds;

    // Make sure user_id is a string (not ObjectId)
    if (isset($payload['user_id']) && is_object($payload['user_id'])) {
        $payload['user_id'] = (string)$payload['user_id'];
    }

    $headerb64 = base64url_encode(json_encode($header, JSON_UNESCAPED_SLASHES));
    $payloadb64 = base64url_encode(json_encode($payload, JSON_UNESCAPED_SLASHES));
    $sig = jwt_sign($headerb64, $payloadb64, $secret);
    return "$headerb64.$payloadb64.$sig";
}

function jwt_decode($token, $secret)
{
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

// Debug function to inspect JWT tokens
function jwt_debug($token, $secret)
{
    $parts = explode('.', $token);
    if (count($parts) !== 3) {
        return ['error' => 'Invalid JWT format'];
    }

    [$headerb64, $payloadb64, $sigb64] = $parts;

    $header = json_decode(base64url_decode($headerb64), true);
    $payload = json_decode(base64url_decode($payloadb64), true);

    $expected_sig = jwt_sign($headerb64, $payloadb64, $secret);
    $signature_valid = hash_equals($expected_sig, $sigb64);

    return [
        'header' => $header,
        'payload' => $payload,
        'signature_valid' => $signature_valid,
        'expected_signature' => $expected_sig,
        'received_signature' => $sigb64
    ];
}
