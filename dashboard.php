<?php
session_start();
require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$user_name = $_SESSION["user_name"] ?? "User";

function money($value) {
    return "৳" . number_format((float) $value, 2);
}

function scalar_query($conn, $sql, $types, ...$params) {
    $stmt = $conn->prepare($sql);
    if ($types !== "") {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    return array_values($row ?? [0])[0] ?? 0;
}

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

function add_group_balance(&$group_balances, $group_id, $payer_id, $receiver_id, $amount) {
    $group_id = (int) $group_id;
    if (!isset($group_balances[$group_id])) {
        $group_balances[$group_id] = [];
    }

    add_balance($group_balances[$group_id], $payer_id, $receiver_id, $amount);
}

$total_expenses = 0;
$balances = [];
$group_balances = [];
$current_month = date("m");
$current_year = date("Y");

$stmt = $conn->prepare("SELECT e.group_id, e.user_id AS paid_by, e.amount, e.expense_date FROM expenses e INNER JOIN group_members gm ON e.group_id = gm.group_id WHERE gm.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$expenses_for_user = $stmt->get_result();
while ($expense = $expenses_for_user->fetch_assoc()) {
    if (date("m", strtotime($expense["expense_date"])) === $current_month && date("Y", strtotime($expense["expense_date"])) === $current_year) {
        $total_expenses += (float) $expense["amount"];
    }
}

$stmt = $conn->prepare("SELECT s.amount, s.next_due_date FROM subscriptions s INNER JOIN `get` gu ON s.subscription_id = gu.subscription_id WHERE gu.user_id = ? AND s.next_due_date IS NOT NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$subscriptions_for_user = $stmt->get_result();
while ($subscription = $subscriptions_for_user->fetch_assoc()) {
    if (date("m", strtotime($subscription["next_due_date"])) === $current_month && date("Y", strtotime($subscription["next_due_date"])) === $current_year) {
        $total_expenses += (float) $subscription["amount"];
    }
}

$stmt = $conn->prepare("SELECT e.group_id, e.user_id AS paid_by, ep.user_id AS participant_id, ep.share_amount FROM expenses e INNER JOIN expenses_participants ep ON e.expense_id = ep.expense_id WHERE ep.is_settled = 0 AND ep.user_id <> e.user_id AND (ep.user_id = ? OR e.user_id = ?)");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$expense_balances = $stmt->get_result();
while ($row = $expense_balances->fetch_assoc()) {
    add_balance($balances, $row["participant_id"], $row["paid_by"], $row["share_amount"]);
    add_group_balance($group_balances, $row["group_id"], $row["participant_id"], $row["paid_by"], $row["share_amount"]);
}

$stmt = $conn->prepare("SELECT s.group_id, s.user_id AS paid_by, sp.user_id AS participant_id, sp.share_amount FROM subscriptions s INNER JOIN subscription_participants sp ON s.subscription_id = sp.subscription_id WHERE sp.is_settled = 0 AND s.user_id IS NOT NULL AND sp.user_id <> s.user_id AND (sp.user_id = ? OR s.user_id = ?)");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$subscription_balances = $stmt->get_result();
while ($row = $subscription_balances->fetch_assoc()) {
    add_balance($balances, $row["participant_id"], $row["paid_by"], $row["share_amount"]);
    add_group_balance($group_balances, $row["group_id"], $row["participant_id"], $row["paid_by"], $row["share_amount"]);
}

$stmt = $conn->prepare("SELECT group_id, paid_by, paid_to, amount FROM settlements WHERE status <> 'paid' AND (paid_by = ? OR paid_to = ?)");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$settlement_balances = $stmt->get_result();
while ($row = $settlement_balances->fetch_assoc()) {
    add_balance($balances, $row["paid_by"], $row["paid_to"], $row["amount"]);
    add_group_balance($group_balances, $row["group_id"], $row["paid_by"], $row["paid_to"], $row["amount"]);
}

$you_owe = 0;
$you_are_owed = 0;
foreach ($group_balances as $group_balance) {
    foreach ($group_balance[$user_id] ?? [] as $amount) {
        if ($amount > 0) {
            $you_owe += $amount;
        } else {
            $you_are_owed += abs($amount);
        }
    }
}

$stmt = $conn->prepare("SELECT g.group_id, g.group_name, g.description FROM groups g INNER JOIN group_members gm ON g.group_id = gm.group_id WHERE gm.user_id = ? ORDER BY g.created_at DESC LIMIT 4");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$group_rows = $stmt->get_result();
$groups = [];
while ($group = $group_rows->fetch_assoc()) {
    $current_group_id = (int) $group["group_id"];

    $stmt = $conn->prepare("SELECT user_id FROM group_members WHERE group_id = ?");
    $stmt->bind_param("i", $current_group_id);
    $stmt->execute();
    $members = $stmt->get_result();
    $group["member_count"] = $members->num_rows;

    $total_amount = 0;
    $stmt = $conn->prepare("SELECT amount FROM expenses WHERE group_id = ?");
    $stmt->bind_param("i", $current_group_id);
    $stmt->execute();
    $expenses = $stmt->get_result();
    while ($expense = $expenses->fetch_assoc()) {
        $total_amount += (float) $expense["amount"];
    }

    $stmt = $conn->prepare("SELECT amount FROM subscriptions WHERE group_id = ?");
    $stmt->bind_param("i", $current_group_id);
    $stmt->execute();
    $subscriptions = $stmt->get_result();
    while ($subscription = $subscriptions->fetch_assoc()) {
        $total_amount += (float) $subscription["amount"];
    }

    $group["total_amount"] = $total_amount;
    $group["user_balance"] = array_sum($group_balances[$current_group_id][$user_id] ?? []);
    $groups[] = $group;
}

$stmt = $conn->prepare("
    SELECT e.expense_id, e.title, e.amount, e.expense_date, g.group_name, u.name AS paid_by
    FROM expenses e
    INNER JOIN groups g ON e.group_id = g.group_id
    INNER JOIN users u ON e.user_id = u.user_id
    INNER JOIN group_members gm ON e.group_id = gm.group_id
    WHERE gm.user_id = ?
    ORDER BY e.created_at DESC
    LIMIT 6
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_expenses = $stmt->get_result();

$pageTitle = "Dashboard";
$pageSubtitle = "Group expense overview";
$activeNav = "home";
$basePath = "";
include "includes/header.php";
?>

<section>
    <p class="page-kicker">Good morning, <?= htmlspecialchars(explode(" ", $user_name)[0]) ?></p>
    <h2 class="hero-title">Split expenses. Stay balanced.</h2>
</section>

<section class="summary-card" style="margin-top:22px;">
    <p class="summary-label">Total Expenses</p>
    <div class="amount"><?= money($total_expenses) ?></div>
    <p class="summary-subtext">This month across your groups</p>
</section>

<section class="mini-grid">
    <article class="mini-card">
        <p class="section-kicker">You Owe</p>
        <div class="amount negative"><?= money($you_owe) ?></div>
    </article>
    <article class="mini-card">
        <p class="section-kicker">You Are Owed</p>
        <div class="amount positive"><?= money($you_are_owed) ?></div>
    </article>
</section>

<section>
    <div class="section-header">
        <h2>Your Groups</h2>
        <a class="section-link" href="groups/details.php">View all</a>
    </div>

    <div class="card-list">
        <?php if (count($groups) > 0): ?>
            <?php foreach ($groups as $group): ?>
                <a class="group-card" href="groups/details.php?group_id=<?= (int) $group["group_id"] ?>">
                    <div class="group-card-top">
                        <div>
                            <h3><?= htmlspecialchars($group["group_name"]) ?></h3>
                            <p class="group-meta"><?= htmlspecialchars($group["description"] ?: "Shared expense group") ?></p>
                        </div>
                        <span class="badge"><?= (int) $group["member_count"] ?> members</span>
                    </div>
                    <div class="group-stats">
                        <div class="stat-pill"><span>Total</span><strong><?= money($group["total_amount"]) ?></strong></div>
                        <?php $group_balance = (float) $group["user_balance"]; ?>
                        <div class="stat-pill"><span><?= $group_balance >= 0 ? 'You owe' : 'You are owed' ?></span><strong class="<?= $group_balance >= 0 ? 'negative' : 'positive' ?>"><?= money(abs($group_balance)) ?></strong></div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>No groups yet</h3>
                <p>Create or join a group to start splitting expenses.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<section>
    <div class="section-header">
        <h2>Recent Expenses</h2>
        <a class="section-link" href="expenses/list.php">View all</a>
    </div>

    <div class="card-list">
        <?php if ($recent_expenses->num_rows > 0): ?>
            <?php while ($expense = $recent_expenses->fetch_assoc()): ?>
                <a class="transaction-row" href="expenses/details.php?expense_id=<?= (int) $expense["expense_id"] ?>">
                    <span class="icon-circle"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M6 7v12h12V7"/><path d="M9 7a3 3 0 0 1 6 0"/></svg></span>
                    <span class="transaction-body">
                        <h3><?= htmlspecialchars($expense["title"]) ?></h3>
                        <span class="transaction-meta"><?= htmlspecialchars($expense["group_name"]) ?> · <?= htmlspecialchars(date("M j", strtotime($expense["expense_date"]))) ?></span>
                    </span>
                    <span class="transaction-amount"><?= money($expense["amount"]) ?></span>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state">
                <h3>No expenses yet</h3>
                <p>Add your first shared expense from the center plus button.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php include "includes/footer.php"; ?>
