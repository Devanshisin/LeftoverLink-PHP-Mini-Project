<?php
// create-admin.php – One‑time administrator setup page
// Place this file in the project root (c:/xampp/htdocs/leftoverlink) and open it once via the browser.
// After a successful admin creation, delete or move this file immediately.

require_once __DIR__ . '/auth.php'; // loads $pdo, CSRF helpers, e()

/**
 * Check whether any admin already exists.
 */
function adminExists(PDO $pdo): bool {
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    return (bool) $stmt->fetchColumn();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $error = 'Invalid CSRF token.';
    } elseif (empty($_POST['name']) || empty($_POST['email']) || empty($_POST['password']) || empty($_POST['confirm'])) {
        $error = 'All fields are required.';
    } elseif (!filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif ($_POST['password'] !== $_POST['confirm']) {
        $error = 'Passwords do not match.';
    } elseif (adminExists($pdo)) {
        $error = 'An administrator account already exists.';
    } else {
        // Insert admin user with hashed password
        $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
        $stmt->execute([
            $_POST['name'],
            $_POST['email'],
            $hash,
            'admin'
        ]);
        $success = 'Administrator account created successfully. You can now log in via <a href="login.php">login.php</a>.';
    }
}

// Generate CSRF token for the form
$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Admin – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
    <style>
        body {font-family:Arial,Helvetica,sans-serif;background:#f4f4f9;margin:2rem;}
        .container {max-width:500px;margin:auto;background:#fff;padding:2rem;border-radius:8px;box-shadow:0 4px 10px rgba(0,0,0,.1);}
        .alert {padding:.8rem;margin-bottom:1rem;border-radius:4px;}
        .error {background:#ffe5e5;color:#b00;}
        .success {background:#e5ffe5;color:#070;}
    </style>
</head>
<body>
<div class="container">
    <h2>Create Administrator Account</h2>
    <?php if (!empty($error)): ?>
        <div class="alert error"><?php echo e($error); ?></div>
    <?php elseif (!empty($success)): ?>
        <div class="alert success"><?php echo $success; // safe HTML ?></div>
        <p><strong>Security step:</strong> Delete or move <code>create-admin.php</code> from the web‑accessible directory immediately.</p>
    <?php elseif (adminExists($pdo)): ?>
        <div class="alert error">An administrator already exists. This setup page is now disabled.</div>
    <?php else: ?>
        <form method="post" class="form-card">
            <input type="hidden" name="csrf" value="<?php echo e($csrf); ?>">
            <label>Name:<br><input type="text" name="name" required></label><br><br>
            <label>Email:<br><input type="email" name="email" required></label><br><br>
            <label>Password:<br><input type="password" name="password" required></label><br><br>
            <label>Confirm Password:<br><input type="password" name="confirm" required></label><br><br>
            <button type="submit" class="btn-primary">Create Admin</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>
