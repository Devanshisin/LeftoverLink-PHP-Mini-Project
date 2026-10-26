<?php
// add-food.php – Donor creates a new food listing
require_once __DIR__ . '/auth.php';
requireLogin();
requireRole(['donor']);

$user = currentUser();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }
    $food_name = trim($_POST['food_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $quantity = $_POST['quantity'] ?? '';
    $unit = trim($_POST['unit'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $prep = trim($_POST['preparation_details'] ?? '');
    $deadline = $_POST['pickup_deadline'] ?? '';

    // Basic validation
    if ($food_name === '' || $quantity === '' || $unit === '' || $location === '' || $deadline === '') {
        $errors[] = 'Please fill in all required fields.';
    }
    if (!is_numeric($quantity) || $quantity <= 0) {
        $errors[] = 'Quantity must be a positive number.';
    }
    $deadline_dt = DateTime::createFromFormat('!Y-m-d\TH:i', $deadline);
    if (!$deadline_dt) {
        $errors[] = 'Invalid deadline format.';
    } elseif ($deadline_dt <= new DateTime()) {
        $errors[] = 'Pickup deadline must be in the future.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO food_listings (donor_id, food_name, description, category, quantity, unit, location, preparation_details, pickup_deadline, status, available_quantity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $stmt->execute([
            $user['id'],
            $food_name,
            $description,
            $category,
            $quantity,
            $unit,
            $location,
            $prep,
            $deadline_dt->format('Y-m-d H:i:s'),
            'pending', // admin approval required
            $quantity // available quantity initially same as total
        ]);
        header('Location: dashboard.php');
        exit;
    }
}
$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Food – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <h2>Add New Food Listing</h2>
    <?php if ($errors): ?>
        <div class="alert error"><ul><?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>
    <form method="post" class="form-card">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label>Food Name:<br><input type="text" name="food_name" value="<?= e($food_name ?? '') ?>" required></label>
        <label>Description:<br><textarea name="description"><?= e($description ?? '') ?></textarea></label>
        <label>Category:<br><input type="text" name="category" value="<?= e($category ?? '') ?>"></label>
        <label>Quantity:<br><input type="number" step="0.01" name="quantity" value="<?= e($quantity ?? '') ?>" required></label>
        <label>Unit (e.g., pieces, kg):<br><input type="text" name="unit" value="<?= e($unit ?? '') ?>" required></label>
        <label>Location:<br><input type="text" name="location" value="<?= e($location ?? '') ?>" required></label>
        <label>Preparation Details:<br><textarea name="preparation_details"><?= e($prep ?? '') ?></textarea></label>
        <label>Pickup Deadline:<br><input type="datetime-local" name="pickup_deadline" value="<?= e($deadline ?? '') ?>" required></label>
        <button type="submit" class="btn-primary">Submit Listing</button>
    </form>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
