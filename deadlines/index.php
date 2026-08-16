<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"])) { header("Location: ../login.php"); exit(); }
$user_id = (int) $_SESSION["user_id"];
function money($value) { return "৳" . number_format((float) $value, 2); }

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $deadline_id = (int) ($_POST["deadline_id"] ?? 0);
    $stmt = $conn->prepare("
        SELECT d.deadline_id, d.due_date, d.subscription_id, s.billing_cycle
        FROM deadlines d
        INNER JOIN subscriptions s ON d.subscription_id = s.subscription_id
        INNER JOIN `get` gu ON s.subscription_id = gu.subscription_id
        WHERE d.deadline_id = ? AND gu.user_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("ii", $deadline_id, $user_id);
    $stmt->execute();
    $deadline = $stmt->get_result()->fetch_assoc();

    if ($deadline) {
        $cycle = $deadline["billing_cycle"] ?? "";
        $intervals = ["weekly" => "+1 week", "monthly" => "+1 month", "yearly" => "+1 year"];
        if (isset($intervals[$cycle])) {
            $next_due_date = date("Y-m-d", strtotime($intervals[$cycle], strtotime($deadline["due_date"])));
            $stmt = $conn->prepare("UPDATE deadlines SET due_date = ?, status = 'upcoming' WHERE deadline_id = ?");
            $stmt->bind_param("si", $next_due_date, $deadline_id);
            $stmt->execute();
            $stmt = $conn->prepare("UPDATE subscriptions SET next_due_date = ? WHERE subscription_id = ?");
            $stmt->bind_param("si", $next_due_date, $deadline["subscription_id"]);
            $stmt->execute();
        } else {
            $stmt = $conn->prepare("UPDATE deadlines SET status = 'paid' WHERE deadline_id = ?");
            $stmt->bind_param("i", $deadline_id);
            $stmt->execute();
        }

        $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE deadline_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $deadline_id, $user_id);
        $stmt->execute();
    }
}

$stmt = $conn->prepare("
    SELECT d.*, s.name, s.amount, s.billing_cycle
    FROM deadlines d
    INNER JOIN subscriptions s ON d.subscription_id = s.subscription_id
    INNER JOIN `get` gu ON s.subscription_id = gu.subscription_id
    WHERE gu.user_id = ? AND d.status <> 'paid'
    ORDER BY d.due_date
");
$stmt->bind_param("i", $user_id); $stmt->execute(); $deadlines = $stmt->get_result();

$pageTitle = "Deadlines"; $pageSubtitle = "Upcoming payment dates"; $activeNav = "activity"; $basePath = "../"; $showBack = true;
include "../includes/header.php";
?>
<div class="section-header" style="margin-top:0;"><h2>Upcoming Deadlines</h2></div>
<div class="card-list">
    <?php if ($deadlines->num_rows > 0): while ($d = $deadlines->fetch_assoc()): ?>
        <?php $days = floor((strtotime($d["due_date"]) - strtotime(date("Y-m-d"))) / 86400); $badge = $days < 0 ? "danger" : ($days <= 3 ? "warning" : ""); ?>
        <article class="date-card">
            <div class="date-block"><?= date("d", strtotime($d["due_date"])) ?><span><?= strtoupper(date("M", strtotime($d["due_date"]))) ?></span></div>
            <div class="transaction-body"><h3><?= htmlspecialchars($d["name"]) ?></h3><p class="transaction-meta"><?= money($d["amount"]) ?> · <?= $days < 0 ? abs($days) . " days overdue" : "Due in " . $days . " days" ?></p></div>
            <span class="badge <?= $badge ?>"><?= htmlspecialchars($d["status"] ?: "upcoming") ?></span>
        </article>
        <form method="POST"><input type="hidden" name="deadline_id" value="<?= (int) $d["deadline_id"] ?>"><button class="secondary-button full-width" type="submit"><?= in_array($d["billing_cycle"], ["weekly", "monthly", "yearly"], true) ? "ADVANCE DUE DATE" : "MARK PAID" ?></button></form>
    <?php endwhile; else: ?>
        <div class="empty-state"><h3>No deadlines</h3><p>Subscription deadlines linked to your account will appear here.</p></div>
    <?php endif; ?>
</div>
<?php include "../includes/footer.php"; ?>
