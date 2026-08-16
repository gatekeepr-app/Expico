<?php
$basePath = $basePath ?? "";
$pageTitle = $pageTitle ?? "Dashboard";
$pageSubtitle = $pageSubtitle ?? "";
$activeNav = $activeNav ?? "home";
$showBack = $showBack ?? false;
$backHref = $backHref ?? "javascript:history.back()";
$userName = $_SESSION["user_name"] ?? "User";
$userInitial = strtoupper(substr($userName, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Expico</title>
    <link rel="stylesheet" href="<?= $basePath ?>css/style.css">
</head>
<body>
<div class="app-shell">
    <?php include __DIR__ . "/sidebar.php"; ?>
    <div class="app-main-wrap">
        <header class="app-header">
            <?php if ($showBack): ?>
                <a class="icon-button" href="<?= htmlspecialchars($backHref) ?>" aria-label="Go back">
                    <svg class="inline-icon" viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"/></svg>
                </a>
            <?php else: ?>
                <a class="icon-button" href="<?= $basePath ?>notifications/index.php" aria-label="Notifications">
                    <svg class="inline-icon" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/></svg>
                </a>
            <?php endif; ?>

            <div class="header-title">
                <h1><?= htmlspecialchars($pageTitle) ?></h1>
                <?php if ($pageSubtitle !== ""): ?>
                    <p><?= htmlspecialchars($pageSubtitle) ?></p>
                <?php endif; ?>
            </div>

            <a class="avatar has-dot" href="<?= $basePath ?>profile.php" aria-label="Profile">
                <?= htmlspecialchars($userInitial) ?>
            </a>
        </header>
        <main class="app-main">
