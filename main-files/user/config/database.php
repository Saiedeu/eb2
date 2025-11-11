<?php
if (!defined('DB_HOST')) define('DB_HOST', 'sql203.infinityfree.com'); // Replace with your actual host
if (!defined('DB_USER')) define('DB_USER', 'if0_38847374'); // Replace with your actual user
if (!defined('DB_PASS')) define('DB_PASS', 'EBAdmin123'); // Replace with your actual password
if (!defined('DB_NAME')) define('DB_NAME', 'if0_38847374_User'); // Replace with your actual database name
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>