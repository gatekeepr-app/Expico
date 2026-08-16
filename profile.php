<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
function money($value) { return "৳" . number_format((float) $value, 2); }

function add_balance(&$balances, $payer_id, $receiver_id, $amount) {
    $payer_id = (int) $payer_id;
    $receiver_id = (int) $receiver_id;
    $amount = (float) $amount;

    if ($payer_id <= 0 || $receiver_id <= 0 || $payer_id === $receiver_id || $amount <= 0) {
        return;
    }

    $balances[$payer_id][$receiver_id] = ($balances[$payer_id][$receiver_id] ?? 0) + $amount;
    $balances[$receiver_id][$payer_id] = ($balances[$receiver_id][$payer_id] ?? 0) - $amount;
}

$stmt = $conn->prepare("SELECT name, email, phone_no, created_at FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM group_members WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$group_count = (int) ($stmt->get_result()->fetch_assoc()["total"] ?? 0);

$stmt = $conn->prepare("SELECT COUNT(*) AS total FROM payment_method WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payment_method_count = (int) ($stmt->get_result()->fetch_assoc()["total"] ?? 0);

$balances = [];

$stmt = $conn->prepare("SELECT e.user_id AS paid_by, ep.user_id AS participant_id, ep.share_amount FROM expenses e INNER JOIN expenses_participants ep ON e.expense_id = ep.expense_id WHERE ep.is_settled = 0 AND ep.user_id <> e.user_id AND (ep.user_id = ? OR e.user_id = ?)");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$rows = $stmt->get_result();
while ($row = $rows->fetch_assoc()) {
    add_balance($balances, $row["participant_id"], $row["paid_by"], $row["share_amount"]);
}

$stmt = $conn->prepare("SELECT s.user_id AS paid_by, sp.user_id AS participant_id, sp.share_amount FROM subscriptions s INNER JOIN subscription_participants sp ON s.subscription_id = sp.subscription_id WHERE sp.is_settled = 0 AND s.user_id IS NOT NULL AND sp.user_id <> s.user_id AND (sp.user_id = ? OR s.user_id = ?)");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$rows = $stmt->get_result();
while ($row = $rows->fetch_assoc()) {
    add_balance($balances, $row["participant_id"], $row["paid_by"], $row["share_amount"]);
}

$stmt = $conn->prepare("SELECT paid_by, paid_to, amount FROM settlements WHERE status <> 'paid' AND (paid_by = ? OR paid_to = ?)");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$rows = $stmt->get_result();
while ($row = $rows->fetch_assoc()) {
    add_balance($balances, $row["paid_by"], $row["paid_to"], $row["amount"]);
}

$you_owe = 0;
$you_are_owed = 0;
foreach ($balances[$user_id] ?? [] as $amount) {
    if ($amount > 0) {
        $you_owe += $amount;
    } else {
        $you_are_owed += abs($amount);
    }
}

$pageTitle = "Profile";
$pageSubtitle = "Account and settings";
$activeNav = "profile";
$basePath = "";
include "includes/header.php";
?>

<section class="summary-card">
    <p class="summary-label">Signed in as</p>
    <div class="amount" style="font-size:28px;"><?= htmlspecialchars($user["name"] ?? "User") ?></div>
    <p class="summary-subtext"><?= htmlspecialchars($user["email"] ?? "") ?></p>
</section>

<section class="mini-grid">
    <article class="mini-card"><p class="section-kicker">Groups</p><div class="amount"><?= $group_count ?></div></article>
    <article class="mini-card"><p class="section-kicker">Methods</p><div class="amount"><?= $payment_method_count ?></div></article>
</section>

<section class="mini-grid">
    <article class="mini-card"><p class="section-kicker">You Owe</p><div class="amount negative"><?= money($you_owe) ?></div></article>
    <article class="mini-card"><p class="section-kicker">You Are Owed</p><div class="amount positive"><?= money($you_are_owed) ?></div></article>
</section>

<div class="section-header"><h2>Account</h2></div>
<div class="card-list">
    <article class="payment-card"><div class="row-top"><div><h3>Phone</h3><p class="group-meta"><?= htmlspecialchars($user["phone_no"] ?: "Not added") ?></p></div></div></article>
    <article class="payment-card"><div class="row-top"><div><h3>Joined</h3><p class="group-meta"><?= htmlspecialchars(date("d M Y", strtotime($user["created_at"] ?? date("Y-m-d")))) ?></p></div></div></article>
</div>

<div class="section-header"><h2>Settings</h2></div>
<div class="card-list">
    <a class="secondary-button full-width" href="payment_methods/index.php">PAYMENT METHODS</a>
    <a class="danger-button full-width" href="logout.php">LOGOUT</a>
</div>

<?php include "includes/footer.php"; ?>
