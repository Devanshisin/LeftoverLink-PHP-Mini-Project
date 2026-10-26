<?php
// admin.php – Simple administrator dashboard
require_once __DIR__ . '/auth.php';
requireLogin();
requireRole(['admin']);
$user = currentUser();

// Handle listing approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['listing_id'])) {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $msg = 'Invalid CSRF token.';
    } else {
        $listingId = (int)$_POST['listing_id'];
        $action = $_POST['action'];
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE food_listings SET status = 'available' WHERE id = ? AND status = 'pending'");
            $stmt->execute([$listingId]);
            $msg = 'Listing approved.';
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE food_listings SET status = 'rejected' WHERE id = ? AND status = 'pending'");
            $stmt->execute([$listingId]);
            $msg = 'Listing rejected.';
        }
    }
}
$csrf = generateCsrfToken();

// Gather statistics
try {
    $stats = [];
    $stats['users'] = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $stats['listings'] = $pdo->query('SELECT COUNT(*) FROM food_listings')->fetchColumn();
    $stats['available'] = $pdo->query("SELECT COUNT(*) FROM food_listings WHERE status = 'available'")->fetchColumn();
    $stats['requests'] = $pdo->query('SELECT COUNT(*) FROM food_requests')->fetchColumn();
    $stats['collected'] = $pdo->query("SELECT COUNT(*) FROM food_requests WHERE status = 'collected'")->fetchColumn();
    $stmt = $pdo->query("SELECT SUM(quantity_requested) FROM food_requests WHERE status = 'collected'");
    $stats['total_quantity'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    $stats = array_fill_keys(['users','listings','available','requests','collected','total_quantity'], 0);
}

// Fetch pending listings for admin review
$pendingListings = $pdo->query("SELECT fl.id, fl.food_name, fl.quantity, fl.unit, u.name AS donor_name FROM food_listings fl JOIN users u ON fl.donor_id = u.id WHERE fl.status = 'pending'")->fetchAll();

// Fetch all users (basic info)
$users = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <h2>Administrator Dashboard</h2>
    <?php if (!empty($msg)): ?>
        <div class="alert success"><?= e($msg) ?></div>
    <?php endif; ?>
    <section class="stats-grid">
        <div class="card"><h3>Total Users</h3><p><?= e($stats['users']) ?></p></div>
        <div class="card"><h3>Total Listings</h3><p><?= e($stats['listings']) ?></p></div>
        <div class="card"><h3>Available Listings</h3><p><?= e($stats['available']) ?></p></div>
        <div class="card"><h3>Total Requests</h3><p><?= e($stats['requests']) ?></p></div>
        <div class="card"><h3>Completed Donations</h3><p><?= e($stats['collected']) ?></p></div>
        <div class="card"><h3>Total Quantity Collected</h3><p><?= e($stats['total_quantity'] ?? 0) ?></p></div>
    </section>
    <section>
        <h3>Pending Food Listings (Admin Review)</h3>
        <?php if (empty($pendingListings)): ?>
            <p>No pending listings.</p>
        <?php else: ?>
            <table class="listings-table">
                <thead>
                    <tr><th>Food</th><th>Donor</th><th>Quantity</th><th>Action</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingListings as $l): ?>
                        <tr>
                            <td><?= e($l['food_name']) ?></td>
                            <td><?= e($l['donor_name']) ?></td>
                            <td><?= e($l['quantity']) . ' ' . e($l['unit']) ?></td>
                            <td>
                                <form method="post" style="display:inline;">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                                    <button type="submit" name="action" value="approve" class="btn-primary">Approve</button>
                                </form>
                                <form method="post" style="display:inline; margin-left:5px;">
                                    <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
                                    <input type="hidden" name="listing_id" value="<?= $l['id'] ?>">
                                    <button type="submit" name="action" value="reject" class="btn-danger">Reject</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </section>
    <section>
        <h3>All Users</h3>
        <table class="listings-table">
            <thead>
                <tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= e($u['id']) ?></td>
                        <td><?= e($u['name']) ?></td>
                        <td><?= e($u['email']) ?></td>
                        <td><?= e(ucfirst($u['role'])) ?></td>
                        <td><?= e($u['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
