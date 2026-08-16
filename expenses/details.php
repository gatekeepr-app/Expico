<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$expense_id = (int) ($_GET["expense_id"] ?? 0);
function money($value) { return "৳" . number_format((float) $value, 2); }

$stmt = $conn->prepare("
    SELECT e.*, g.group_name, u.name AS paid_by_name
    FROM expenses e
    INNER JOIN groups g ON e.group_id = g.group_id
    INNER JOIN users u ON e.user_id = u.user_id
    INNER JOIN group_members gm ON e.group_id = gm.group_id
    WHERE e.expense_id = ? AND gm.user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $expense_id, $user_id);
$stmt->execute();
$expense = $stmt->get_result()->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete" && $expense && (int) $expense["user_id"] === $user_id) {
    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("UPDATE payment_method SET expense_id = NULL WHERE expense_id = ?");
        $stmt->bind_param("i", $expense_id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM categories WHERE expense_id = ?");
        $stmt->bind_param("i", $expense_id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM expenses_participants WHERE expense_id = ?");
        $stmt->bind_param("i", $expense_id);
        $stmt->execute();

        $stmt = $conn->prepare("DELETE FROM expenses WHERE expense_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $expense_id, $user_id);
        $stmt->execute();

        $conn->commit();
        header("Location: list.php");
        exit();
    } catch (Throwable $exception) {
        $conn->rollback();
    }
}

$pageTitle = "Expense Details";
$pageSubtitle = "Participants and settlement state";
$activeNav = "activity";
$basePath = "../";
$showBack = true;
include "../includes/header.php";

if (!$expense) {
    echo '<div class="empty-state"><h3>Expense not found</h3><p>You do not have access to this expense.</p></div>';
    include "../includes/footer.php";
    exit();
}

$stmt = $conn->prepare("
    SELECT u.name, ep.share_amount, ep.is_settled
    FROM expenses_participants ep
    INNER JOIN users u ON ep.user_id = u.user_id
    WHERE ep.expense_id = ?
    ORDER BY u.name
");
$stmt->bind_param("i", $expense_id);
$stmt->execute();
$participants = $stmt->get_result();
?>

<section class="summary-card">
    <p class="summary-label"><?= htmlspecialchars($expense["title"]) ?></p>
    <div class="amount"><?= money($expense["amount"]) ?></div>
    <p class="summary-subtext"><?= htmlspecialchars($expense["group_name"]) ?> · <?= htmlspecialchars(date("d M Y", strtotime($expense["expense_date"]))) ?></p>
</section>

<div class="mini-grid">
    <article class="mini-card"><p class="section-kicker">Paid by</p><div class="amount" style="font-size:20px;"><?= htmlspecialchars($expense["paid_by_name"]) ?></div></article>
    <article class="mini-card"><p class="section-kicker">Status</p><div class="amount positive" style="font-size:20px;">Tracked</div></article>
</div>

<div class="section-header">
    <h2>Participants</h2>
    <?php if ((int) $expense["user_id"] === $user_id): ?>
        <a class="section-link" href="edit.php?expense_id=<?= (int) $expense_id ?>">Edit</a>
    <?php endif; ?>
</div>
<div class="card-list">
    <?php if ($participants->num_rows > 0): ?>
        <?php while ($participant = $participants->fetch_assoc()): ?>
            <article class="user-row">
                <span class="avatar"><?= htmlspecialchars(strtoupper(substr($participant["name"], 0, 1))) ?></span>
                <span class="user-body"><h3><?= htmlspecialchars($participant["name"]) ?></h3><span class="row-meta"><?= $participant["is_settled"] ? "Settled" : "Pending" ?></span></span>
                <span class="row-value <?= $participant["is_settled"] ? 'positive' : 'warning' ?>"><?= money($participant["share_amount"]) ?></span>
            </article>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state"><h3>No participants</h3><p>This expense has no participant rows yet.</p></div>
    <?php endif; ?>
</div>

<?php if ((int) $expense["user_id"] === $user_id): ?>
    <br>
    <form method="POST" data-confirm="Delete this expense? This removes its split rows too.">
        <input type="hidden" name="action" value="delete">
        <button class="danger-button full-width" type="submit">DELETE EXPENSE</button>
    </form>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>
