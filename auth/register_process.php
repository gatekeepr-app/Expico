<?php

session_start();

require_once "../config/database.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../register.php");
    exit();

}


$name = trim($_POST["name"] ?? "");
$email = trim($_POST["email"] ?? "");
$phone_no = trim($_POST["phone_no"] ?? "");
$password = $_POST["password"] ?? "";
$confirm_password = $_POST["confirm_password"] ?? "";


if ($name === "" || $email === "" || $password === "") {

    $_SESSION["register_error"] =
        "Please fill in all required fields.";

    header("Location: ../register.php");
    exit();

}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    $_SESSION["register_error"] =
        "Please enter a valid email address.";

    header("Location: ../register.php");
    exit();

}


if ($password !== $confirm_password) {

    $_SESSION["register_error"] =
        "Passwords do not match.";

    header("Location: ../register.php");
    exit();

}


if (strlen($password) < 6) {

    $_SESSION["register_error"] =
        "Password must be at least 6 characters.";

    header("Location: ../register.php");
    exit();

}


/*
 * Check whether email already exists.
 */

$sql = "SELECT user_id FROM users WHERE email = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $email);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows > 0) {

    $_SESSION["register_error"] =
        "An account with this email already exists.";

    header("Location: ../register.php");
    exit();

}


/*
 * Hash password.
 */

$password_hash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


/*
 * Insert user.
 */

$sql = "
    INSERT INTO users
    (user_id, name, email, password_hash, phone_no)
    VALUES (?, ?, ?, ?, ?)
";

$stmt = $conn->prepare($sql);

$next_user_id_result = $conn->query(
    "SELECT COALESCE(MAX(user_id), 0) + 1 AS next_user_id FROM users"
);

$next_user_id = (int) $next_user_id_result
    ->fetch_assoc()["next_user_id"];

$stmt->bind_param(
    "issss",
    $next_user_id,
    $name,
    $email,
    $password_hash,
    $phone_no
);


if ($stmt->execute()) {

    $_SESSION["register_success"] =
        "Account created successfully. Please login.";

    header("Location: ../login.php");
    exit();

}


$_SESSION["register_error"] =
    "Something went wrong. Please try again.";

header("Location: ../register.php");
exit();

?>
