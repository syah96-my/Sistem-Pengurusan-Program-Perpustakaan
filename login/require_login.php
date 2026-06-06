<?php

require_once __DIR__ . '/../config/bootstrap.php';

if (empty($_SESSION['user_id'])) {
    header('Location: ' . app_path('/login/login.php'));
    exit;
}

$stmt = $pdo->prepare("
    SELECT id, username, role_id, library_id, password
    FROM gm_users
    WHERE id = ?
      AND status = 'active'
    LIMIT 1
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    session_destroy();
    header('Location: ' . app_path('/login/login.php'));
    exit;
}

$forcePasswordChange = strtolower((string)(getenv('GM_FORCE_PASSWORD_CHANGE') ?: 'false')) === 'true';
$defaultPassword = getenv('GM_DEFAULT_USER_PASSWORD') ?: 'password';
if ($forcePasswordChange && !empty($user['password']) && password_verify($defaultPassword, $user['password'])) {
    $_SESSION['force_change_password'] = true;
} else {
    unset($_SESSION['force_change_password']);
}

$libType = null;
$libParent = null;

if (!empty($user['library_id'])) {
    $stmt2 = $pdo->prepare("
        SELECT type_id, parent_id
        FROM gm_libraries
        WHERE id = ?
        LIMIT 1
    ");
    $stmt2->execute([$user['library_id']]);
    $lib = $stmt2->fetch(PDO::FETCH_ASSOC);

    if ($lib) {
        $libType = $lib['type_id'];
        $libParent = $lib['parent_id'];
    }
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role_id'];
$_SESSION['library_id'] = $user['library_id'];
$_SESSION['library_type_id'] = $libType;
$_SESSION['library_parent_id'] = $libParent;
?>
