<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$group_id = (int) ($_GET["group_id"] ?? 0);

function money($value) { return "৳" . number_format((float) $value, 2); }

function add_balance(&$balances, $payer_id, $receiver_id, $amount) {
    $payer_id = (int) $payer_id;
    $receiver_id = (int) $receiver_id;
    $amount = (float) $amount;

    if ($payer_id <= 0 || $receiver_id <= 0 || $payer_id === $receiver_id || $amount <= 0) {
        return;
    }

    $balances[$payer_id] = ($balances[$payer_id] ?? 0) - $amount;
    $balances[$receiver_id] = ($balances[$receiver_id] ?? 0) + $amount;
}

$stmt = $conn->prepare("SELECT group_id FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();

$pageTitle = "Members";
$pageSubtitle = "Group people and balances";
$activeNav = "groups";
$basePath = "../";
$showBack = true;
include "../includes/header.php";

if ($group_id <= 0 || $stmt->get_result()->num_rows === 0) {
    echo '<div class="empty-state"><h3>Group not found</h3><p>You do not have access to these members.</p></div>';
    include "../includes/footer.php";
    exit();
}

$stmt = $conn->prepare("SELECT u.user_id, u.name, gm.role FROM group_members gm INNER JOIN users u ON gm.user_id = u.user_id WHERE gm.group_id = ? ORDER BY gm.role = 'Admin' DESC, u.name");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members = $stmt->get_result();

$balances = [];
$stmt = $conn->prepare("SELECT e.user_id AS paid_by, ep.user_id AS participant_id, ep.share_amount FROM expenses e INNER JOIN expenses_participants ep ON e.expense_id = ep.expense_id WHERE e.group_id = ? AND ep.is_settled = 0 AND ep.user_id <> e.user_id");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$rows = $stmt->get_result();
while ($row = $rows->fetch_assoc()) {
    add_balance($balances, $row["participant_id"], $row["paid_by"], $row["share_amount"]);
}

$stmt = $conn->prepare("SELECT s.user_id AS paid_by, sp.user_id AS participant_id, sp.share_amount FROM subscriptions s INNER JOIN subscription_participants sp ON s.subscription_id = sp.subscription_id WHERE s.group_id = ? AND sp.is_settled = 0 AND s.user_id IS NOT NULL AND sp.user_id <> s.user_id");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$rows = $stmt->get_result();
while ($row = $rows->fetch_assoc()) {
    add_balance($balances, $row["participant_id"], $row["paid_by"], $row["share_amount"]);
}

$stmt = $conn->prepare("SELECT paid_by, paid_to, amount FROM settlements WHERE group_id = ? AND status <> 'paid'");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$rows = $stmt->get_result();
while ($row = $rows->fetch_assoc()) {
    add_balance($balances, $row["paid_by"], $row["paid_to"], $row["amount"]);
}
?>

<div class="card-list">
    <?php while ($member = $members->fetch_assoc()): ?>
        <?php $balance = (float) ($balances[(int) $member["user_id"]] ?? 0); ?>
        <article class="user-row">
            <span class="avatar"><?= htmlspecialchars(strtoupper(substr($member["name"], 0, 1))) ?></span>
            <span class="user-body"><h3><?= htmlspecialchars($member["name"]) ?></h3><span class="row-meta"><?= htmlspecialchars($member["role"] ?: "Member") ?></span></span>
            <span class="row-value <?= $balance >= 0 ? 'positive' : 'negative' ?>"><?= ($balance >= 0 ? '+' : '-') . money(abs($balance)) ?></span>
        </article>
    <?php endwhile; ?>
</div>

<?php include "../includes/footer.php"; ?>
