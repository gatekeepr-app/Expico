<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = $_SESSION["login_error"] ?? "";
$success = $_SESSION["register_success"] ?? "";

unset($_SESSION["login_error"], $_SESSION["register_success"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Expico</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="auth-shell">
    <section class="auth-card">
        <a class="auth-logo" href="index.php">EXPICO</a>
        <h1>Welcome back</h1>
        <p class="auth-subtitle">Manage group expenses, balances, and settlements from one place.</p>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <form class="form-card" action="auth/login_process.php" method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
            </div>

            <button type="submit" class="primary-button full-width">LOGIN</button>
            <p class="muted" style="text-align:center;margin-top:14px;font-size:13px;">Forgot password? Contact your project admin.</p>
        </form>

        <p class="auth-footer">Don't have an account? <a href="register.php">Create account</a></p>
    </section>
</main>
<script src="js/app.js"></script>
</body>
</html>
