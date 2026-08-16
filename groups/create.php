<?php
session_start();
require_once "../config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login.php");
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $group_name = trim($_POST["group_name"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($group_name === "") {
        $error = "Group name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO groups (group_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $group_name, $description);

        if ($stmt->execute()) {
            $group_id = $conn->insert_id;
            $role = "Admin";
            $member = $conn->prepare("INSERT INTO group_members (user_id, group_id, role) VALUES (?, ?, ?)");
            $member->bind_param("iis", $user_id, $group_id, $role);
            $member->execute();

            header("Location: details.php?group_id=" . $group_id);
            exit();
        }

        $error = "Could not create the group. Please try again.";
    }
}

$pageTitle = "Create Group";
$pageSubtitle = "Start a shared expense space";
$activeNav = "groups";
$basePath = "../";
$showBack = true;
include "../includes/header.php";
?>

<?php if ($error): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<form class="form-card" method="POST">
    <div class="form-group">
        <label for="group_name">Group Name</label>
        <input id="group_name" name="group_name" type="text" placeholder="Cox's Bazar Trip" required>
    </div>
    <div class="form-group">
        <label for="description">Description</label>
        <textarea id="description" name="description" placeholder="What is this group for?"></textarea>
    </div>
    <button class="primary-button full-width" type="submit">CREATE GROUP</button>
</form>

<?php include "../includes/footer.php"; ?>
