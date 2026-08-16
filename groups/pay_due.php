<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$group_id = (int) ($_GET["group_id"] ?? $_POST["group_id"] ?? 0);
$error = "";

function money($value) { return "৳" . number_format((float) $value, 2); }

function add_balance(&$balances, $payer_id, $receiver_id, $amount) {
    $payer_id = (int) $payer_id;
    $receiver_id = (int) $receiver_id;
    $amount = (float) $amount;

    if ($payer_id <= 0 || $receiver_id <= 0 || $payer_id === $receiver_id || $amount <= 0) {
        return;
    }

    if (!isset($balances[$payer_id])) {
        $balances[$payer_id] = [];
    }
    if (!isset($balances[$receiver_id])) {
        $balances[$receiver_id] = [];
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

function get_group_member_names($conn, $group_id) {
    $names = [];
    $stmt = $conn->prepare("SELECT u.user_id, u.name FROM users u INNER JOIN group_members gm ON u.user_id = gm.user_id WHERE gm.group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $members = $stmt->get_result();
    while ($member = $members->fetch_assoc()) {
        $names[(int) $member["user_id"]] = $member["name"];
    }

    return $names;
}

function create_notification($conn, $user_id, $message) {
    if ($user_id <= 0) {
        return;
    }

    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        return;
    }

    $notification_id = (int) ($conn->query("SELECT COALESCE(MAX(notification_id), 0) + 1 AS next_id FROM notifications")->fetch_assoc()["next_id"] ?? 1);
    $deadline_id = null;
    $stmt = $conn->prepare("INSERT INTO notifications (notification_id, message, is_read, deadline_id, user_id) VALUES (?, ?, 0, ?, ?)");
    $stmt->bind_param("isii", $notification_id, $message, $deadline_id, $user_id);
    $stmt->execute();
}

$stmt = $conn->prepare("
    SELECT g.*, gm.role
    FROM groups g
    INNER JOIN group_members gm ON g.group_id = gm.group_id
    WHERE g.group_id = ? AND gm.user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$group = $stmt->get_result()->fetch_assoc();

$pageTitle = "Pay Due";
$pageSubtitle = "Settle group balances";
$activeNav = "groups";
$basePath = "../";
$showBack = true;
$backHref = "details.php?group_id=" . $group_id;

if (!$group) {
    include "../includes/header.php";
    echo '<div class="empty-state"><h3>Group not found</h3><p>You do not have access to this group.</p></div>';
    include "../includes/footer.php";
    exit();
}

$is_admin = ($group["role"] ?? "") === "Admin";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    if ($action === "request_settlement" && $is_admin) {
        $today = date("Y-m-d");
        $stmt = $conn->prepare("UPDATE groups SET settlement_deadline = ? WHERE group_id = ?");
        $stmt->bind_param("si", $today, $group_id);
        $stmt->execute();
        $group["settlement_deadline"] = $today;

        $balances = get_group_balances($conn, $group_id);
        $member_names = get_group_member_names($conn, $group_id);
        foreach ($balances as $member_id => $dues) {
            foreach ($dues as $counterparty_id => $amount) {
                if ($amount > 0.005) {
                    create_notification($conn, $member_id, "Settlement requested for " . $group["group_name"] . ": pay " . money($amount) . " to " . ($member_names[$counterparty_id] ?? "another member") . ".");
                }
            }
        }
    } elseif ($action === "pay_due") {
        $counterparty_id = (int) ($_POST["counterparty_id"] ?? 0);
        $deadline = $group["settlement_deadline"] ?? null;
        $can_pay = $deadline === null || $deadline === "" || $deadline <= date("Y-m-d");
        $balances = get_group_balances($conn, $group_id);
        $member_names = get_group_member_names($conn, $group_id);
        $net_amount = (float) ($balances[$user_id][$counterparty_id] ?? 0);

        if (!$can_pay) {
            $error = "This group can settle on or after " . $deadline . ".";
        } elseif ($counterparty_id <= 0 || $net_amount <= 0) {
            $error = "No payable due was found for that member.";
        } else {
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("
                    UPDATE expenses_participants ep
                    INNER JOIN expenses e ON ep.expense_id = e.expense_id
                    SET ep.is_settled = 1
                    WHERE e.group_id = ? AND ep.is_settled = 0 AND ((ep.user_id = ? AND e.user_id = ?) OR (ep.user_id = ? AND e.user_id = ?))
                ");
                $stmt->bind_param("iiiii", $group_id, $user_id, $counterparty_id, $counterparty_id, $user_id);
                $stmt->execute();

                $stmt = $conn->prepare("
                    UPDATE subscription_participants sp
                    INNER JOIN subscriptions s ON sp.subscription_id = s.subscription_id
                    SET sp.is_settled = 1
                    WHERE s.group_id = ? AND sp.is_settled = 0 AND ((sp.user_id = ? AND s.user_id = ?) OR (sp.user_id = ? AND s.user_id = ?))
                ");
                $stmt->bind_param("iiiii", $group_id, $user_id, $counterparty_id, $counterparty_id, $user_id);
                $stmt->execute();

                $stmt = $conn->prepare("
                    UPDATE settlements
                    SET status = 'paid'
                    WHERE group_id = ? AND status <> 'paid' AND ((paid_by = ? AND paid_to = ?) OR (paid_by = ? AND paid_to = ?))
                ");
                $stmt->bind_param("iiiii", $group_id, $user_id, $counterparty_id, $counterparty_id, $user_id);
                $stmt->execute();

                create_notification($conn, $counterparty_id, ($member_names[$user_id] ?? "Someone") . " paid you " . money($net_amount) . " for " . $group["group_name"] . ".");
                $conn->commit();
                header("Location: pay_due.php?group_id=" . $group_id);
                exit();
            } catch (Throwable $exception) {
                $conn->rollback();
                $error = "Could not pay this due. Please try again.";
            }
        }
    }
}

$balances = get_group_balances($conn, $group_id);
$member_names = get_group_member_names($conn, $group_id);
$my_dues = [];
$all_dues = [];

foreach ($balances[$user_id] ?? [] as $counterparty_id => $amount) {
    if (abs($amount) > 0.005) {
        $my_dues[] = [
            "counterparty_id" => $counterparty_id,
            "counterparty_name" => $member_names[$counterparty_id] ?? "Unknown member",
            "net_amount" => $amount
        ];
    }
}

foreach ($balances as $member_id => $dues) {
    foreach ($dues as $counterparty_id => $amount) {
        if ($amount > 0.005) {
            $all_dues[] = [
                "member_name" => $member_names[$member_id] ?? "Unknown member",
                "counterparty_name" => $member_names[$counterparty_id] ?? "Unknown member",
                "net_amount" => $amount
            ];
        }
    }
}

$deadline = $group["settlement_deadline"] ?? null;
$can_pay = $deadline === null || $deadline === "" || $deadline <= date("Y-m-d");

include "../includes/header.php";
?>

<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<section class="summary-card">
    <p class="summary-label"><?= htmlspecialchars($group["group_name"]) ?></p>
    <div class="amount" style="font-size:28px;">Settlement <?= htmlspecialchars($deadline ?: "anytime") ?></div>
    <p class="summary-subtext"><?= $can_pay ? "Members can pay dues now." : "Payment opens on " . htmlspecialchars($deadline) . "." ?></p>
</section>

<?php if ($is_admin): ?>
    <br>
    <form method="POST">
        <input type="hidden" name="group_id" value="<?= (int) $group_id ?>">
        <input type="hidden" name="action" value="request_settlement">
        <button class="primary-button full-width" type="submit"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg> ASK FOR SETTLEMENT NOW</button>
    </form>
<?php endif; ?>

<div class="section-header"><h2>Your Dues</h2></div>
<div class="card-list">
    <?php if (count($my_dues) > 0): ?>
        <?php foreach ($my_dues as $due): ?>
            <?php $net_amount = (float) $due["net_amount"]; $is_owe = $net_amount > 0; ?>
            <article class="transaction-row">
                <span class="icon-circle"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span class="transaction-body"><h3><?= $is_owe ? "You owe " . htmlspecialchars($due["counterparty_name"]) : htmlspecialchars($due["counterparty_name"]) . " owes you" ?></h3><span class="transaction-meta">Net amount after offsetting opposite dues</span></span>
                <span class="transaction-amount <?= $is_owe ? 'negative' : 'positive' ?>"><?= money(abs($net_amount)) ?></span>
            </article>
            <?php if ($is_owe): ?>
                <?php if ($can_pay): ?>
                    <form method="POST"><input type="hidden" name="group_id" value="<?= (int) $group_id ?>"><input type="hidden" name="action" value="pay_due"><input type="hidden" name="counterparty_id" value="<?= (int) $due["counterparty_id"] ?>"><button class="secondary-button full-width" type="submit"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg> PAY DUE</button></form>
                <?php else: ?>
                    <button class="secondary-button full-width" type="button" disabled>PAY DUE AFTER <?= htmlspecialchars($deadline) ?></button>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state"><h3>No dues</h3><p>You have no net dues in this group.</p></div>
    <?php endif; ?>
</div>

<div class="section-header"><h2>Group Due Map</h2></div>
<div class="card-list">
    <?php if (count($all_dues) > 0): ?>
        <?php foreach ($all_dues as $due): ?>
            <article class="transaction-row">
                <span class="icon-circle"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg></span>
                <span class="transaction-body"><h3><?= htmlspecialchars($due["member_name"]) ?> owes <?= htmlspecialchars($due["counterparty_name"]) ?></h3><span class="transaction-meta">Net remaining due</span></span>
                <span class="transaction-amount negative"><?= money($due["net_amount"]) ?></span>
            </article>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="empty-state"><h3>No group dues</h3><p>All group balances are settled.</p></div>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?>
