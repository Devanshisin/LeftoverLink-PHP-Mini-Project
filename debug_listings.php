<?php
require __DIR__ . '/config.php';
$stmt = $pdo->query('SELECT id, status, available_quantity, pickup_deadline FROM food_listings');
foreach ($stmt as $row) {
    echo $row['id'] . " | " . $row['status'] . " | " . $row['available_quantity'] . " | " . $row['pickup_deadline'] . "\n";
}
?>
