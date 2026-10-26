<?php
// register.php – User registration
require_once __DIR__ . '/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($name === '' || $email === '' || $password === '' || $role === '') {
        $errors[] = 'All fields are required.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email address.';
    }
    if (!in_array($role, ['donor', 'recipient'], true)) {
        $errors[] = 'Invalid role selection.';
    }
    if (empty($errors)) {
        // Check duplicate email
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
            $stmt->execute([$name, $email, $hash, $role]);
            // Auto‑login after registration
            $_SESSION['user_id'] = $pdo->lastInsertId();
            header('Location: dashboard.php');
            exit;
        }
    }
}
$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <h2>Register</h2>
    <?php if ($errors): ?>
        <div class="alert error">
            <ul>
                <?php foreach ($errors as $e): ?>
                    <li><?= e($e) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form method="post" class="form-card">
        <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
        <label>Name:<br><input type="text" name="name" value="<?= e($name ?? '') ?>" required></label>
        <label>Email:<br><input type="email" name="email" value="<?= e($email ?? '') ?>" required></label>
        <label>Password:<br><input type="password" name="password" required></label>
        <label>Role:<br>
            <select name="role" required>
                <option value="" disabled <?= empty($role) ? 'selected' : '' ?>>Select role</option>
                <option value="donor" <?= ($role ?? '') === 'donor' ? 'selected' : '' ?>>Donor</option>
                <option value="recipient" <?= ($role ?? '') === 'recipient' ? 'selected' : '' ?>>Recipient</option>
            </select>
        </label>
        <button type="submit" class="btn-primary">Register</button>
    </form>
    <p>Already have an account? <a href="login.php">Login here</a>.</p>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
