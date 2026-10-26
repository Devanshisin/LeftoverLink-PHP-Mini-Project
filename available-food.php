<?php
// available-food.php – Recipient browses available food listings
require_once __DIR__ . '/auth.php';
requireLogin();
$user = currentUser();
requireRole(['recipient']);

// Optional category filter
$categoryFilter = trim($_GET['category'] ?? '');

$where = "WHERE fl.status = 'available' AND fl.pickup_deadline > NOW() AND fl.available_quantity > 0";
$params = [];
if ($categoryFilter !== '') {
    $where .= " AND fl.category = ?";
    $params[] = $categoryFilter;
}

$stmt = $pdo->prepare("SELECT fl.id, fl.food_name, fl.description, fl.category, fl.available_quantity, fl.unit, fl.location, fl.pickup_deadline, u.name AS donor_name FROM food_listings fl JOIN users u ON fl.donor_id = u.id $where ORDER BY fl.pickup_deadline ASC");
$stmt->execute($params);
$listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Available Food – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <h2>Available Food Listings</h2>
    <form method="get" class="filter-form">
        <label>Category: <input type="text" name="category" value="<?= e($categoryFilter) ?>"></label>
        <button type="submit" class="btn-primary">Filter</button>
        <a href="available-food.php" class="btn-secondary">Reset</a>
    </form>
    <?php if (empty($listings)): ?>
        <p>No food listings found.</p>
    <?php else: ?>
        <table class="listings-table">
            <thead>
                <tr>
                    <th>Food</th>
                    <th>Description</th>
                    <th>Category</th>
                    <th>Quantity</th>
                    <th>Location</th>
                    <th>Pickup By</th>
                    <th>Donor</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($listings as $l): ?>
                    <tr>
                        <td><?= e($l['food_name']) ?></td>
                        <td><?= e($l['description']) ?></td>
                        <td><?= e($l['category']) ?></td>
                        <td><?= e($l['available_quantity']) . ' ' . e($l['unit']) ?></td>
                        <td><?= e($l['location']) ?></td>
                        <td><?= e((new DateTime($l['pickup_deadline']))->format('Y-m-d H:i')) ?></td>
                        <td><?= e($l['donor_name']) ?></td>
                        <td>
                            <?php if ($l['donor_name'] !== $user['name']): ?>
                                <a class="btn-primary" href="request-food.php?listing_id=<?= $l['id'] ?>">Request</a>
                            <?php else: ?>
                                <span class="badge">Your listing</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
