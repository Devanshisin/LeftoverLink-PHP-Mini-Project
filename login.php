<?php
// login.php – User login
require_once __DIR__ . '/auth.php';

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf'] ?? '')) {
        $errors[] = 'Invalid CSRF token.';
    }
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($email === '' || $password === '') {
        $errors[] = 'Both fields are required.';
    }
    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT id, password FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Incorrect email or password.';
        }
    }
}
$csrf = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login – LeftoverLink</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<?php include 'header.php'; ?>
<main class="container">
    <h2>Login</h2>
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
        <label>Email:<br><input type="email" name="email" value="<?= e($email ?? '') ?>" required></label>
        <label>Password:<br><input type="password" name="password" required></label>
        <button type="submit" class="btn-primary">Login</button>
    </form>
    <p>Don\'t have an account? <a href="register.php">Register here</a>.</p>
</main>
<?php include 'footer.php'; ?>
</body>
</html>
