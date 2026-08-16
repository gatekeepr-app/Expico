<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
function money($value) { return "৳" . number_format((float) $value, 2); }

function add_due(&$dues, $group_id, $counterparty_id, $amount) {
    $group_id = (int) $group_id;
    $counterparty_id = (int) $counterparty_id;
    $amount = (float) $amount;

    if ($group_id <= 0 || $counterparty_id <= 0 || $amount == 0) {
        return;
    }

    if (!isset($dues[$group_id])) {
        $dues[$group_id] = [];
    }

    $dues[$group_id][$counterparty_id] = ($dues[$group_id][$counterparty_id] ?? 0) + $amount;
}

function get_net_dues($conn, $user_id) {
    $dues = [];

    $stmt = $conn->prepare("SELECT e.group_id, e.user_id AS paid_by, ep.user_id AS participant_id, ep.share_amount FROM expenses e INNER JOIN expenses_participants ep ON e.expense_id = ep.expense_id WHERE ep.is_settled = 0 AND ep.user_id <> e.user_id AND (ep.user_id = ? OR e.user_id = ?)");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        if ((int) $row["participant_id"] === $user_id) {
            add_due($dues, $row["group_id"], $row["paid_by"], $row["share_amount"]);
        } else {
            add_due($dues, $row["group_id"], $row["participant_id"], 0 - (float) $row["share_amount"]);
        }
    }

    $stmt = $conn->prepare("SELECT s.group_id, s.user_id AS paid_by, sp.user_id AS participant_id, sp.share_amount FROM subscriptions s INNER JOIN subscription_participants sp ON s.subscription_id = sp.subscription_id WHERE sp.is_settled = 0 AND s.user_id IS NOT NULL AND sp.user_id <> s.user_id AND (sp.user_id = ? OR s.user_id = ?)");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        if ((int) $row["participant_id"] === $user_id) {
            add_due($dues, $row["group_id"], $row["paid_by"], $row["share_amount"]);
        } else {
            add_due($dues, $row["group_id"], $row["participant_id"], 0 - (float) $row["share_amount"]);
        }
    }

    $stmt = $conn->prepare("SELECT group_id, paid_by, paid_to, amount FROM settlements WHERE status <> 'paid' AND (paid_by = ? OR paid_to = ?)");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();
    $rows = $stmt->get_result();
    while ($row = $rows->fetch_assoc()) {
        if ((int) $row["paid_by"] === $user_id) {
            add_due($dues, $row["group_id"], $row["paid_to"], $row["amount"]);
        } else {
            add_due($dues, $row["group_id"], $row["paid_by"], 0 - (float) $row["amount"]);
        }
    }

    return $dues;
}

function notify_payment_received($conn, $receiver_id, $message) {
    $notification_id = (int) ($conn->query("SELECT COALESCE(MAX(notification_id), 0) + 1 AS next_id FROM notifications")->fetch_assoc()["next_id"] ?? 1);
    $deadline_id = null;
    $stmt = $conn->prepare("INSERT INTO notifications (notification_id, message, is_read, deadline_id, user_id) VALUES (?, ?, 0, ?, ?)");
    $stmt->bind_param("isii", $notification_id, $message, $deadline_id, $receiver_id);
    $stmt->execute();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $type = $_POST["type"] ?? "";
    $item_id = (int) ($_POST["item_id"] ?? 0);

    if ($type === "net") {
        $group_id = (int) ($_POST["group_id"] ?? 0);
        $counterparty_id = (int) ($_POST["counterparty_id"] ?? 0);

        $all_dues = get_net_dues($conn, $user_id);
        $net_amount = (float) ($all_dues[$group_id][$counterparty_id] ?? 0);

        $stmt = $conn->prepare("SELECT group_name, settlement_deadline FROM groups WHERE group_id = ? LIMIT 1");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $group_info = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $payer = $stmt->get_result()->fetch_assoc();

        $deadline = $group_info["settlement_deadline"] ?? null;
        $can_pay = $deadline === null || $deadline === "" || $deadline <= date("Y-m-d");

        if ($group_id > 0 && $counterparty_id > 0 && $net_amount > 0 && $can_pay) {
            $stmt = $conn->prepare("
                UPDATE expenses_participants ep
                INNER JOIN expenses e ON ep.expense_id = e.expense_id
                SET ep.is_settled = 1
                WHERE e.group_id = ? AND ep.is_settled = 0
                  AND ((ep.user_id = ? AND e.user_id = ?) OR (ep.user_id = ? AND e.user_id = ?))
            ");
            $stmt->bind_param("iiiii", $group_id, $user_id, $counterparty_id, $counterparty_id, $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("
                UPDATE subscription_participants sp
                INNER JOIN subscriptions s ON sp.subscription_id = s.subscription_id
                SET sp.is_settled = 1
                WHERE s.group_id = ? AND sp.is_settled = 0
                  AND ((sp.user_id = ? AND s.user_id = ?) OR (sp.user_id = ? AND s.user_id = ?))
            ");
            $stmt->bind_param("iiiii", $group_id, $user_id, $counterparty_id, $counterparty_id, $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("
                UPDATE settlements
                SET status = 'paid'
                WHERE group_id = ? AND status <> 'paid'
                  AND ((paid_by = ? AND paid_to = ?) OR (paid_by = ? AND paid_to = ?))
            ");
            $stmt->bind_param("iiiii", $group_id, $user_id, $counterparty_id, $counterparty_id, $user_id);
            $stmt->execute();

            notify_payment_received($conn, $counterparty_id, ($payer["name"] ?? "Someone") . " paid you " . money($net_amount) . " for " . ($group_info["group_name"] ?? "a group") . ".");
        }
    } elseif ($type === "expense") {
        $stmt = $conn->prepare("
            SELECT e.user_id AS receiver_id, e.title, ep.share_amount, u.name AS payer_name
            FROM expenses_participants ep
            INNER JOIN expenses e ON ep.expense_id = e.expense_id
            INNER JOIN users u ON ep.user_id = u.user_id
            WHERE ep.expense_id = ? AND ep.user_id = ? AND e.user_id <> ? AND ep.is_settled = 0
            LIMIT 1
        ");
        $stmt->bind_param("iii", $item_id, $user_id, $user_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("
            UPDATE expenses_participants ep
            INNER JOIN expenses e ON ep.expense_id = e.expense_id
            SET ep.is_settled = 1
            WHERE ep.expense_id = ? AND ep.user_id = ? AND e.user_id <> ?
        ");
        $stmt->bind_param("iii", $item_id, $user_id, $user_id);
        $stmt->execute();
        if ($payment && $stmt->affected_rows > 0) {
            notify_payment_received($conn, (int) $payment["receiver_id"], $payment["payer_name"] . " paid you " . money($payment["share_amount"]) . " for " . $payment["title"] . ".");
        }
    } elseif ($type === "subscription") {
        $stmt = $conn->prepare("
            SELECT s.user_id AS receiver_id, s.name, sp.share_amount, u.name AS payer_name
            FROM subscription_participants sp
            INNER JOIN subscriptions s ON sp.subscription_id = s.subscription_id
            INNER JOIN users u ON sp.user_id = u.user_id
            WHERE sp.subscription_id = ? AND sp.user_id = ? AND s.user_id <> ? AND sp.is_settled = 0
            LIMIT 1
        ");
        $stmt->bind_param("iii", $item_id, $user_id, $user_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();

        $stmt = $conn->prepare("
            UPDATE subscription_participants sp
            INNER JOIN subscriptions s ON sp.subscription_id = s.subscription_id
            SET sp.is_settled = 1
            WHERE sp.subscription_id = ? AND sp.user_id = ? AND s.user_id <> ?
        ");
        $stmt->bind_param("iii", $item_id, $user_id, $user_id);
        $stmt->execute();
        if ($payment && $stmt->affected_rows > 0) {
            notify_payment_received($conn, (int) $payment["receiver_id"], $payment["payer_name"] . " paid you " . money($payment["share_amount"]) . " for " . $payment["name"] . ".");
        }
    } elseif ($type === "legacy") {
        $stmt = $conn->prepare("
            SELECT s.paid_to AS receiver_id, s.amount, s.group_id, g.group_name, g.settlement_deadline, u.name AS payer_name
            FROM settlements s
            INNER JOIN groups g ON s.group_id = g.group_id
            INNER JOIN users u ON s.paid_by = u.user_id
            WHERE s.settlement_id = ? AND s.paid_by = ? AND s.status <> 'paid'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $item_id, $user_id);
        $stmt->execute();
        $payment = $stmt->get_result()->fetch_assoc();

        $deadline = $payment["settlement_deadline"] ?? null;
        $can_pay = $payment && ($deadline === null || $deadline === "" || $deadline <= date("Y-m-d"));
        if ($can_pay) {
            $stmt = $conn->prepare("UPDATE settlements SET status = 'paid' WHERE settlement_id = ? AND paid_by = ?");
            $stmt->bind_param("ii", $item_id, $user_id);
            $stmt->execute();
            if ($payment && $stmt->affected_rows > 0) {
                notify_payment_received($conn, (int) $payment["receiver_id"], $payment["payer_name"] . " paid you " . money($payment["amount"]) . " for " . $payment["group_name"] . ".");
            }
        }
    }
}

$you_owe = 0;
$you_are_owed = 0;
$net_dues = [];
$all_dues = get_net_dues($conn, $user_id);
foreach ($all_dues as $group_id => $group_dues) {
    $stmt = $conn->prepare("SELECT group_name, settlement_deadline FROM groups WHERE group_id = ? LIMIT 1");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $group = $stmt->get_result()->fetch_assoc();

    foreach ($group_dues as $counterparty_id => $net_amount) {
        if (abs($net_amount) <= 0.005) {
            continue;
        }

        $stmt = $conn->prepare("SELECT name FROM users WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $counterparty_id);
        $stmt->execute();
        $counterparty = $stmt->get_result()->fetch_assoc();

        if ($net_amount > 0) { $you_owe += $net_amount; }
        else { $you_are_owed += abs($net_amount); }

        $net_dues[] = [
            "group_id" => $group_id,
            "group_name" => $group["group_name"] ?? "Group",
            "settlement_deadline" => $group["settlement_deadline"] ?? null,
            "counterparty_id" => $counterparty_id,
            "counterparty_name" => $counterparty["name"] ?? "Unknown member",
            "net_amount" => $net_amount
        ];
    }
}

$stmt = $conn->prepare("
    SELECT s.settlement_id, s.amount, s.settlement_date, s.status, s.group_id, g.group_name, g.settlement_deadline, pb.name AS paid_by_name, pt.name AS paid_to_name, s.paid_by, s.paid_to
    FROM settlements s
    INNER JOIN groups g ON s.group_id = g.group_id
    INNER JOIN users pb ON s.paid_by = pb.user_id
    INNER JOIN users pt ON s.paid_to = pt.user_id
    WHERE (s.paid_by = ? OR s.paid_to = ?) AND s.status <> 'paid'
    ORDER BY s.settlement_date DESC
");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$legacy_settlements = $stmt->get_result();

$pageTitle = "Settlements";
$pageSubtitle = "Live dues from splits";
$activeNav = "activity";
$basePath = "../";
$showBack = true;
include "../includes/header.php";
?>

<section class="mini-grid" style="margin-top:0;">
    <article class="mini-card"><p class="section-kicker">You Owe</p><div class="amount negative"><?= money($you_owe) ?></div></article>
    <article class="mini-card"><p class="section-kicker">You Are Owed</p><div class="amount positive"><?= money($you_are_owed) ?></div></article>
</section>

<div class="section-header"><h2>Live Split Dues</h2></div>
<div class="card-list">
    <?php if (count($net_dues) > 0): ?>
        <?php foreach ($net_dues as $due): ?>
            <?php $net_amount = (float) $due["net_amount"]; $is_owe = $net_amount > 0; $deadline = $due["settlement_deadline"] ?? null; $can_pay = $deadline === null || $deadline === "" || $deadline <= date("Y-m-d"); ?>
            <article class="transaction-row">
                <span class="icon-circle"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span class="transaction-body"><h3><?= $is_owe ? "You owe " . htmlspecialchars($due["counterparty_name"]) : htmlspecialchars($due["counterparty_name"]) . " owes you" ?></h3><span class="transaction-meta">Net balance · <?= htmlspecialchars($due["group_name"]) ?> · Settlement <?= htmlspecialchars($deadline ?: "anytime") ?></span></span>
                <span class="transaction-amount <?= $is_owe ? 'negative' : 'positive' ?>"><?= money(abs($net_amount)) ?></span>
            </article>
            <?php if ($is_owe): ?>
                <?php if ($can_pay): ?>
                    <form method="POST"><input type="hidden" name="type" value="net"><input type="hidden" name="group_id" value="<?= (int) $due["group_id"] ?>"><input type="hidden" name="counterparty_id" value="<?= (int) $due["counterparty_id"] ?>"><button class="secondary-button full-width" type="submit"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg> PAY DUE</button></form>
                <?php else: ?>
                    <button class="secondary-button full-width" type="button" disabled>PAY DUE AFTER <?= htmlspecialchars($deadline) ?></button>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state"><h3>No live dues</h3><p>Unsettled expense and subscription shares will appear here.</p></div>
    <?php endif; ?>
</div>

<?php if ($legacy_settlements->num_rows > 0): ?>
    <div class="section-header"><h2>Manual Settlements</h2></div>
    <div class="card-list">
        <?php while ($settlement = $legacy_settlements->fetch_assoc()): ?>
            <?php $is_owe = (int) $settlement["paid_by"] === $user_id; $deadline = $settlement["settlement_deadline"] ?? null; $can_pay = $deadline === null || $deadline === "" || $deadline <= date("Y-m-d"); ?>
            <article class="transaction-row">
                <span class="icon-circle"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span class="transaction-body"><h3><?= $is_owe ? "You owe " . htmlspecialchars($settlement["paid_to_name"]) : htmlspecialchars($settlement["paid_by_name"]) . " owes you" ?></h3><span class="transaction-meta"><?= htmlspecialchars($settlement["group_name"]) ?> · <?= htmlspecialchars($settlement["status"]) ?></span></span>
                <span class="transaction-amount <?= $is_owe ? 'negative' : 'positive' ?>"><?= money($settlement["amount"]) ?></span>
            </article>
            <?php if ($is_owe): ?>
                <?php if ($can_pay): ?>
                    <form method="POST"><input type="hidden" name="type" value="legacy"><input type="hidden" name="item_id" value="<?= (int) $settlement["settlement_id"] ?>"><button class="secondary-button full-width" type="submit"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg> PAY DUE</button></form>
                <?php else: ?>
                    <button class="secondary-button full-width" type="button" disabled>PAY DUE AFTER <?= htmlspecialchars($deadline) ?></button>
                <?php endif; ?>
            <?php endif; ?>
        <?php endwhile; ?>
    </div>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>
