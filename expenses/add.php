<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$selected_group_id = (int) ($_GET["group_id"] ?? $_POST["group_id"] ?? 0);
$error = "";
$group_blocked = false;

if ($selected_group_id > 0) {
    $stmt = $conn->prepare("SELECT settlement_deadline FROM groups WHERE group_id = ? LIMIT 1");
    $stmt->bind_param("i", $selected_group_id);
    $stmt->execute();
    $group_info = $stmt->get_result()->fetch_assoc();
    $deadline = $group_info["settlement_deadline"] ?? null;
    if ($deadline !== null && $deadline !== "" && $deadline <= date("Y-m-d")) {
        $group_blocked = true;
    }
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $amount = (float) ($_POST["amount"] ?? 0);
    $expense_date = $_POST["expense_date"] ?? date("Y-m-d");
    $group_id = (int) ($_POST["group_id"] ?? 0);
    $participants = array_map("intval", $_POST["participants"] ?? []);

    $stmt = $conn->prepare("SELECT group_id FROM group_members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $user_id);
    $stmt->execute();
    $has_access = $stmt->get_result()->num_rows > 0;

    $post_blocked = false;
    if ($group_id > 0) {
        $stmt = $conn->prepare("SELECT settlement_deadline FROM groups WHERE group_id = ? LIMIT 1");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $pg = $stmt->get_result()->fetch_assoc();
        $pd = $pg["settlement_deadline"] ?? null;
        if ($pd !== null && $pd !== "" && $pd <= date("Y-m-d")) {
            $post_blocked = true;
        }
    }

    if ($post_blocked) {
        $error = "Settlement deadline has passed. Cannot add expenses to this group.";
    } elseif ($title === "" || $amount <= 0 || !$has_access) {
        $error = "Enter a valid title, amount, and group.";
    } elseif (count($participants) === 0) {
        $error = "Select at least one participant.";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("INSERT INTO expenses (title, amount, expense_date, group_id, user_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sdsii", $title, $amount, $expense_date, $group_id, $user_id);
            $stmt->execute();
            $expense_id = $conn->insert_id;

            $share = $amount / count($participants);
            $stmt = $conn->prepare("INSERT INTO expenses_participants (user_id, expense_id, share_amount, is_settled) VALUES (?, ?, ?, 0)");
            foreach ($participants as $participant_id) {
                $check = $conn->prepare("SELECT user_id FROM group_members WHERE group_id = ? AND user_id = ?");
                $check->bind_param("ii", $group_id, $participant_id);
                $check->execute();
                if ($check->get_result()->num_rows > 0) {
                    $stmt->bind_param("iid", $participant_id, $expense_id, $share);
                    $stmt->execute();
                }
            }

            $category = trim($_POST["category"] ?? "");
            if ($category !== "") {
                $lower_cat = strtolower($category);
                $check = $conn->prepare("SELECT category_id FROM categories WHERE LOWER(category_name) = ? AND expense_id = ? LIMIT 1");
                $check->bind_param("si", $lower_cat, $expense_id);
                $check->execute();
                if ($check->get_result()->num_rows === 0) {
                    $category_id = (int) ($conn->query("SELECT COALESCE(MAX(category_id), 0) + 1 AS next_id FROM categories")->fetch_assoc()["next_id"] ?? 1);
                    $description = "Added from expense form";
                    $stmt = $conn->prepare("INSERT INTO categories (category_id, category_name, description, expense_id) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("issi", $category_id, $lower_cat, $description, $expense_id);
                    $stmt->execute();
                }
            }

            $payment_method_id = (int) ($_POST["payment_method_id"] ?? 0);
            if ($payment_method_id > 0) {
                $stmt = $conn->prepare("UPDATE payment_method SET expense_id = ? WHERE payment_method_id = ? AND user_id = ?");
                $stmt->bind_param("iii", $expense_id, $payment_method_id, $user_id);
                $stmt->execute();
            }

            $conn->commit();
            header("Location: details.php?expense_id=" . $expense_id);
            exit();
        } catch (Throwable $exception) {
            $conn->rollback();
            $error = "Could not add the expense. Please try again.";
        }
    }
}

$stmt = $conn->prepare("
    SELECT g.group_id, g.group_name
    FROM groups g
    INNER JOIN group_members gm ON g.group_id = gm.group_id
    WHERE gm.user_id = ?
    ORDER BY g.group_name
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$groups = $stmt->get_result();

$members = [];
if ($selected_group_id > 0) {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.name
        FROM group_members gm
        INNER JOIN users u ON gm.user_id = u.user_id
        WHERE gm.group_id = ?
        ORDER BY u.name
    ");
    $stmt->bind_param("i", $selected_group_id);
    $stmt->execute();
    $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$stmt = $conn->prepare("SELECT payment_method_id, method_type, account_details FROM payment_method WHERE user_id = ? ORDER BY is_default DESC, method_type");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payment_methods = $stmt->get_result();

$pageTitle = "Add Expense";
$pageSubtitle = "Split with participants";
$activeNav = "activity";
$basePath = "../";
$showBack = true;
include "../includes/header.php";
?>

<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<?php if ($group_blocked): ?>
<div class="empty-state">
    <h3>Settlement period active</h3>
    <p>Cannot add expenses. The settlement deadline for this group has passed. Ask the admin to extend the deadline to add new expenses.</p>
</div>
<?php else: ?>
<form class="form-card" method="POST">
    <div class="form-group"><label for="title">Expense Title</label><input id="title" name="title" type="text" placeholder="Dinner" required></div>
    <div class="form-group"><label for="amount">Amount</label><input id="amount" name="amount" type="number" min="0.01" step="0.01" placeholder="1200" data-split-amount required></div>
    <div class="form-group"><label for="expense_date">Date</label><input id="expense_date" name="expense_date" type="date" value="<?= date("Y-m-d") ?>" required></div>
    <div class="form-group">
        <label for="group_id">Group</label>
        <select id="group_id" name="group_id" required onchange="if(this.value){window.location='add.php?group_id='+this.value}">
            <option value="">Select group</option>
            <?php while ($group = $groups->fetch_assoc()): ?>
                <option value="<?= (int) $group["group_id"] ?>" <?= $selected_group_id === (int) $group["group_id"] ? "selected" : "" ?>><?= htmlspecialchars($group["group_name"]) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="form-group"><label for="category">Category</label><input id="category" name="category" type="text" placeholder="Food, Transport, Rent"></div>
    <div class="form-group">
        <label for="payment_method_id">Payment Method</label>
        <select id="payment_method_id" name="payment_method_id">
            <option value="0">Not selected</option>
            <?php while ($method = $payment_methods->fetch_assoc()): ?>
                <option value="<?= (int) $method["payment_method_id"] ?>"><?= htmlspecialchars($method["method_type"]) ?> · <?= htmlspecialchars($method["account_details"] ?? "") ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Who participated?</label>
        <div class="card-list">
            <?php if (count($members) > 0): ?>
                <?php foreach ($members as $member): ?>
                    <label class="participant-row"><input type="checkbox" name="participants[]" value="<?= (int) $member["user_id"] ?>" data-split-participant checked> <?= htmlspecialchars($member["name"]) ?></label>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state"><h3>Select a group</h3><p>Participants appear after selecting a group.</p></div>
            <?php endif; ?>
        </div>
    </div>

    <div class="split-preview">
        <div class="stat-pill"><span>Total</span><strong data-split-total>৳0.00</strong></div>
        <div class="stat-pill"><span>Split between</span><strong data-split-count>0</strong></div>
        <div class="stat-pill"><span>Each pays</span><strong data-split-each>৳0.00</strong></div>
    </div>
    <br>
    <button class="primary-button full-width" type="submit">ADD EXPENSE</button>
</form>
<?php endif; ?>

<?php include "../includes/footer.php"; ?>
