<?php
// my-requests.php – Recipient view of their request history
require_once __DIR__ . '/auth.php';
requireLogin();
requireRole(['recipient']);
$user = currentUser();

// Update any expired listings (so status is accurate) – safe to run on each page load
$pdo->exec("
    UPDATE food_listings
    SET status = 'expired'
    WHERE status IN ('available', 'partially_reserved')
      AND pickup_deadline <= NOW()
");

$stmt = $pdo->prepare("SELECT fr.id, fr.quantity_requested, fr.status, fr.created_at, fl.food_name, fl.unit, fl.pickup_deadline, u.name AS donor_name FROM food_requests fr JOIN food_listings fl ON fr.listing_id = fl.id JOIN users u ON fl.donor_id = u.id WHERE fr.recipient_id = ? ORDER BY fr.created_at DESC");
$stmt->execute([$user['id']]);
$requests = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Requests – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <h2>My Food Requests</h2>
    <?php if (empty($requests)): ?>
        <p>You have not made any requests yet.</p>
    <?php else: ?>
        <table class="listings-table">
            <thead>
                <tr>
                    <th>Food</th>
                    <th>Donor</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Requested At</th>
                    <th>Pickup By</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $r): ?>
                    <tr>
                        <td><?= e($r['food_name']) ?></td>
                        <td><?= e($r['donor_name']) ?></td>
                        <td><?= e($r['quantity_requested']) . ' ' . e($r['unit']) ?></td>
                        <td><?= e(ucfirst(str_replace('_', ' ', $r['status']))) ?></td>
                        <td><?= e($r['created_at']) ?></td>
                        <td><?= e((new DateTime($r['pickup_deadline']))->format('Y-m-d H:i')) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
