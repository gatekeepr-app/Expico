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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $amount = (float) ($_POST["amount"] ?? 0);
    $billing_cycle = trim($_POST["billing_cycle"] ?? "");
    $next_due_date = trim($_POST["next_due_date"] ?? "");
    $category_name = trim($_POST["category"] ?? "");
    $payment_method_id = (int) ($_POST["payment_method_id"] ?? 0);
    $group_id = (int) ($_POST["group_id"] ?? 0);
    $participants = array_unique(array_map("intval", $_POST["participants"] ?? []));

    $has_group_access = $group_id === 0;
    $valid_participants = [$user_id];
    if ($group_id > 0) {
        $stmt = $conn->prepare("SELECT group_id FROM group_members WHERE group_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $group_id, $user_id);
        $stmt->execute();
        $has_group_access = $stmt->get_result()->num_rows > 0;

        $valid_participants = [];
        if ($has_group_access && count($participants) > 0) {
            $stmt = $conn->prepare("SELECT user_id FROM group_members WHERE group_id = ?");
            $stmt->bind_param("i", $group_id);
            $stmt->execute();
            $member_ids = array_map("intval", array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), "user_id"));
            $valid_participants = array_values(array_intersect($participants, $member_ids));
        }
    }

    $payment_method_valid = true;
    if ($payment_method_id > 0) {
        $stmt = $conn->prepare("SELECT payment_method_id FROM payment_method WHERE payment_method_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $payment_method_id, $user_id);
        $stmt->execute();
        $payment_method_valid = $stmt->get_result()->num_rows > 0;
    }

    if ($name === "" || $amount <= 0) {
        $error = "Enter a valid subscription name and amount.";
    } elseif (!$has_group_access) {
        $error = "Select a valid group.";
    } elseif (count($valid_participants) === 0) {
        $error = "Select at least one participant.";
    } elseif (!$payment_method_valid) {
        $error = "Select a valid payment method.";
    } else {
        $conn->begin_transaction();
        try {
            $category_id = null;
            if ($category_name !== "") {
                $category_id = (int) ($conn->query("SELECT COALESCE(MAX(category_id), 0) + 1 AS next_id FROM categories")->fetch_assoc()["next_id"] ?? 1);
                $description = "Added from subscription form";
                $expense_id = null;
                $stmt = $conn->prepare("INSERT INTO categories (category_id, category_name, description, expense_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $category_id, $category_name, $description, $expense_id);
                $stmt->execute();
            }

            $subscription_id = (int) ($conn->query("SELECT COALESCE(MAX(subscription_id), 0) + 1 AS next_id FROM subscriptions")->fetch_assoc()["next_id"] ?? 1);
            $payment_method_value = $payment_method_id > 0 ? $payment_method_id : null;
            $next_due_value = $next_due_date !== "" ? $next_due_date : null;
            $billing_cycle_value = $billing_cycle !== "" ? $billing_cycle : null;

            $group_value = $group_id > 0 ? $group_id : null;
            $stmt = $conn->prepare("INSERT INTO subscriptions (subscription_id, name, amount, billing_cycle, next_due_date, payment_method_id, category_id, group_id, user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isdssiiii", $subscription_id, $name, $amount, $billing_cycle_value, $next_due_value, $payment_method_value, $category_id, $group_value, $user_id);
            $stmt->execute();

            $share = $amount / count($valid_participants);
            $get_stmt = $conn->prepare("INSERT INTO `get` (user_id, subscription_id) VALUES (?, ?)");
            $participant_stmt = $conn->prepare("INSERT INTO subscription_participants (user_id, subscription_id, share_amount, is_settled) VALUES (?, ?, ?, 0)");
            foreach ($valid_participants as $participant_id) {
                $get_stmt->bind_param("ii", $participant_id, $subscription_id);
                $get_stmt->execute();

                $participant_stmt->bind_param("iid", $participant_id, $subscription_id, $share);
                $participant_stmt->execute();
            }

            if ($next_due_value !== null) {
                $deadline_id = (int) ($conn->query("SELECT COALESCE(MAX(deadline_id), 0) + 1 AS next_id FROM deadlines")->fetch_assoc()["next_id"] ?? 1);
                $status = "upcoming";
                $stmt = $conn->prepare("INSERT INTO deadlines (deadline_id, due_date, status, subscription_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $deadline_id, $next_due_value, $status, $subscription_id);
                $stmt->execute();
            }

            $conn->commit();
            header("Location: index.php");
            exit();
        } catch (Throwable $exception) {
            $conn->rollback();
            $error = "Could not add the subscription. Please try again.";
        }
    }
}

$stmt = $conn->prepare("SELECT payment_method_id, method_type, account_details FROM payment_method WHERE user_id = ? ORDER BY is_default DESC, method_type");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$payment_methods = $stmt->get_result();

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

$pageTitle = "Add Subscription";
$pageSubtitle = "Track recurring costs";
$activeNav = "activity";
$basePath = "../";
$showBack = true;
$backHref = "index.php";
include "../includes/header.php";
?>

<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

<form class="form-card" method="POST">
    <div class="form-group"><label for="name">Subscription Name</label><input id="name" name="name" type="text" value="<?= htmlspecialchars($_POST["name"] ?? "") ?>" placeholder="Netflix, Spotify, Gym" required></div>
    <div class="form-group"><label for="amount">Amount</label><input id="amount" name="amount" type="number" min="0.01" step="0.01" value="<?= htmlspecialchars($_POST["amount"] ?? "") ?>" placeholder="500" required></div>
    <div class="form-group">
        <label for="billing_cycle">Billing Cycle</label>
        <?php $selected_cycle = $_POST["billing_cycle"] ?? "monthly"; ?>
        <select id="billing_cycle" name="billing_cycle">
            <option value="one_time" <?= $selected_cycle === "one_time" ? "selected" : "" ?>>One Time</option>
            <option value="weekly" <?= $selected_cycle === "weekly" ? "selected" : "" ?>>Weekly</option>
            <option value="monthly" <?= $selected_cycle === "monthly" ? "selected" : "" ?>>Monthly</option>
            <option value="yearly" <?= $selected_cycle === "yearly" ? "selected" : "" ?>>Yearly</option>
            <option value="custom" <?= $selected_cycle === "custom" ? "selected" : "" ?>>Custom</option>
        </select>
    </div>
    <div class="form-group">
        <label for="group_id">Group</label>
        <select id="group_id" name="group_id" onchange="window.location='add.php' + (this.value ? '?group_id=' + this.value : '')">
            <option value="0">Personal subscription</option>
            <?php while ($group = $groups->fetch_assoc()): ?>
                <option value="<?= (int) $group["group_id"] ?>" <?= $selected_group_id === (int) $group["group_id"] ? "selected" : "" ?>><?= htmlspecialchars($group["group_name"]) ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="form-group"><label for="next_due_date">Next Due Date</label><input id="next_due_date" name="next_due_date" type="date" value="<?= htmlspecialchars($_POST["next_due_date"] ?? "") ?>"></div>
    <div class="form-group"><label for="category">Category</label><input id="category" name="category" type="text" value="<?= htmlspecialchars($_POST["category"] ?? "") ?>" placeholder="Entertainment, Utilities, Fitness"></div>
    <div class="form-group">
        <label for="payment_method_id">Payment Method</label>
        <select id="payment_method_id" name="payment_method_id">
            <option value="0">Not selected</option>
            <?php $selected_method = (int) ($_POST["payment_method_id"] ?? 0); ?>
            <?php while ($method = $payment_methods->fetch_assoc()): ?>
                <option value="<?= (int) $method["payment_method_id"] ?>" <?= $selected_method === (int) $method["payment_method_id"] ? "selected" : "" ?>><?= htmlspecialchars($method["method_type"]) ?> · <?= htmlspecialchars($method["account_details"] ?? "") ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="form-group">
        <label>Who participates?</label>
        <div class="card-list">
            <?php if ($selected_group_id > 0 && count($members) > 0): ?>
                <?php foreach ($members as $member): ?>
                    <?php $posted_participants = array_map("intval", $_POST["participants"] ?? []); ?>
                    <label class="participant-row"><input type="checkbox" name="participants[]" value="<?= (int) $member["user_id"] ?>" data-split-participant <?= count($posted_participants) === 0 || in_array((int) $member["user_id"], $posted_participants, true) ? "checked" : "" ?>> <?= htmlspecialchars($member["name"]) ?></label>
                <?php endforeach; ?>
            <?php else: ?>
                <label class="participant-row"><input type="checkbox" name="participants[]" value="<?= (int) $user_id ?>" data-split-participant checked> <?= htmlspecialchars($_SESSION["user_name"] ?? "You") ?></label>
            <?php endif; ?>
        </div>
    </div>
    <div class="split-preview">
        <div class="stat-pill"><span>Total</span><strong data-split-total>৳0.00</strong></div>
        <div class="stat-pill"><span>Split between</span><strong data-split-count>0</strong></div>
        <div class="stat-pill"><span>Each pays</span><strong data-split-each>৳0.00</strong></div>
    </div>
    <br>
    <button class="primary-button full-width" type="submit">ADD SUBSCRIPTION</button>
</form>

<?php include "../includes/footer.php"; ?>
