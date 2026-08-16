<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}

$error = $_SESSION["register_error"] ?? "";
unset($_SESSION["register_error"]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - Expico</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="auth-shell">
    <section class="auth-card">
        <a class="auth-logo" href="index.php">EXPICO</a>
        <h1>Create your account</h1>
        <p class="auth-subtitle">Start splitting group expenses and tracking what everyone owes.</p>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form class="form-card" action="auth/register_process.php" method="POST">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" placeholder="Your full name" autocomplete="name" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" placeholder="you@example.com" autocomplete="email" required>
            </div>

            <div class="form-group">
                <label for="phone_no">Phone</label>
                <input type="tel" id="phone_no" name="phone_no" placeholder="01XXXXXXXXX" autocomplete="tel">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" placeholder="At least 6 characters" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" data-toggle-password="password" aria-label="Show password" aria-pressed="false">Show</button>
                </div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="password-field">
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Repeat your password" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" data-toggle-password="confirm_password" aria-label="Show confirm password" aria-pressed="false">Show</button>
                </div>
            </div>

            <button type="submit" class="primary-button full-width">CREATE ACCOUNT</button>
        </form>

        <p class="auth-footer">Already have an account? <a href="login.php">Login</a></p>
    </section>
</main>
<script src="js/app.js"></script>
</body>
</html>
