<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"])) { header("Location: ../login.php"); exit(); }
$user_id = (int) $_SESSION["user_id"];
$subscription_id = (int) ($_GET["subscription_id"] ?? $_POST["subscription_id"] ?? 0);
function money($value) { return "৳" . number_format((float) $value, 2); }

$stmt = $conn->prepare("
    SELECT s.*, pm.method_type, c.category_name, g.group_name, u.name AS paid_by_name
    FROM subscriptions s
    INNER JOIN `get` gu ON s.subscription_id = gu.subscription_id
    LEFT JOIN payment_method pm ON s.payment_method_id = pm.payment_method_id
    LEFT JOIN categories c ON s.category_id = c.category_id
    LEFT JOIN groups g ON s.group_id = g.group_id
    LEFT JOIN users u ON s.user_id = u.user_id
    WHERE s.subscription_id = ? AND gu.user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $subscription_id, $user_id);
$stmt->execute();
$subscription = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete" && $subscription && (int) $subscription["user_id"] === $user_id) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE n FROM notifications n INNER JOIN deadlines d ON n.deadline_id = d.deadline_id WHERE d.subscription_id = ?");
        $stmt->bind_param("i", $subscription_id); $stmt->execute();
        $stmt = $conn->prepare("DELETE FROM deadlines WHERE subscription_id = ?");
        $stmt->bind_param("i", $subscription_id); $stmt->execute();
        $stmt = $conn->prepare("DELETE FROM subscription_participants WHERE subscription_id = ?");
        $stmt->bind_param("i", $subscription_id); $stmt->execute();
        $stmt = $conn->prepare("DELETE FROM `get` WHERE subscription_id = ?");
        $stmt->bind_param("i", $subscription_id); $stmt->execute();
        $category_id = (int) ($subscription["category_id"] ?? 0);
        $stmt = $conn->prepare("DELETE FROM subscriptions WHERE subscription_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $subscription_id, $user_id); $stmt->execute();
        if ($category_id > 0) {
            $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ? AND expense_id IS NULL");
            $stmt->bind_param("i", $category_id); $stmt->execute();
        }
        $conn->commit();
        header("Location: index.php");
        exit();
    } catch (Throwable $exception) {
        $conn->rollback();
    }
}

$pageTitle = "Subscription Details"; $pageSubtitle = "Split and billing state"; $activeNav = "activity"; $basePath = "../"; $showBack = true;
include "../includes/header.php";
if (!$subscription) {
    echo '<div class="empty-state"><h3>Subscription not found</h3><p>You do not have access to this subscription.</p></div>';
    include "../includes/footer.php"; exit();
}

$stmt = $conn->prepare("
    SELECT u.name, sp.share_amount, sp.is_settled
    FROM subscription_participants sp
    INNER JOIN users u ON sp.user_id = u.user_id
    WHERE sp.subscription_id = ?
    ORDER BY u.name
");
$stmt->bind_param("i", $subscription_id); $stmt->execute(); $participants = $stmt->get_result();
?>
<section class="summary-card">
    <p class="summary-label"><?= htmlspecialchars($subscription["name"]) ?></p>
    <div class="amount"><?= money($subscription["amount"]) ?></div>
    <p class="summary-subtext"><?= htmlspecialchars($subscription["group_name"] ?: "Personal") ?> · <?= htmlspecialchars($subscription["billing_cycle"] === "one_time" ? "One Time" : ($subscription["billing_cycle"] ?: "Cycle not set")) ?></p>
</section>
<div class="mini-grid">
    <article class="mini-card"><p class="section-kicker">Paid by</p><div class="amount" style="font-size:20px;"><?= htmlspecialchars($subscription["paid_by_name"] ?: "Unknown") ?></div></article>
    <article class="mini-card"><p class="section-kicker">Next Due</p><div class="amount" style="font-size:20px;"><?= htmlspecialchars($subscription["next_due_date"] ?: "Not set") ?></div></article>
</div>
<div class="section-header"><h2>Participants</h2><?php if ((int) $subscription["user_id"] === $user_id): ?><a class="section-link" href="edit.php?subscription_id=<?= (int) $subscription_id ?>">Edit</a><?php endif; ?></div>
<div class="card-list">
    <?php while ($participant = $participants->fetch_assoc()): ?>
        <article class="user-row"><span class="avatar"><?= htmlspecialchars(strtoupper(substr($participant["name"], 0, 1))) ?></span><span class="user-body"><h3><?= htmlspecialchars($participant["name"]) ?></h3><span class="row-meta"><?= $participant["is_settled"] ? "Settled" : "Pending" ?></span></span><span class="row-value <?= $participant["is_settled"] ? 'positive' : 'warning' ?>"><?= money($participant["share_amount"]) ?></span></article>
    <?php endwhile; ?>
</div>
<?php if ((int) $subscription["user_id"] === $user_id): ?>
    <br><form method="POST" data-confirm="Delete this subscription?"><input type="hidden" name="subscription_id" value="<?= (int) $subscription_id ?>"><input type="hidden" name="action" value="delete"><button class="danger-button full-width" type="submit">DELETE SUBSCRIPTION</button></form>
<?php endif; ?>
<?php include "../includes/footer.php"; ?>
