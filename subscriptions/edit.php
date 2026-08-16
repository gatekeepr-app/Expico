<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"])) { header("Location: ../login.php"); exit(); }
$user_id = (int) $_SESSION["user_id"];
$subscription_id = (int) ($_GET["subscription_id"] ?? $_POST["subscription_id"] ?? 0);
$error = "";

$stmt = $conn->prepare("SELECT s.*, c.category_name FROM subscriptions s LEFT JOIN categories c ON s.category_id = c.category_id WHERE s.subscription_id = ? AND s.user_id = ? LIMIT 1");
$stmt->bind_param("ii", $subscription_id, $user_id); $stmt->execute(); $subscription = $stmt->get_result()->fetch_assoc();
if (!$subscription) {
    $pageTitle = "Edit Subscription"; $basePath = "../"; $showBack = true; include "../includes/header.php";
    echo '<div class="empty-state"><h3>Subscription not found</h3><p>You can only edit subscriptions you created.</p></div>';
    include "../includes/footer.php"; exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $name = trim($_POST["name"] ?? "");
    $amount = (float) ($_POST["amount"] ?? 0);
    $billing_cycle = trim($_POST["billing_cycle"] ?? "");
    $next_due_date = trim($_POST["next_due_date"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $payment_method_id = (int) ($_POST["payment_method_id"] ?? 0);
    $group_id = (int) ($_POST["group_id"] ?? 0);
    $participants = array_unique(array_map("intval", $_POST["participants"] ?? []));

    $valid_participants = [$user_id];
    $has_group_access = $group_id === 0;
    if ($group_id > 0) {
        $stmt = $conn->prepare("SELECT user_id FROM group_members WHERE group_id = ?");
        $stmt->bind_param("i", $group_id); $stmt->execute(); $member_ids = array_map("intval", array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), "user_id"));
        $has_group_access = in_array($user_id, $member_ids, true);
        $valid_participants = array_values(array_intersect($participants, $member_ids));
    }

    if ($name === "" || $amount <= 0) { $error = "Enter a valid name and amount."; }
    elseif (!$has_group_access) { $error = "Select a valid group."; }
    elseif (count($valid_participants) === 0) { $error = "Select at least one participant."; }
    else {
        $conn->begin_transaction();
        try {
            $category_id = (int) ($subscription["category_id"] ?? 0);
            if ($category !== "" && $category_id > 0) {
                $description = "Updated from subscription form";
                $stmt = $conn->prepare("UPDATE categories SET category_name = ?, description = ? WHERE category_id = ?");
                $stmt->bind_param("ssi", $category, $description, $category_id); $stmt->execute();
            } elseif ($category !== "") {
                $category_id = (int) ($conn->query("SELECT COALESCE(MAX(category_id), 0) + 1 AS next_id FROM categories")->fetch_assoc()["next_id"] ?? 1);
                $description = "Updated from subscription form"; $expense_id = null;
                $stmt = $conn->prepare("INSERT INTO categories (category_id, category_name, description, expense_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $category_id, $category, $description, $expense_id); $stmt->execute();
            } elseif ($category_id > 0) {
                $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ? AND expense_id IS NULL");
                $stmt->bind_param("i", $category_id); $stmt->execute(); $category_id = null;
            } else { $category_id = null; }

            $payment_value = $payment_method_id > 0 ? $payment_method_id : null;
            $group_value = $group_id > 0 ? $group_id : null;
            $due_value = $next_due_date !== "" ? $next_due_date : null;
            $cycle_value = $billing_cycle !== "" ? $billing_cycle : null;
            $stmt = $conn->prepare("UPDATE subscriptions SET name = ?, amount = ?, billing_cycle = ?, next_due_date = ?, payment_method_id = ?, category_id = ?, group_id = ? WHERE subscription_id = ? AND user_id = ?");
            $stmt->bind_param("sdssiiiii", $name, $amount, $cycle_value, $due_value, $payment_value, $category_id, $group_value, $subscription_id, $user_id); $stmt->execute();

            $stmt = $conn->prepare("DELETE FROM subscription_participants WHERE subscription_id = ?");
            $stmt->bind_param("i", $subscription_id); $stmt->execute();
            $stmt = $conn->prepare("DELETE FROM `get` WHERE subscription_id = ?");
            $stmt->bind_param("i", $subscription_id); $stmt->execute();
            $share = $amount / count($valid_participants);
            $get_stmt = $conn->prepare("INSERT INTO `get` (user_id, subscription_id) VALUES (?, ?)");
            $participant_stmt = $conn->prepare("INSERT INTO subscription_participants (user_id, subscription_id, share_amount, is_settled) VALUES (?, ?, ?, 0)");
            foreach ($valid_participants as $participant_id) {
                $get_stmt->bind_param("ii", $participant_id, $subscription_id); $get_stmt->execute();
                $participant_stmt->bind_param("iid", $participant_id, $subscription_id, $share); $participant_stmt->execute();
            }

            if ($due_value === null) {
                $stmt = $conn->prepare("DELETE n FROM notifications n INNER JOIN deadlines d ON n.deadline_id = d.deadline_id WHERE d.subscription_id = ?");
                $stmt->bind_param("i", $subscription_id); $stmt->execute();
                $stmt = $conn->prepare("DELETE FROM deadlines WHERE subscription_id = ?");
                $stmt->bind_param("i", $subscription_id); $stmt->execute();
            } else {
                $stmt = $conn->prepare("UPDATE deadlines SET due_date = ?, status = 'upcoming' WHERE subscription_id = ?");
                $stmt->bind_param("si", $due_value, $subscription_id); $stmt->execute();
            }
            if ($due_value !== null && $stmt->affected_rows === 0) {
                $deadline_id = (int) ($conn->query("SELECT COALESCE(MAX(deadline_id), 0) + 1 AS next_id FROM deadlines")->fetch_assoc()["next_id"] ?? 1);
                $status = "upcoming"; $stmt = $conn->prepare("INSERT INTO deadlines (deadline_id, due_date, status, subscription_id) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("issi", $deadline_id, $due_value, $status, $subscription_id); $stmt->execute();
            }
            $conn->commit(); header("Location: details.php?subscription_id=" . $subscription_id); exit();
        } catch (Throwable $exception) { $conn->rollback(); $error = "Could not update subscription."; }
    }
    $subscription = array_merge($subscription, ["name" => $name, "amount" => $amount, "billing_cycle" => $billing_cycle, "next_due_date" => $next_due_date, "category_name" => $category, "payment_method_id" => $payment_method_id, "group_id" => $group_id]);
}

$stmt = $conn->prepare("SELECT payment_method_id, method_type, account_details FROM payment_method WHERE user_id = ? ORDER BY is_default DESC, method_type");
$stmt->bind_param("i", $user_id); $stmt->execute(); $payment_methods = $stmt->get_result();
$stmt = $conn->prepare("SELECT g.group_id, g.group_name FROM groups g INNER JOIN group_members gm ON g.group_id = gm.group_id WHERE gm.user_id = ? ORDER BY g.group_name");
$stmt->bind_param("i", $user_id); $stmt->execute(); $groups = $stmt->get_result();
$selected_group_id = (int) ($_GET["group_id"] ?? $subscription["group_id"] ?? 0);
$members = [];
if ($selected_group_id > 0) {
    $stmt = $conn->prepare("SELECT u.user_id, u.name FROM group_members gm INNER JOIN users u ON gm.user_id = u.user_id WHERE gm.group_id = ? ORDER BY u.name");
    $stmt->bind_param("i", $selected_group_id); $stmt->execute(); $members = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}
$stmt = $conn->prepare("SELECT user_id FROM subscription_participants WHERE subscription_id = ?");
$stmt->bind_param("i", $subscription_id); $stmt->execute(); $selected_participants = array_map("intval", array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), "user_id"));
$pageTitle = "Edit Subscription"; $pageSubtitle = "Update billing split"; $activeNav = "activity"; $basePath = "../"; $showBack = true; $backHref = "details.php?subscription_id=" . $subscription_id; include "../includes/header.php";
?>
<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form class="form-card" method="POST">
    <input type="hidden" name="subscription_id" value="<?= (int) $subscription_id ?>">
    <div class="form-group"><label for="name">Subscription Name</label><input id="name" name="name" value="<?= htmlspecialchars($subscription["name"]) ?>" required></div>
    <div class="form-group"><label for="amount">Amount</label><input id="amount" name="amount" type="number" min="0.01" step="0.01" value="<?= htmlspecialchars((string) $subscription["amount"]) ?>" data-split-amount required></div>
    <div class="form-group"><label for="billing_cycle">Billing Cycle</label><select id="billing_cycle" name="billing_cycle"><?php foreach (["one_time"=>"One Time","weekly"=>"Weekly","monthly"=>"Monthly","yearly"=>"Yearly","custom"=>"Custom"] as $value=>$label): ?><option value="<?= $value ?>" <?= ($subscription["billing_cycle"] ?? "") === $value ? "selected" : "" ?>><?= $label ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="group_id">Group</label><select id="group_id" name="group_id" onchange="window.location='edit.php?subscription_id=<?= (int) $subscription_id ?>&group_id='+this.value"><option value="0">Personal subscription</option><?php while ($group = $groups->fetch_assoc()): ?><option value="<?= (int) $group["group_id"] ?>" <?= $selected_group_id === (int) $group["group_id"] ? "selected" : "" ?>><?= htmlspecialchars($group["group_name"]) ?></option><?php endwhile; ?></select></div>
    <div class="form-group"><label for="next_due_date">Next Due Date</label><input id="next_due_date" name="next_due_date" type="date" value="<?= htmlspecialchars($subscription["next_due_date"] ?? "") ?>"></div>
    <div class="form-group"><label for="category">Category</label><input id="category" name="category" value="<?= htmlspecialchars($subscription["category_name"] ?? "") ?>"></div>
    <div class="form-group"><label for="payment_method_id">Payment Method</label><select id="payment_method_id" name="payment_method_id"><option value="0">Not selected</option><?php while ($method = $payment_methods->fetch_assoc()): ?><option value="<?= (int) $method["payment_method_id"] ?>" <?= (int) ($subscription["payment_method_id"] ?? 0) === (int) $method["payment_method_id"] ? "selected" : "" ?>><?= htmlspecialchars($method["method_type"]) ?> · <?= htmlspecialchars($method["account_details"] ?? "") ?></option><?php endwhile; ?></select></div>
    <div class="form-group"><label>Who participates?</label><div class="card-list"><?php if ($selected_group_id > 0): foreach ($members as $member): ?><label class="participant-row"><input type="checkbox" name="participants[]" value="<?= (int) $member["user_id"] ?>" data-split-participant <?= in_array((int) $member["user_id"], $selected_participants, true) ? "checked" : "" ?>> <?= htmlspecialchars($member["name"]) ?></label><?php endforeach; else: ?><label class="participant-row"><input type="checkbox" name="participants[]" value="<?= (int) $user_id ?>" data-split-participant checked> <?= htmlspecialchars($_SESSION["user_name"] ?? "You") ?></label><?php endif; ?></div></div>
    <div class="split-preview"><div class="stat-pill"><span>Total</span><strong data-split-total>৳0.00</strong></div><div class="stat-pill"><span>Split between</span><strong data-split-count>0</strong></div><div class="stat-pill"><span>Each pays</span><strong data-split-each>৳0.00</strong></div></div><br>
    <button class="primary-button full-width" type="submit">SAVE CHANGES</button>
</form>
<?php include "../includes/footer.php"; ?>
