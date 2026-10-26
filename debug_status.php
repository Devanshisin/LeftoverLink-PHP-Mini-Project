<?php
require __DIR__ . '/config.php';
$stmt = $pdo->query("SHOW COLUMNS FROM food_listings LIKE 'status'");
$row = $stmt->fetch();
var_export($row);
?>
