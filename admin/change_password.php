<?php
error_reporting(E_ALL);
require __DIR__ . '/../login/require_login.php';

/* ============================================================
   Reject direct page access (GET requests)
============================================================ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ./index.php");
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

if (!gm_verify_csrf_header()) {
    http_response_code(403);
    echo json_encode(["error" => "Invalid security token"]);
    exit;
}

/* ============================================================
   Validate Input
============================================================ */
if (!isset($_POST['new_password'])) {
    http_response_code(400);
    echo json_encode(["error" => "Missing password"]);
    exit;
}

$newPassword = trim($_POST['new_password']);

if (strlen($newPassword) < 6) {
    http_response_code(400);
    echo json_encode(["error" => "Password must be at least 6 characters"]);
    exit;
}

/* ============================================================
   Update User Password
============================================================ */
$hash = password_hash($newPassword, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE gm_users SET password = ? WHERE id = ?");
$stmt->execute([$hash, $_SESSION['user_id']]);

// Remove force flag
unset($_SESSION['force_change_password']);

/* ============================================================
   Return JSON Response
============================================================ */
echo json_encode(["success" => true]);
exit;
?>


