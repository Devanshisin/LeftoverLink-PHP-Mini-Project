<?php
// my-listings.php – Donor view of their own food listings and status
require_once __DIR__ . '/auth.php';
requireLogin();
requireRole(['donor']);
$user = currentUser();

// Update expired listings status on load
$pdo->exec("UPDATE food_listings SET status = 'expired' WHERE donor_id = {$user['id']} AND status IN ('available','partially_reserved','fully_reserved') AND pickup_deadline <= NOW() AND status <> 'collected'");

$stmt = $pdo->prepare('SELECT id, food_name, description, category, quantity, unit, location, preparation_details, pickup_deadline, status, available_quantity, created_at FROM food_listings WHERE donor_id = ? ORDER BY created_at DESC');
$stmt->execute([$user['id']]);
$listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Listings – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <h2>My Food Listings</h2>
    <?php if (empty($listings)): ?>
        <p>You have not added any listings yet.</p>
    <?php else: ?>
        <table class="listings-table">
            <thead>
                <tr>
                    <th>Food</th>
                    <th>Category</th>
                    <th>Total Qty</th>
                    <th>Available Qty</th>
                    <th>Unit</th>
                    <th>Location</th>
                    <th>Pickup By</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $l): ?>
                    <tr>
                        <td><?= e($l['food_name']) ?></td>
                        <td><?= e($l['category']) ?></td>
                        <td><?= e($l['quantity']) ?></td>
                        <td><?= e($l['available_quantity']) ?></td>
                        <td><?= e($l['unit']) ?></td>
                        <td><?= e($l['location']) ?></td>
                        <td><?= e((new DateTime($l['pickup_deadline']))->format('Y-m-d H:i')) ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', $l['status']))) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
