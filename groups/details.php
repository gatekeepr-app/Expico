<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$group_id = isset($_GET["group_id"]) ? (int) $_GET["group_id"] : 0;
$error = "";

function money($value) {
    return "৳" . number_format((float) $value, 2);
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

function get_group_balances($conn, $group_id) {
    $balances = [];

    $stmt = $conn->prepare("SELECT e.user_id AS paid_by, ep.user_id AS participant_id, ep.share_amount FROM expenses e INNER JOIN expenses_participants ep ON e.expense_id = ep.expense_id WHERE e.group_id = ? AND ep.is_settled = 0 AND ep.user_id <> e.user_id");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $expenses = $stmt->get_result();
    while ($expense = $expenses->fetch_assoc()) {
        add_balance($balances, $expense["participant_id"], $expense["paid_by"], $expense["share_amount"]);
    }

    $stmt = $conn->prepare("SELECT s.user_id AS paid_by, sp.user_id AS participant_id, sp.share_amount FROM subscriptions s INNER JOIN subscription_participants sp ON s.subscription_id = sp.subscription_id WHERE s.group_id = ? AND sp.is_settled = 0 AND s.user_id IS NOT NULL AND sp.user_id <> s.user_id");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $subscriptions = $stmt->get_result();
    while ($subscription = $subscriptions->fetch_assoc()) {
        add_balance($balances, $subscription["participant_id"], $subscription["paid_by"], $subscription["share_amount"]);
    }

    $stmt = $conn->prepare("SELECT paid_by, paid_to, amount FROM settlements WHERE group_id = ? AND status <> 'paid'");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $settlements = $stmt->get_result();
    while ($settlement = $settlements->fetch_assoc()) {
        add_balance($balances, $settlement["paid_by"], $settlement["paid_to"], $settlement["amount"]);
    }

    return $balances;
}

$pageTitle = $group_id > 0 ? "Group Details" : "Your Groups";
$pageSubtitle = $group_id > 0 ? "Members, expenses, settlements" : "Shared expense spaces";
$activeNav = "groups";
$basePath = "../";
$showBack = $group_id > 0;

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "leave_group" && $group_id > 0) {
    $stmt = $conn->prepare("SELECT group_id FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $is_member = $stmt->get_result()->num_rows > 0;

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM expenses e INNER JOIN expenses_participants ep ON e.expense_id = ep.expense_id WHERE e.group_id = ? AND ep.is_settled = 0 AND (e.user_id = ? OR ep.user_id = ?) AND ep.user_id <> e.user_id");
    $stmt->bind_param("iii", $group_id, $user_id, $user_id);
    $stmt->execute();
    $pending_count = (int) ($stmt->get_result()->fetch_assoc()["total"] ?? 0);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM subscriptions s INNER JOIN subscription_participants sp ON s.subscription_id = sp.subscription_id WHERE s.group_id = ? AND sp.is_settled = 0 AND (s.user_id = ? OR sp.user_id = ?) AND sp.user_id <> s.user_id");
    $stmt->bind_param("iii", $group_id, $user_id, $user_id);
    $stmt->execute();
    $pending_count += (int) ($stmt->get_result()->fetch_assoc()["total"] ?? 0);

    $stmt = $conn->prepare("SELECT COUNT(*) AS total FROM settlements WHERE group_id = ? AND status <> 'paid' AND (paid_by = ? OR paid_to = ?)");
    $stmt->bind_param("iii", $group_id, $user_id, $user_id);
    $stmt->execute();
    $pending_count += (int) ($stmt->get_result()->fetch_assoc()["total"] ?? 0);

    if (!$is_member) {
        $error = "You are not a member of this group.";
    } elseif ($pending_count > 0) {
        $error = "Settle your pending dues in this group before leaving.";
    } else {
        $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        header("Location: details.php");
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete_group" && $group_id > 0) {
    $stmt = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $membership = $stmt->get_result()->fetch_assoc();

    if (!$membership || $membership["role"] !== "Admin") {
        $error = "Only a group admin can delete this group.";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("
                DELETE n FROM notifications n
                INNER JOIN deadlines d ON n.deadline_id = d.deadline_id
                INNER JOIN subscriptions s ON d.subscription_id = s.subscription_id
                WHERE s.group_id = ?
            ");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE d FROM deadlines d INNER JOIN subscriptions s ON d.subscription_id = s.subscription_id WHERE s.group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE sp FROM subscription_participants sp INNER JOIN subscriptions s ON sp.subscription_id = s.subscription_id WHERE s.group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE gu FROM `get` gu INNER JOIN subscriptions s ON gu.subscription_id = s.subscription_id WHERE s.group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM subscriptions WHERE group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("UPDATE payment_method pm INNER JOIN expenses e ON pm.expense_id = e.expense_id SET pm.expense_id = NULL WHERE e.group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE c FROM categories c INNER JOIN expenses e ON c.expense_id = e.expense_id WHERE e.group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE ep FROM expenses_participants ep INNER JOIN expenses e ON ep.expense_id = e.expense_id WHERE e.group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM expenses WHERE group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE isr FROM is_settled isr INNER JOIN settlements s ON isr.settlement_id = s.settlement_id WHERE s.group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM settlements WHERE group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM group_members WHERE group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM groups WHERE group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();

            $conn->commit();
            header("Location: details.php");
            exit();
        } catch (Throwable $exception) {
            $conn->rollback();
            $error = "Could not delete this group. Please try again.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "update_settlement_deadline" && $group_id > 0) {
    $stmt = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $membership = $stmt->get_result()->fetch_assoc();

    if (!$membership || $membership["role"] !== "Admin") {
        $error = "Only a group admin can update the settlement deadline.";
    } else {
        $settlement_deadline = trim($_POST["settlement_deadline"] ?? "");
        $deadline_value = $settlement_deadline !== "" ? $settlement_deadline : null;
        $stmt = $conn->prepare("UPDATE groups SET settlement_deadline = ? WHERE group_id = ?");
        $stmt->bind_param("si", $deadline_value, $group_id);
        $stmt->execute();
        header("Location: details.php?group_id=" . $group_id);
        exit();
    }
}

include "../includes/header.php";

if ($group_id <= 0) {
    $stmt = $conn->prepare("
        SELECT g.group_id, g.group_name, g.description
        FROM groups g
        INNER JOIN group_members gm_me ON g.group_id = gm_me.group_id AND gm_me.user_id = ?
        ORDER BY g.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $groups = $stmt->get_result();
    ?>
    <div class="section-header" style="margin-top:0;">
        <h2>Your Groups</h2>
        <a class="section-link" href="create.php">Create</a>
    </div>
    <div class="card-list">
        <?php if ($groups->num_rows > 0): ?>
            <?php while ($group = $groups->fetch_assoc()): ?>
                <?php
                $card_group_id = (int) $group["group_id"];
                $total_amount = 0;
                $stmt = $conn->prepare("SELECT user_id FROM group_members WHERE group_id = ?");
                $stmt->bind_param("i", $card_group_id);
                $stmt->execute();
                $member_count = $stmt->get_result()->num_rows;
                $stmt = $conn->prepare("SELECT amount FROM expenses WHERE group_id = ?");
                $stmt->bind_param("i", $card_group_id);
                $stmt->execute();
                $amount_rows = $stmt->get_result();
                while ($amount_row = $amount_rows->fetch_assoc()) { $total_amount += (float) $amount_row["amount"]; }
                $stmt = $conn->prepare("SELECT amount FROM subscriptions WHERE group_id = ?");
                $stmt->bind_param("i", $card_group_id);
                $stmt->execute();
                $amount_rows = $stmt->get_result();
                while ($amount_row = $amount_rows->fetch_assoc()) { $total_amount += (float) $amount_row["amount"]; }
                ?>
                <a class="group-card" href="details.php?group_id=<?= (int) $group["group_id"] ?>">
                    <div class="group-card-top">
                        <div>
                            <h3><?= htmlspecialchars($group["group_name"]) ?></h3>
                            <p class="group-meta">ID #<?= (int) $group["group_id"] ?> · <?= (int) $member_count ?> members</p>
                        </div>
                        <span class="badge"><?= money($total_amount) ?></span>
                    </div>
                    <p class="group-meta"><?= htmlspecialchars($group["description"] ?: "No description added") ?></p>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="empty-state"><h3>No groups yet</h3><p>Create or join a group from the plus button.</p></div>
        <?php endif; ?>
    </div>
    <?php include "../includes/footer.php"; exit();
}

$stmt = $conn->prepare("
    SELECT g.* FROM groups g
    INNER JOIN group_members gm ON g.group_id = gm.group_id
    WHERE g.group_id = ? AND gm.user_id = ?
");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();

if (!$group) {
    echo '<div class="empty-state"><h3>Group not found</h3><p>You do not have access to this group.</p></div>';
    include "../includes/footer.php";
    exit();
}

$stmt = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$current_membership = $stmt->get_result()->fetch_assoc();
$is_admin = ($current_membership["role"] ?? "") === "Admin";

$total = 0;
$stmt = $conn->prepare("SELECT amount FROM expenses WHERE group_id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$amount_rows = $stmt->get_result();
while ($amount_row = $amount_rows->fetch_assoc()) { $total += (float) $amount_row["amount"]; }

$stmt = $conn->prepare("SELECT amount FROM subscriptions WHERE group_id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$amount_rows = $stmt->get_result();
while ($amount_row = $amount_rows->fetch_assoc()) { $total += (float) $amount_row["amount"]; }

$stmt = $conn->prepare("
    SELECT u.user_id, u.name, gm.role
    FROM group_members gm
    INNER JOIN users u ON gm.user_id = u.user_id
    WHERE gm.group_id = ?
    ORDER BY gm.role = 'Admin' DESC, u.name
    LIMIT 4
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$members = $stmt->get_result();

$stmt = $conn->prepare("
    SELECT e.expense_id, e.title, e.amount, e.expense_date, u.name AS paid_by
    FROM expenses e
    INNER JOIN users u ON e.user_id = u.user_id
    WHERE e.group_id = ?
    ORDER BY e.created_at DESC
    LIMIT 4
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$expenses = $stmt->get_result();

$stmt = $conn->prepare("
    SELECT s.subscription_id, s.name, s.amount, s.billing_cycle, s.next_due_date, u.name AS paid_by
    FROM subscriptions s
    LEFT JOIN users u ON s.user_id = u.user_id
    WHERE s.group_id = ?
    ORDER BY s.next_due_date IS NULL, s.next_due_date
    LIMIT 4
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$subscriptions = $stmt->get_result();

$balances = get_group_balances($conn, $group_id);
$settlements = [];
foreach ($balances[$user_id] ?? [] as $counterparty_id => $amount) {
    if (abs($amount) > 0.005) {
        $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $counterparty_id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $settlements[] = [
            "counterparty_name" => $user["name"] ?? "Unknown member",
            "net_amount" => $amount
        ];
    }
}
$settlements = array_slice($settlements, 0, 4);
$settlement_deadline = $group["settlement_deadline"] ?? null;
$settlement_deadline_display = $settlement_deadline ? date("M j, Y", strtotime($settlement_deadline)) : "No date set";
?>

<section class="summary-card">
    <p class="summary-label"><?= htmlspecialchars($group["group_name"]) ?></p>
    <div class="amount"><?= money($total) ?></div>
    <p class="summary-subtext">Total group expenses · Group ID #<?= (int) $group_id ?> · Settlement <?= htmlspecialchars($settlement_deadline ?: "anytime") ?></p>
</section>

<?php if ($error): ?><div class="alert error" style="margin-top:14px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<div class="section-header"><h2>Members</h2><a class="section-link" href="members.php?group_id=<?= $group_id ?>">View all</a></div>
<div class="card-list">
    <?php while ($member = $members->fetch_assoc()): ?>
        <article class="user-row">
            <span class="avatar"><?= htmlspecialchars(strtoupper(substr($member["name"], 0, 1))) ?></span>
            <span class="user-body"><h3><?= htmlspecialchars($member["name"]) ?></h3><span class="row-meta"><?= htmlspecialchars($member["role"] ?: "Member") ?></span></span>
            <span class="badge">Active</span>
        </article>
    <?php endwhile; ?>
</div>

<div class="section-header"><h2>Recent Expenses</h2><a class="section-link" href="../expenses/add.php?group_id=<?= $group_id ?>">Add</a></div>
<div class="card-list">
    <?php if ($expenses->num_rows > 0): ?>
        <?php while ($expense = $expenses->fetch_assoc()): ?>
            <a class="transaction-row" href="../expenses/details.php?expense_id=<?= (int) $expense["expense_id"] ?>">
                <span class="icon-circle"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M6 7v12h12V7"/></svg></span>
                <span class="transaction-body"><h3><?= htmlspecialchars($expense["title"]) ?></h3><span class="transaction-meta">Paid by <?= htmlspecialchars($expense["paid_by"]) ?> · <?= htmlspecialchars(date("M j", strtotime($expense["expense_date"]))) ?></span></span>
                <span class="transaction-amount"><?= money($expense["amount"]) ?></span>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state"><h3>No expenses</h3><p>Add the first expense for this group.</p></div>
    <?php endif; ?>
</div>

<div class="section-header"><h2>Group Subscriptions</h2><a class="section-link" href="../subscriptions/add.php?group_id=<?= $group_id ?>">Add</a></div>
<div class="card-list">
    <?php if ($subscriptions->num_rows > 0): ?>
        <?php while ($subscription = $subscriptions->fetch_assoc()): ?>
            <article class="transaction-row">
                <span class="icon-circle"><svg class="inline-icon" viewBox="0 0 24 24"><rect x="4" y="5" width="16" height="14" rx="3"/><path d="M8 9h8M8 13h5"/></svg></span>
                <span class="transaction-body"><h3><?= htmlspecialchars($subscription["name"]) ?></h3><span class="transaction-meta"><?= htmlspecialchars($subscription["billing_cycle"] === "one_time" ? "One Time" : ($subscription["billing_cycle"] ?: "Cycle not set")) ?> · Paid by <?= htmlspecialchars($subscription["paid_by"] ?: "Unknown") ?></span></span>
                <span class="transaction-amount"><?= money($subscription["amount"]) ?></span>
            </article>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state"><h3>No subscriptions</h3><p>Add shared recurring or one-time subscription costs for this group.</p></div>
    <?php endif; ?>
</div>

<div class="section-header"><h2>Settlement Summary</h2><span><a class="section-link" href="pay_due.php?group_id=<?= (int) $group_id ?>">Pay Due</a> · <a class="section-link" href="../settlements/index.php">Open</a></span></div>
<div class="card-list">
    <?php if (count($settlements) > 0): ?>
        <?php foreach ($settlements as $settlement): ?>
            <?php $net_amount = (float) $settlement["net_amount"]; $isOwedByUser = $net_amount > 0; ?>
            <article class="transaction-row">
                <span class="icon-circle"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span class="transaction-body"><h3><?= $isOwedByUser ? "You owe " . htmlspecialchars($settlement["counterparty_name"]) : htmlspecialchars($settlement["counterparty_name"]) . " owes you" ?></h3><span class="transaction-meta">Net remaining balance</span></span>
                <span class="transaction-amount <?= $isOwedByUser ? 'negative' : 'positive' ?>"><?= money(abs($net_amount)) ?></span>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state"><h3>No live dues yet</h3><p>Unpaid expense and subscription shares for this group will appear here.</p></div>
    <?php endif; ?>
</div>

<?php if ($is_admin): ?>
    <div class="section-header"><h2>Settlement Deadline</h2></div>
    <article class="deadline-card">
        <div class="deadline-copy">
            <h3>Settlement Deadline</h3>
            <p><?= htmlspecialchars($settlement_deadline_display) ?></p>
        </div>
        <button class="icon-button deadline-edit-button" type="button" data-open-modal="settlement-deadline-modal" aria-label="Edit settlement deadline">
            <svg class="inline-icon" viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
        </button>
    </article>

    <div class="modal-backdrop" data-modal="settlement-deadline-modal" hidden>
        <section class="modal-card" role="dialog" aria-modal="true" aria-labelledby="settlement-deadline-title">
            <div class="modal-header">
                <h2 id="settlement-deadline-title">Edit Settlement Deadline</h2>
                <button class="icon-button" type="button" data-close-modal aria-label="Close modal">×</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="update_settlement_deadline">
                <div class="form-group">
                    <label for="settlement_deadline">Settlement date</label>
                    <input id="settlement_deadline" name="settlement_deadline" type="date" value="<?= htmlspecialchars($settlement_deadline ?? "") ?>">
                </div>
                <button class="secondary-button full-width" type="submit">SAVE</button>
            </form>
        </section>
    </div>
<?php endif; ?>

<br>
<form method="POST" data-confirm="Leave this group? You will lose access to its details.">
    <input type="hidden" name="action" value="leave_group">
    <button class="danger-button full-width" type="submit">LEAVE GROUP</button>
</form>

<?php if ($is_admin): ?>
    <br>
    <form method="POST" data-confirm="Delete this group and all of its expenses, subscriptions, settlements, and members?">
        <input type="hidden" name="action" value="delete_group">
        <button class="danger-button full-width" type="submit">DELETE GROUP</button>
    </form>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>
