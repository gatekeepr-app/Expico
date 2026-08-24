<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$expense_id = (int) ($_GET["expense_id"] ?? $_POST["expense_id"] ?? 0);
$error = "";

$stmt = $conn->prepare("
    SELECT e.*, c.category_id, c.category_name, pm.payment_method_id
    FROM expenses e
    LEFT JOIN categories c ON e.expense_id = c.expense_id
    LEFT JOIN payment_method pm ON e.expense_id = pm.expense_id AND pm.user_id = e.user_id
    WHERE e.expense_id = ? AND e.user_id = ?
    LIMIT 1
");
$stmt->bind_param("ii", $expense_id, $user_id);
$stmt->execute();
$expense = $stmt->get_result()->fetch_assoc();

$pageTitle = "Edit Expense";
$pageSubtitle = "Update split details";
$activeNav = "activity";
$basePath = "../";
$showBack = true;
$backHref = "details.php?expense_id=" . $expense_id;

if (!$expense) {
    include "../includes/header.php";
    echo '<div class="empty-state"><h3>Expense not found</h3><p>You can only edit expenses you paid.</p></div>';
    include "../includes/footer.php";
    exit();
}

$stmt = $conn->prepare("
    SELECT u.user_id, u.name
    FROM group_members gm
    INNER JOIN users u ON gm.user_id = u.user_id
    WHERE gm.group_id = ?
    ORDER BY u.name
");
$stmt->bind_param("i", $expense["group_id"]);
$stmt->execute();
$members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$member_ids = array_map(fn($member) => (int) $member["user_id"], $members);

$stmt = $conn->prepare("SELECT user_id FROM expenses_participants WHERE expense_id = ?");
$stmt->bind_param("i", $expense_id);
$stmt->execute();
$selected_participants = array_map("intval", array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), "user_id"));

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $title = trim($_POST["title"] ?? "");
    $amount = (float) ($_POST["amount"] ?? 0);
    $expense_date = $_POST["expense_date"] ?? date("Y-m-d");
    $category = trim($_POST["category"] ?? "");
    $payment_method_id = (int) ($_POST["payment_method_id"] ?? 0);
    $posted_participants = array_unique(array_map("intval", $_POST["participants"] ?? []));
    $valid_participants = array_values(array_intersect($posted_participants, $member_ids));

    if ($title === "" || $amount <= 0 || $expense_date === "") {
        $error = "Enter a valid title, amount, and date.";
    } elseif (count($valid_participants) === 0) {
        $error = "Select at least one participant.";
    } else {
        $conn->begin_transaction();
        try {
            $stmt = $conn->prepare("UPDATE expenses SET title = ?, amount = ?, expense_date = ? WHERE expense_id = ? AND user_id = ?");
            $stmt->bind_param("sdsii", $title, $amount, $expense_date, $expense_id, $user_id);
            $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM expenses_participants WHERE expense_id = ?");
            $stmt->bind_param("i", $expense_id);
            $stmt->execute();

            $share = $amount / count($valid_participants);
            $stmt = $conn->prepare("INSERT INTO expenses_participants (user_id, expense_id, share_amount, is_settled) VALUES (?, ?, ?, 0)");
            foreach ($valid_participants as $participant_id) {
                $stmt->bind_param("iid", $participant_id, $expense_id, $share);
                $stmt->execute();
            }

            $category_id = (int) ($expense["category_id"] ?? 0);
            $lower_cat = strtolower($category);
            if ($category !== "" && $category_id > 0) {
                $description = "Updated from expense form";
                $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ? AND expense_id = ?");
                $stmt->bind_param("ssii", $lower_cat, $description, $category_id, $expense_id);
                $stmt->execute();
            } elseif ($category !== "") {
                $check = $conn->prepare("SELECT category_id FROM categories WHERE LOWER(category_name) = ? AND expense_id = ? LIMIT 1");
                $check->bind_param("si", $lower_cat, $expense_id);
                $check->execute();
                if ($check->get_result()->num_rows === 0) {
                    $category_id = (int) ($conn->query("SELECT COALESCE(MAX(category_id), 0) + 1 AS next_id FROM categories")->fetch_assoc()["next_id"] ?? 1);
                    $description = "Updated from expense form";
                    $stmt = $conn->prepare("INSERT INTO categories (category_id, category_name, description, expense_id) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("issi", $category_id, $lower_cat, $description, $expense_id);
                    $stmt->execute();
                }
            } elseif ($category_id > 0) {
                $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ? AND expense_id = ?");
                $stmt->bind_param("ii", $category_id, $expense_id);
                $stmt->execute();
            }

            $stmt = $conn->prepare("UPDATE payment_method SET expense_id = NULL WHERE expense_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $expense_id, $user_id);
            $stmt->execute();

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
            $error = "Could not update the expense. Please try again.";
        }
    }

    $expense["title"] = $title;
    $expense["amount"] = $amount;
    $expense["expense_date"] = $expense_date;
    $expense["category_name"] = strtolower($category);
    $expense["payment_method_id"] = $payment_method_id;
    $selected_participants = $valid_participants;
}

$stmt = $conn->prepare("SELECT payment_method_id, method_type, account_details FROM payment_method WHERE user_id = ? ORDER BY is_default DESC, method_type");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payment_methods = $stmt->get_result();

include "../includes/header.php";
?>

<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form class="form-card" method="POST">
    <input type="hidden" name="expense_id" value="<?= (int) $expense_id ?>">
    <div class="form-group"><label for="title">Expense Title</label><input id="title" name="title" type="text" value="<?= htmlspecialchars($expense["title"]) ?>" required></div>
    <div class="form-group"><label for="amount">Amount</label><input id="amount" name="amount" type="number" min="0.01" step="0.01" value="<?= htmlspecialchars((string) $expense["amount"]) ?>" data-split-amount required></div>
    <div class="form-group"><label for="expense_date">Date</label><input id="expense_date" name="expense_date" type="date" value="<?= htmlspecialchars($expense["expense_date"]) ?>" required></div>
    <div class="form-group"><label for="category">Category</label><input id="category" name="category" type="text" value="<?= htmlspecialchars(format_category($expense["category_name"] ?? "")) ?>" placeholder="Food, Transport, Rent"></div>
    <div class="form-group">
        <label for="payment_method_id">Payment Method</label>
        <select id="payment_method_id" name="payment_method_id">
            <option value="0">Not selected</option>
            <?php while ($method = $payment_methods->fetch_assoc()): ?>
                <option value="<?= (int) $method["payment_method_id"] ?>" <?= (int) ($expense["payment_method_id"] ?? 0) === (int) $method["payment_method_id"] ? "selected" : "" ?>><?= htmlspecialchars($method["method_type"]) ?> · <?= htmlspecialchars($method["account_details"] ?? "") ?></option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="form-group">
        <label>Who participated?</label>
        <div class="card-list">
            <?php foreach ($members as $member): ?>
                <label class="participant-row"><input type="checkbox" name="participants[]" value="<?= (int) $member["user_id"] ?>" data-split-participant <?= in_array((int) $member["user_id"], $selected_participants, true) ? "checked" : "" ?>> <?= htmlspecialchars($member["name"]) ?></label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="split-preview">
        <div class="stat-pill"><span>Total</span><strong data-split-total>৳0.00</strong></div>
        <div class="stat-pill"><span>Split between</span><strong data-split-count>0</strong></div>
        <div class="stat-pill"><span>Each pays</span><strong data-split-each>৳0.00</strong></div>
    </div>
    <br>
    <button class="primary-button full-width" type="submit">SAVE CHANGES</button>
</form>

<?php include "../includes/footer.php"; ?>
