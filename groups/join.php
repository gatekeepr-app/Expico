<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $group_id = (int) ($_POST["group_id"] ?? 0);

    $stmt = $conn->prepare("SELECT group_id, group_name FROM groups WHERE group_id = ?");
    $stmt->bind_param("i", $group_id);
    $stmt->execute();
    $group = $stmt->get_result()->fetch_assoc();

    if (!$group) {
        $error = "No group exists with that ID.";
    } else {
        $stmt = $conn->prepare("SELECT user_id FROM group_members WHERE user_id = ? AND group_id = ?");
        $stmt->bind_param("ii", $user_id, $group_id);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = "You are already a member of this group.";
        } else {
            $role = "Member";
            $stmt = $conn->prepare("INSERT INTO group_members (user_id, group_id, role) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $user_id, $group_id, $role);
            $stmt->execute();

            header("Location: details.php?group_id=" . $group_id);
            exit();
        }
    }
}

$pageTitle = "Join Group";
$pageSubtitle = "Enter a shared group ID";
$activeNav = "groups";
$basePath = "../";
$showBack = true;
include "../includes/header.php";
?>

<?php if ($error): ?><div class="toast error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="toast success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

<form class="form-card" method="POST">
    <div class="form-group">
        <label for="group_id">Enter Group ID</label>
        <input id="group_id" name="group_id" type="number" min="1" placeholder="Example: 12" required>
    </div>
    <button class="primary-button full-width" type="submit">JOIN GROUP</button>
</form>

<?php include "../includes/footer.php"; ?>
