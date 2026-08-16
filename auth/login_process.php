<?php

session_start();

require_once "../config/database.php";


if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../login.php");
    exit();

}


$email = trim($_POST["email"] ?? "");
$password = $_POST["password"] ?? "";


if ($email === "" || $password === "") {

    $_SESSION["login_error"] =
        "Please enter your email and password.";

    header("Location: ../login.php");
    exit();

}


/*
 * Find user.
 */

$sql = "
    SELECT
        user_id,
        name,
        email,
        password_hash,
        phone_no
    FROM users
    WHERE email = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $email);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows !== 1) {

    $_SESSION["login_error"] =
        "Invalid email or password.";

    header("Location: ../login.php");
    exit();

}


$user = $result->fetch_assoc();


/*
 * Verify password.
 */

if (!password_verify($password, $user["password_hash"])) {

    $_SESSION["login_error"] =
        "Invalid email or password.";

    header("Location: ../login.php");
    exit();

}


/*
 * Login successful.
 */

session_regenerate_id(true);

$_SESSION["user_id"] = $user["user_id"];
$_SESSION["user_name"] = $user["name"];
$_SESSION["user_email"] = $user["email"];


header("Location: ../dashboard.php");
exit();

?>
