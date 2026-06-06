<?php
/**
 * One-time admin upsert script
 * - Insert if not exists
 * - Update password if exists
 * PHP 7.2 compatible
 */

$db = require __DIR__ . '/database.php';

/* ===============================
   PDO CONNECTION
================================ */

$dsn = "mysql:host={$db['host']};dbname={$db['db']}";

try {
    $pdo = new PDO($dsn, $db['user'], $db['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    // Fix MariaDB charset issue
    $pdo->exec("SET NAMES utf8mb4");

} catch (PDOException $e) {
    die("DB connection failed: " . $e->getMessage());
}

/* ===============================
   USER DATA
================================ */

$username   = getenv('GM_ADMIN_USERNAME') ?: "admin";
$password   = getenv('GM_ADMIN_PASSWORD') ?: "password";
$library_id = (int)(getenv('GM_ADMIN_LIBRARY_ID') ?: 1);
$role_id    = (int)(getenv('GM_ADMIN_ROLE_ID') ?: 1); // super_admin
$status     = "active";

/* ===============================
   HASH PASSWORD
================================ */

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

/* ===============================
   UPSERT USER
================================ */

$sql = "
INSERT INTO gm_users
    (username, password, library_id, role_id, status)
VALUES
    (:username, :password, :library_id, :role_id, :status)
ON DUPLICATE KEY UPDATE
    password   = VALUES(password),
    library_id = VALUES(library_id),
    role_id    = VALUES(role_id),
    status     = VALUES(status)
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':username'   => $username,
        ':password'   => $hashedPassword,
        ':library_id' => $library_id,
        ':role_id'    => $role_id,
        ':status'     => $status
    ]);

    if ($stmt->rowCount() === 1) {
        echo "Admin user inserted\n";
    } else {
        echo "Admin user updated (password refreshed)\n";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
