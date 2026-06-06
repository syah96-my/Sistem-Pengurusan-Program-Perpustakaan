<?php
$config = require __DIR__ . '/database.php';

$dsn = "mysql:host={$config['host']};dbname={$config['db']};charset={$config['charset']}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

try {
    $pdo = new PDO($dsn, $config['user'], $config['pass'], $options);
} catch (PDOException $e) {
    die('DB connection failed');
}
?>