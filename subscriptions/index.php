<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"])) { header("Location: ../login.php"); exit(); }
$user_id = (int) $_SESSION["user_id"];
function money($value) { return "৳" . number_format((float) $value, 2); }

$stmt = $conn->prepare("
    SELECT s.*, pm.method_type, c.category_name, g.group_name, u.name AS paid_by_name,
           sp.share_amount,
           (SELECT COUNT(*) FROM subscription_participants sp_count WHERE sp_count.subscription_id = s.subscription_id) AS participant_count
    FROM subscriptions s
    INNER JOIN `get` gu ON s.subscription_id = gu.subscription_id
    LEFT JOIN payment_method pm ON s.payment_method_id = pm.payment_method_id
    LEFT JOIN categories c ON s.category_id = c.category_id
    LEFT JOIN groups g ON s.group_id = g.group_id
    LEFT JOIN users u ON s.user_id = u.user_id
    LEFT JOIN subscription_participants sp ON s.subscription_id = sp.subscription_id AND sp.user_id = gu.user_id
    WHERE gu.user_id = ?
    ORDER BY s.next_due_date IS NULL, s.next_due_date
");
$stmt->bind_param("i", $user_id); $stmt->execute(); $subscriptions = $stmt->get_result();

$pageTitle = "Subscriptions"; $pageSubtitle = "Recurring shared costs"; $activeNav = "activity"; $basePath = "../"; $showBack = true;
include "../includes/header.php";
?>
<div class="section-header" style="margin-top:0;"><h2>Subscriptions</h2><a class="section-link" href="add.php">Add</a></div>
<div class="card-list">
    <?php if ($subscriptions->num_rows > 0): while ($sub = $subscriptions->fetch_assoc()): ?>
        <a class="group-card" href="details.php?subscription_id=<?= (int) $sub["subscription_id"] ?>">
            <div class="group-card-top"><div><h3><?= htmlspecialchars($sub["name"]) ?></h3><p class="group-meta"><?= htmlspecialchars($sub["billing_cycle"] === "one_time" ? "One Time" : ($sub["billing_cycle"] ?: "cycle not set")) ?> · <?= htmlspecialchars(format_category($sub["category_name"]) ?: "Uncategorized") ?></p></div><span class="badge"><?= money($sub["amount"]) ?></span></div>
            <div class="group-stats"><div class="stat-pill"><span>Group</span><strong><?= htmlspecialchars($sub["group_name"] ?: "Personal") ?></strong></div><div class="stat-pill"><span>Your share</span><strong><?= money($sub["share_amount"] ?? $sub["amount"]) ?></strong></div></div>
            <div class="group-stats"><div class="stat-pill"><span>Next payment</span><strong><?= htmlspecialchars($sub["next_due_date"] ?: "Not set") ?></strong></div><div class="stat-pill"><span>Split</span><strong><?= (int) ($sub["participant_count"] ?? 1) ?> people</strong></div></div>
        </a>
    <?php endwhile; else: ?>
        <div class="empty-state"><h3>No subscriptions</h3><p>Recurring subscription records linked to you will appear here.</p></div>
    <?php endif; ?>
</div>
<?php include "../includes/footer.php"; ?>
