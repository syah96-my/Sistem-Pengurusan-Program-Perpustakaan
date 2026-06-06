<?php

require_once __DIR__ . '/../config/bootstrap.php';

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if ($username === '' || $password === '') {
    $_SESSION['error'] = 'Username or password cannot be empty.';
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.password, u.role_id, u.library_id, r.role_name
    FROM gm_users u
    JOIN gm_roles r ON r.id = u.role_id
    WHERE u.username = ?
      AND u.status = 'active'
    LIMIT 1
");
$stmt->execute([$username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

$fakeHash = '$2y$10$ABCDEFGHIJKLMNOPQRSTUV12345678901234567890123456789012';
$hashToCheck = $user ? $user['password'] : $fakeHash;

if (!password_verify($password, $hashToCheck) || !$user) {
    $_SESSION['error'] = 'Invalid username or password.';
    header('Location: login.php');
    exit;
}

session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role_id'];
$_SESSION['library_id'] = $user['library_id'];

require_once __DIR__ . '/../api/controllers/programs_controller.php';
require_once __DIR__ . '/../api/controllers/program_status_controller.php';

try {
    ProgramStatusController::syncByLibrary($pdo, $user['library_id']);
} catch (Throwable $e) {
    error_log('Program status reconcile failed: ' . $e->getMessage());
}

header('Location: ' . app_path('/admin/index.php'));
exit;
?>
