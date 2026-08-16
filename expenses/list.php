<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
function money($value) { return "৳" . number_format((float) $value, 2); }

$stmt = $conn->prepare("
    SELECT e.expense_id, e.title, e.amount, e.expense_date, g.group_name, u.name AS paid_by
    FROM expenses e
    INNER JOIN groups g ON e.group_id = g.group_id
    INNER JOIN users u ON e.user_id = u.user_id
    INNER JOIN group_members gm ON e.group_id = gm.group_id
    WHERE gm.user_id = ?
    ORDER BY e.expense_date DESC, e.created_at DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$expenses = $stmt->get_result();

$pageTitle = "Expenses";
$pageSubtitle = "Entries across your groups";
$activeNav = "activity";
$basePath = "../";
$showBack = true;
include "../includes/header.php";
?>

<div class="filter-pills" data-filter-group>
    <button class="filter-pill active" type="button" data-filter="all">All</button>
    <button class="filter-pill" type="button" data-filter="month">This Month</button>
    <button class="filter-pill" type="button" data-filter="mine">Paid by Me</button>
</div>

<div class="section-header"><h2>Latest Entries</h2><span><a class="section-link" href="../settlements/index.php">Settlements</a> · <a class="section-link" href="../deadlines/index.php">Deadlines</a> · <a class="section-link" href="add.php">Add</a></span></div>
<div class="card-list">
    <?php if ($expenses->num_rows > 0): ?>
        <?php while ($expense = $expenses->fetch_assoc()): ?>
            <?php
            $date = strtotime($expense["expense_date"]);
            $filter = date("Y-m", $date) === date("Y-m") ? "month" : "old";
            if ($expense["paid_by"] === ($_SESSION["user_name"] ?? "")) { $filter = "mine"; }
            ?>
            <a class="transaction-row" href="details.php?expense_id=<?= (int) $expense["expense_id"] ?>" data-filter-item="<?= $filter ?>">
                <span class="icon-circle"><svg class="inline-icon" viewBox="0 0 24 24"><path d="M6 3h12l2 7H4z"/><path d="M6 10v10h12V10"/><path d="M9 14h6"/></svg></span>
                <span class="transaction-body"><h3><?= htmlspecialchars($expense["title"]) ?></h3><span class="transaction-meta"><?= htmlspecialchars($expense["group_name"]) ?> · <?= htmlspecialchars(date("d M Y", $date)) ?> · Paid by <?= htmlspecialchars($expense["paid_by"]) ?></span></span>
                <span class="transaction-amount"><?= money($expense["amount"]) ?></span>
            </a>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="empty-state"><h3>No expenses yet</h3><p>Add an expense and split it with group members.</p></div>
    <?php endif; ?>
</div>

<?php include "../includes/footer.php"; ?>
