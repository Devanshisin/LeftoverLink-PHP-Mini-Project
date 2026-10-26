<?php
// dashboard.php – Role‑specific dashboard
require_once __DIR__ . '/auth.php';
requireLogin();
$user = currentUser();
$role = $user['role'];

// Common statistics
$stats = [];
try {
    // Total listings
    $stats['total_listings'] = $pdo->query('SELECT COUNT(*) FROM food_listings')->fetchColumn();
    // Available listings (status available and not expired)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM food_listings WHERE status = 'available' AND pickup_deadline > NOW()");
    $stmt->execute();
    $stats['available_listings'] = $stmt->fetchColumn();
    // Pending requests (for donors they see their own, for admin all)
    $stats['pending_requests'] = $pdo->query("SELECT COUNT(*) FROM food_requests WHERE status = 'pending'")->fetchColumn();
    // Completed donations (collected requests)
    $stats['completed_donations'] = $pdo->query("SELECT COUNT(*) FROM food_requests WHERE status = 'collected'")->fetchColumn();
    // Total quantity collected (sum of quantity_requested of collected requests)
    $stmt = $pdo->prepare("SELECT SUM(fr.quantity_requested) FROM food_requests fr WHERE fr.status = 'collected'");
    $stmt->execute();
    $stats['total_quantity'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Graceful fallback
    $stats = array_fill_keys(['total_listings','available_listings','pending_requests','completed_donations','total_quantity'], 0);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <h2>Welcome, <?= e($user['name']) ?> (<?= e(ucfirst($role)) ?>)</h2>
    <section class="stats-grid">
        <div class="card"><h3>Total Listings</h3><p><?= e((string)$stats['total_listings']) ?></p></div>
        <div class="card"><h3>Available Listings</h3><p><?= e((string)$stats['available_listings']) ?></p></div>
        <div class="card"><h3>Pending Requests</h3><p><?= e((string)$stats['pending_requests']) ?></p></div>
        <div class="card"><h3>Completed Donations</h3><p><?= e((string)$stats['completed_donations']) ?></p></div>
        <div class="card"><h3>Total Quantity Collected</h3><p><?= e((string)($stats['total_quantity'] ?? 0)) ?></p></div>
    </section>
    <?php if ($role === 'donor'): ?>
        <section class="actions">
            <a class="btn-primary" href="add-food.php">Add New Food Listing</a>
            <a class="btn-primary" href="my-listings.php">My Listings</a>
            <a class="btn-primary" href="manage-requests.php">Manage Requests</a>
        </section>
    <?php elseif ($role === 'recipient'): ?>
        <section class="actions">
            <a class="btn-primary" href="available-food.php">Browse Food</a>
            <a class="btn-primary" href="my-requests.php">My Requests</a>
        </section>
    <?php elseif ($role === 'admin'): ?>
        <section class="actions">
            <a class="btn-primary" href="admin.php">Admin Dashboard</a>
        </section>
    <?php endif; ?>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
