<?php

// ?? Use universal bootstrap
require_once __DIR__ . '/../config/bootstrap.php';

// API helpers
require_once __DIR__ . '/helpers/response.php';

if (empty($_SESSION['user_id'])) {
    Response::json(['error' => 'Login required'], 401);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$safeMethods = ['GET', 'HEAD', 'OPTIONS'];
if (!in_array($method, $safeMethods, true) && !gm_verify_csrf_header()) {
    Response::json(['error' => 'Invalid security token'], 403);
}

// Load route handler
require_once __DIR__ . '/routes.php';
?>
