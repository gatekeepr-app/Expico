<?php
session_start();

if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expico - Split expenses. Stay balanced.</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<main class="landing-shell">
    <section class="landing-card">
        <div class="brand-mark">E</div>
        <h1>EXPICO</h1>
        <p>Split expenses, track balances, and settle group costs without confusion.</p>
        <div class="card-list">
            <a class="primary-button full-width" href="login.php">LOGIN</a>
            <a class="secondary-button full-width" href="register.php">CREATE ACCOUNT</a>
        </div>
    </section>
</main>
</body>
</html>
