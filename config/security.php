<?php

$gmSecretKey = getenv('GM_SECRET_KEY');
if ($gmSecretKey === false || $gmSecretKey === '') {
    $gmSecretKey = 'change-this-local-dev-secret-key';
}

define("GM_SECRET_KEY", $gmSecretKey);

function gm_base64url_encode($value) {
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function gm_base64url_decode($value) {
    $value = (string)$value;
    $padding = strlen($value) % 4;
    if ($padding > 0) {
        $value .= str_repeat('=', 4 - $padding);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

function gm_encryption_key() {
    return hash('sha256', GM_SECRET_KEY, true);
}

function gm_encrypt($value) {
    $cipher = 'AES-256-CBC';
    $key = gm_encryption_key();
    $ivLength = openssl_cipher_iv_length($cipher);
    $iv = random_bytes($ivLength);
    $encrypted = openssl_encrypt((string)$value, $cipher, $key, OPENSSL_RAW_DATA, $iv);

    if ($encrypted === false) {
        return false;
    }

    $mac = hash_hmac('sha256', $iv . $encrypted, $key, true);

    return gm_base64url_encode($iv . $mac . $encrypted);
}

function gm_decrypt($token) {
    $payload = gm_base64url_decode($token);
    if ($payload === false) {
        return false;
    }

    $cipher = 'AES-256-CBC';
    $key = gm_encryption_key();
    $ivLength = openssl_cipher_iv_length($cipher);

    if (strlen($payload) > $ivLength + 32) {
        $iv = substr($payload, 0, $ivLength);
        $mac = substr($payload, $ivLength, 32);
        $encrypted = substr($payload, $ivLength + 32);
        $expectedMac = hash_hmac('sha256', $iv . $encrypted, $key, true);

        if (hash_equals($expectedMac, $mac)) {
            return openssl_decrypt($encrypted, $cipher, $key, OPENSSL_RAW_DATA, $iv);
        }
    }

    return openssl_decrypt($payload, "AES-256-ECB", GM_SECRET_KEY, OPENSSL_RAW_DATA);
}

?>
