<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"])) { header("Location: ../login.php"); exit(); }
$user_id = (int) $_SESSION["user_id"];

function add_notification($conn, $user_id, $message, $deadline_id = null) {
    $stmt = $conn->prepare("SELECT notification_id FROM notifications WHERE user_id = ? AND message = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $message);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) { return; }

    $notification_id = (int) ($conn->query("SELECT COALESCE(MAX(notification_id), 0) + 1 AS next_id FROM notifications")->fetch_assoc()["next_id"] ?? 1);
    $stmt = $conn->prepare("INSERT INTO notifications (notification_id, message, is_read, deadline_id, user_id) VALUES (?, ?, 0, ?, ?)");
    $stmt->bind_param("isii", $notification_id, $message, $deadline_id, $user_id);
    $stmt->execute();
}

function notification_action_page($notification) {
    if (!empty($notification["deadline_id"])) {
        return "../deadlines/index.php";
    }

    $message = strtolower($notification["message"] ?? "");
    if (strpos($message, "settlement") !== false || strpos($message, "you owe") !== false || strpos($message, "paid you") !== false) {
        return "../settlements/index.php";
    }

    return "../dashboard.php";
}

if (isset($_GET["open"])) {
    $notification_id = (int) $_GET["open"];
    $stmt = $conn->prepare("SELECT * FROM notifications WHERE notification_id = ? AND user_id = ? LIMIT 1");
    $stmt->bind_param("ii", $notification_id, $user_id);
    $stmt->execute();
    $notification = $stmt->get_result()->fetch_assoc();

    if ($notification) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();

        header("Location: " . notification_action_page($notification));
        exit();
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $notification_id = (int) ($_POST["notification_id"] ?? 0);
    if ($notification_id > 0) {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $notification_id, $user_id);
        $stmt->execute();
    } elseif (($_POST["action"] ?? "") === "mark_all_read") {
        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
}

$stmt = $conn->prepare("
    SELECT d.deadline_id, d.due_date, s.name, s.amount
    FROM deadlines d
    INNER JOIN subscriptions s ON d.subscription_id = s.subscription_id
    INNER JOIN `get` gu ON s.subscription_id = gu.subscription_id
    WHERE gu.user_id = ? AND d.status <> 'paid' AND d.due_date <= DATE_ADD(CURRENT_DATE(), INTERVAL 3 DAY)
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$due_deadlines = $stmt->get_result();
while ($deadline = $due_deadlines->fetch_assoc()) {
    $days = floor((strtotime($deadline["due_date"]) - strtotime(date("Y-m-d"))) / 86400);
    $when = $days < 0 ? abs($days) . " days overdue" : ($days === 0 ? "due today" : "due in " . $days . " days");
    add_notification($conn, $user_id, $deadline["name"] . " is " . $when . ".", (int) $deadline["deadline_id"]);
}

$stmt = $conn->prepare("SELECT payer.name AS paid_by_name, e.title, ep.share_amount FROM expenses_participants ep INNER JOIN expenses e ON ep.expense_id = e.expense_id INNER JOIN users payer ON e.user_id = payer.user_id WHERE ep.user_id = ? AND e.user_id <> ? AND ep.is_settled = 0 LIMIT 10");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$dues = $stmt->get_result();
while ($due = $dues->fetch_assoc()) {
    add_notification($conn, $user_id, "You owe " . $due["paid_by_name"] . " ৳" . number_format((float) $due["share_amount"], 2) . " for " . $due["title"] . ".");
}

$stmt = $conn->prepare("SELECT payer.name AS paid_by_name, s.name AS title, sp.share_amount FROM subscription_participants sp INNER JOIN subscriptions s ON sp.subscription_id = s.subscription_id INNER JOIN users payer ON s.user_id = payer.user_id WHERE sp.user_id = ? AND s.user_id <> ? AND sp.is_settled = 0 LIMIT 10");
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$dues = $stmt->get_result();
while ($due = $dues->fetch_assoc()) {
    add_notification($conn, $user_id, "You owe " . $due["paid_by_name"] . " ৳" . number_format((float) $due["share_amount"], 2) . " for " . $due["title"] . ".");
}

$stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY is_read, sent_at DESC");
$stmt->bind_param("i", $user_id); $stmt->execute(); $notifications = $stmt->get_result();
$pageTitle = "Notifications"; $pageSubtitle = "Reminders and updates"; $activeNav = "profile"; $basePath = "../"; $showBack = true;
include "../includes/header.php";
?>
<div class="section-header" style="margin-top:0;"><h2>Notifications</h2><form method="POST"><input type="hidden" name="action" value="mark_all_read"><button class="section-link" type="submit" style="border:0;background:transparent;">Mark all read</button></form></div>
<div class="card-list">
    <?php if ($notifications->num_rows > 0): while ($n = $notifications->fetch_assoc()): ?>
        <a class="notification-card" href="index.php?open=<?= (int) $n["notification_id"] ?>">
            <div class="row-top"><h3><?= $n["is_read"] ? "Update" : "New reminder" ?></h3><span class="badge <?= $n["is_read"] ? 'success' : '' ?>"><?= $n["is_read"] ? "Read" : "Unread" ?></span></div>
            <p class="group-meta" style="margin-top:10px;"><?= htmlspecialchars($n["message"]) ?></p>
            <p class="transaction-meta"><?= htmlspecialchars(date("d M Y, h:i A", strtotime($n["sent_at"]))) ?></p>
            <p class="transaction-meta">Tap to open</p>
        </a>
    <?php endwhile; else: ?>
        <div class="empty-state"><h3>No notifications</h3><p>Settlement and subscription reminders will appear here.</p></div>
    <?php endif; ?>
</div>
<?php include "../includes/footer.php"; ?>
