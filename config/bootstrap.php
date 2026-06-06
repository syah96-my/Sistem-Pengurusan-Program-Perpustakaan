<?php

if (session_status() === PHP_SESSION_NONE) {
    $secureCookie = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    if (PHP_VERSION_ID >= 70300) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secureCookie,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    } else {
        session_set_cookie_params(0, '/; samesite=Lax', '', $secureCookie, true);
    }
    session_start();
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!function_exists('gm_csrf_token')) {
    function gm_csrf_token()
    {
        return $_SESSION['csrf_token'] ?? '';
    }
}

if (!function_exists('gm_verify_csrf_header')) {
    function gm_verify_csrf_header()
    {
        $token = $_SERVER['HTTP_X_API_KEY'] ?? '';
        $expected = gm_csrf_token();

        return $expected !== '' && is_string($token) && hash_equals($expected, $token);
    }
}

date_default_timezone_set('Asia/Kuala_Lumpur');

if (!defined('GM_BASE_PATH')) {
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $projectDir = basename(dirname(__DIR__));
    $prefix = '/' . $projectDir;
    $basePath = '';

    if ($scriptName === $prefix || strpos($scriptName, $prefix . '/') === 0) {
        $basePath = $prefix;
    }

    define('GM_BASE_PATH', $basePath);
}

if (!function_exists('app_path')) {
    function app_path($path = '')
    {
        $path = '/' . ltrim((string)$path, '/');
        return rtrim(GM_BASE_PATH, '/') . $path;
    }
}

// Database config (array)
require_once __DIR__ . '/database.php';

// Security helpers (optional)
$security = __DIR__ . '/security.php';
if (file_exists($security)) {
    require_once $security;
}

// Shared hierarchy helpers
require_once __DIR__ . '/../helpers/LibraryHierarchy.php';

// PDO connection ($pdo)
require_once __DIR__ . '/db.php';
?>
