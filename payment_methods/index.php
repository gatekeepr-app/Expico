<?php
session_start();
require_once "../config/database.php";
if (!isset($_SESSION["user_id"])) { header("Location: ../login.php"); exit(); }
$user_id = (int) $_SESSION["user_id"];
$error = "";
$edit_id = (int) ($_GET["edit_id"] ?? 0);

function mask_detail($value) {
    $value = trim((string) $value);
    if ($value === "") { return "No details"; }
    $last = substr($value, -4);
    return "•••• " . $last;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "add";
    $payment_method_id = (int) ($_POST["payment_method_id"] ?? 0);
    $method_type = trim($_POST["method_type"] ?? "");
    $account_details = trim($_POST["account_details"] ?? "");
    $is_default = isset($_POST["is_default"]) ? 1 : 0;

    if ($action === "delete") {
        $stmt = $conn->prepare("UPDATE subscriptions SET payment_method_id = NULL WHERE payment_method_id = ?");
        $stmt->bind_param("i", $payment_method_id); $stmt->execute();
        $stmt = $conn->prepare("DELETE FROM payment_method WHERE payment_method_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $payment_method_id, $user_id); $stmt->execute();
        header("Location: index.php"); exit();
    } elseif ($action === "default") {
        $stmt = $conn->prepare("UPDATE payment_method SET is_default = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id); $stmt->execute();
        $stmt = $conn->prepare("UPDATE payment_method SET is_default = 1 WHERE payment_method_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $payment_method_id, $user_id); $stmt->execute();
        header("Location: index.php"); exit();
    } elseif ($method_type === "") {
        $error = "Payment method type is required.";
    } elseif ($action === "edit") {
        if ($is_default) {
            $stmt = $conn->prepare("UPDATE payment_method SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id); $stmt->execute();
        }
        $stmt = $conn->prepare("UPDATE payment_method SET method_type = ?, account_details = ?, is_default = ? WHERE payment_method_id = ? AND user_id = ?");
        $stmt->bind_param("ssiii", $method_type, $account_details, $is_default, $payment_method_id, $user_id);
        $stmt->execute();
        header("Location: index.php"); exit();
    } else {
        $next = (int) ($conn->query("SELECT COALESCE(MAX(payment_method_id), 0) + 1 AS next_id FROM payment_method")->fetch_assoc()["next_id"] ?? 1);
        if ($is_default) {
            $stmt = $conn->prepare("UPDATE payment_method SET is_default = 0 WHERE user_id = ?");
            $stmt->bind_param("i", $user_id); $stmt->execute();
        }
        $expense_id = null;
        $stmt = $conn->prepare("INSERT INTO payment_method (payment_method_id, method_type, account_details, is_default, expense_id, user_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issiii", $next, $method_type, $account_details, $is_default, $expense_id, $user_id);
        $stmt->execute();
    }
}

$edit_method = null;
if ($edit_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM payment_method WHERE payment_method_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $edit_id, $user_id); $stmt->execute(); $edit_method = $stmt->get_result()->fetch_assoc();
}

$stmt = $conn->prepare("SELECT * FROM payment_method WHERE user_id = ? ORDER BY is_default DESC, method_type");
$stmt->bind_param("i", $user_id); $stmt->execute(); $methods = $stmt->get_result();
$pageTitle = "Payment Methods"; $pageSubtitle = "Cards, wallets, and cash"; $activeNav = "profile"; $basePath = "../"; $showBack = true; $backHref = "../profile.php";
include "../includes/header.php";
?>
<?php if ($error): ?><div class="alert error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<form class="form-card" method="POST">
    <input type="hidden" name="action" value="<?= $edit_method ? 'edit' : 'add' ?>">
    <?php if ($edit_method): ?><input type="hidden" name="payment_method_id" value="<?= (int) $edit_method["payment_method_id"] ?>"><?php endif; ?>
    <div class="form-group"><label for="method_type">Method Type</label><input id="method_type" name="method_type" value="<?= htmlspecialchars($edit_method["method_type"] ?? "") ?>" placeholder="Visa, bKash, Cash" required></div>
    <div class="form-group"><label for="account_details">Account Details</label><input id="account_details" name="account_details" value="<?= htmlspecialchars($edit_method["account_details"] ?? "") ?>" placeholder="Card or wallet reference"></div>
    <label class="participant-row"><input type="checkbox" name="is_default" value="1" <?= !empty($edit_method["is_default"]) ? "checked" : "" ?>> Set as default</label>
    <br>
    <button class="primary-button full-width" type="submit"><?= $edit_method ? "SAVE PAYMENT METHOD" : "ADD PAYMENT METHOD" ?></button>
    <?php if ($edit_method): ?><br><br><a class="secondary-button full-width" href="index.php">CANCEL EDIT</a><?php endif; ?>
</form>
<div class="section-header"><h2>Payment Methods</h2></div>
<div class="card-list">
    <?php if ($methods->num_rows > 0): while ($m = $methods->fetch_assoc()): ?>
        <article class="payment-card">
            <div class="row-top"><div><h3><?= htmlspecialchars($m["method_type"]) ?></h3><p class="group-meta"><?= htmlspecialchars(mask_detail($m["account_details"])) ?></p></div><?php if ($m["is_default"]): ?><span class="badge success">Default</span><?php endif; ?></div>
            <div class="group-stats">
                <a class="secondary-button" href="index.php?edit_id=<?= (int) $m["payment_method_id"] ?>">Edit</a>
                <?php if (!$m["is_default"]): ?>
                    <form method="POST"><input type="hidden" name="action" value="default"><input type="hidden" name="payment_method_id" value="<?= (int) $m["payment_method_id"] ?>"><button class="secondary-button full-width" type="submit">Default</button></form>
                <?php else: ?>
                    <span class="badge success" style="justify-content:center;">Active</span>
                <?php endif; ?>
            </div>
            <form method="POST" data-confirm="Delete this payment method?" style="margin-top:10px;"><input type="hidden" name="action" value="delete"><input type="hidden" name="payment_method_id" value="<?= (int) $m["payment_method_id"] ?>"><button class="danger-button full-width" type="submit">DELETE</button></form>
        </article>
    <?php endwhile; else: ?>
        <div class="empty-state"><h3>No payment methods</h3><p>Add a card, wallet, or cash account reference.</p></div>
    <?php endif; ?>
</div>

<br>
<a class="danger-button full-width" href="../logout.php">LOGOUT</a>
<?php include "../includes/footer.php"; ?>
