<?php
// request-food.php – Recipient submits a request for a food listing
require_once __DIR__ . '/auth.php';
requireLogin();
requireRole(['recipient']);
$user = currentUser();

$listingId = isset($_GET['listing_id']) ? (int)$_GET['listing_id'] : 0;
if ($listingId <= 0) {
    die('Invalid listing ID.');
}

// Fetch listing details
$stmt = $pdo->prepare("SELECT fl.*, u.name AS donor_name FROM food_listings fl JOIN users u ON fl.donor_id = u.id WHERE fl.id = ? AND fl.status = 'available' AND fl.pickup_deadline > NOW() AND fl.available_quantity > 0");
$stmt->execute([$listingId]);
$listing = $stmt->fetch();
if (!$listing) {
    die('Listing not available or does not exist.');
}
// Prevent donor from requesting own listing
if ($listing['donor_id'] == $user['id']) {
    die('You cannot request your own listing.');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }
    $quantity = $_POST['quantity_requested'] ?? '';
    if ($quantity === '' || !is_numeric($quantity) || $quantity <= 0) {
        $errors[] = 'Please enter a positive quantity.';
    } elseif ($quantity > $listing['available_quantity']) {
        $errors[] = 'Requested quantity exceeds available quantity.';
    }
    // Check duplicate pending request for same recipient & listing
    $stmt = $pdo->prepare('SELECT id FROM food_requests WHERE listing_id = ? AND recipient_id = ? AND status = ?');
    $stmt->execute([$listingId, $user['id'], 'pending']);
    if ($stmt->fetch()) {
        $errors[] = 'You already have a pending request for this listing.';
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO food_requests (listing_id, recipient_id, quantity_requested, status) VALUES (?, ?, ?, ?)');
        $stmt->execute([$listingId, $user['id'], $quantity, 'pending']);
        header('Location: my-requests.php');
        exit;
    }
}
$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Food – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <h2>Request Food: <?= e($listing['food_name']) ?></h2>
    <p><strong>Donor:</strong> <?= e($listing['donor_name']) ?></p>
    <p><strong>Available:</strong> <?= e($listing['available_quantity']) . ' ' . e($listing['unit']) ?></p>
    <p><strong>Pickup By:</strong> <?= e((new DateTime($listing['pickup_deadline']))->format('Y-m-d H:i')) ?></p>
    <?php if ($errors): ?>
        <div class="alert error"><ul><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="form-card">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label>Quantity Requested (<?= e($listing['unit']) ?>):<br>
            <input type="number" step="0.01" name="quantity_requested" required>
        </label>
        <button type="submit" class="btn-primary">Submit Request</button>
    </form>
    <p><a href="available-food.php" class="btn-secondary">Back to Listings</a></p>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
