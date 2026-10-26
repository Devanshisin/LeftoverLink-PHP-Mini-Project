<?php
// header.php – reusable page header with navigation bar
require_once __DIR__ . '/auth.php';
?>
<header class="navbar">
    <h1><a href="index.php" class="logo">LeftoverLink</a></h1>
    <input type="checkbox" id="nav-toggle" />
    <label class="menu-toggle" for="nav-toggle">&#9776;</label>
    <nav>
        <?php if (isLoggedIn()): ?>
            <a href="dashboard.php">Dashboard</a>
            <?php if (currentUser()['role'] === 'donor'): ?>
                <a href="add-food.php">Add Food</a>
                <a href="my-listings.php">My Listings</a>
                <a href="manage-requests.php">Manage Requests</a>
            <?php elseif (currentUser()['role'] === 'recipient'): ?>
                <a href="available-food.php">Available Food</a>
                <a href="my-requests.php">My Requests</a>
            <?php elseif (currentUser()['role'] === 'admin'): ?>
                <a href="admin.php">Admin</a>
            <?php endif; ?>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </nav>
</header>
