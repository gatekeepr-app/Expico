<?php
$basePath = $basePath ?? "";
$activeNav = $activeNav ?? "home";

function expico_sidebar_icon($name) {
    $icons = [
        "home" => '<svg class="inline-icon" viewBox="0 0 24 24"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 10v10h14V10"/></svg>',
        "groups" => '<svg class="inline-icon" viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        "activity" => '<svg class="inline-icon" viewBox="0 0 24 24"><path d="M4 19V5"/><path d="M8 17V9"/><path d="M12 19V4"/><path d="M16 15v-5"/><path d="M20 19V7"/></svg>',
        "profile" => '<svg class="inline-icon" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>',
        "logout" => '<svg class="inline-icon" viewBox="0 0 24 24"><path d="M10 17l5-5-5-5"/><path d="M15 12H3"/><path d="M21 3v18"/></svg>',
    ];

    return $icons[$name] ?? $icons["home"];
}
?>

<aside class="desktop-sidebar" aria-label="Desktop navigation">
    <a class="sidebar-brand" href="<?= $basePath ?>dashboard.php" aria-label="Expico home">E</a>
    <a class="sidebar-link <?= $activeNav === 'home' ? 'active' : '' ?>" href="<?= $basePath ?>dashboard.php" aria-label="Home"><?= expico_sidebar_icon("home") ?></a>
    <a class="sidebar-link <?= $activeNav === 'groups' ? 'active' : '' ?>" href="<?= $basePath ?>groups/details.php" aria-label="Groups"><?= expico_sidebar_icon("groups") ?></a>
    <a class="sidebar-link <?= $activeNav === 'activity' ? 'active' : '' ?>" href="<?= $basePath ?>expenses/list.php" aria-label="Activity"><?= expico_sidebar_icon("activity") ?></a>
    <a class="sidebar-link <?= $activeNav === 'profile' ? 'active' : '' ?>" href="<?= $basePath ?>profile.php" aria-label="Profile"><?= expico_sidebar_icon("profile") ?></a>
    <div class="sidebar-spacer"></div>
    <a class="sidebar-link" href="<?= $basePath ?>logout.php" aria-label="Logout"><?= expico_sidebar_icon("logout") ?></a>
</aside>
