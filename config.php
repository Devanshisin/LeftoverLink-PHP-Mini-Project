<?php
// config.php – Database connection using PDO

// Update these constants to match your local XAMPP MySQL credentials
define('DB_HOST', 'localhost');
define('DB_NAME', 'leftoverlink');
define('DB_USER', 'root');
define('DB_PASS', ''); // XAMPP default password is empty

define('DSN', 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4');

try {
    $pdo = new PDO(DSN, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // In a real app you might log this error instead of displaying it.
    die('Database connection failed: ' . $e->getMessage());
}
?>
