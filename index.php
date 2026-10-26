<?php
// index.php – Public home page
require_once __DIR__ . '/auth.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>LeftoverLink – Surplus Food Redistribution</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'header.php'; ?>
    <main class="container">
        <section class="hero">
            <h2>Good Food Deserves a Second Chance</h2>
            <p>Connect canteens, cafés and event organizers with students who need extra meals.</p>
            <a class="btn-primary" href="available-food.php">Find Food</a>
            <a class="btn-secondary" href="add-food.php">Donate Surplus Food</a>
        </section>
        <section class="stats-grid">
            <?php
            // Simple impact statistics – executed on page load
            try {
                $stmt = $pdo->query('SELECT COUNT(*) FROM food_listings');
                $totalListings = $stmt->fetchColumn();
                $stmt = $pdo->query("SELECT COUNT(*) FROM food_requests WHERE status='collected'");
                $totalCollected = $stmt->fetchColumn();
            } catch (PDOException $e) {
                $totalListings = $totalCollected = 0;
            }
            ?>
            <div class="card">
                <h3>Total Listings</h3>
                <p><?php echo e((string)$totalListings); ?></p>
            </div>
            <div class="card">
                <h3>Food Collected (Requests)</h3>
                <p><?php echo e((string)$totalCollected); ?></p>
            </div>
        </section>
        <section class="how-grid">
        <div class="how-card">
            <h4>1. List Surplus Food</h4>
            <p>Donors create a listing with details, quantity and pickup deadline.</p>
        </div>
        <div class="how-card">
            <h4>2. Request Food</h4>
            <p>Recipients browse available listings and request the amount they need.</p>
        </div>
        <div class="how-card">
            <h4>3. Confirm Collection</h4>
            <p>Donors approve requests, reserve quantity, and mark the food as collected after hand‑over.</p>
        </div>
    </section>
    </main>
    <footer>
        <p>Academic prototype – not intended for production use.</p>
    </footer>
</body>
</html>
