<?php
$basePath = $basePath ?? "";
$activeNav = $activeNav ?? "home";
?>

<nav class="bottom-nav" aria-label="Primary navigation">
    <a class="nav-link <?= $activeNav === 'home' ? 'active' : '' ?>" href="<?= $basePath ?>dashboard.php">
        <?= expico_sidebar_icon("home") ?>
        <span>Home</span>
    </a>
    <a class="nav-link <?= $activeNav === 'groups' ? 'active' : '' ?>" href="<?= $basePath ?>groups/details.php">
        <?= expico_sidebar_icon("groups") ?>
        <span>Groups</span>
    </a>
    <button class="fab" type="button" data-open-sheet aria-label="Open quick actions">+</button>
    <a class="nav-link <?= $activeNav === 'activity' ? 'active' : '' ?>" href="<?= $basePath ?>expenses/list.php">
        <?= expico_sidebar_icon("activity") ?>
        <span>Activity</span>
    </a>
    <a class="nav-link <?= $activeNav === 'profile' ? 'active' : '' ?>" href="<?= $basePath ?>profile.php">
        <?= expico_sidebar_icon("profile") ?>
        <span>Profile</span>
    </a>
</nav>

<div class="bottom-sheet-backdrop" data-sheet-backdrop></div>
<section class="bottom-sheet" data-bottom-sheet aria-label="Quick actions">
    <div class="sheet-handle"></div>
    <h2 class="sheet-title">Add to Expico</h2>
    <div class="sheet-actions">
        <a class="sheet-action" href="<?= $basePath ?>expenses/add.php"><?= expico_sidebar_icon("activity") ?> Add Expense</a>
        <a class="sheet-action" href="<?= $basePath ?>groups/create.php"><?= expico_sidebar_icon("groups") ?> Create Group</a>
        <a class="sheet-action" href="<?= $basePath ?>groups/join.php"><?= expico_sidebar_icon("groups") ?> Join Group</a>
        <a class="sheet-action" href="<?= $basePath ?>subscriptions/add.php"><svg viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="3"/><path d="M8 9h8M8 13h5"/></svg> Add Subscription</a>
        <a class="sheet-action" href="<?= $basePath ?>payment_methods/index.php"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18"/></svg> Add Payment Method</a>
        <a class="sheet-action" href="<?= $basePath ?>logout.php"><?= expico_sidebar_icon("logout") ?> Logout</a>
    </div>
</section>
