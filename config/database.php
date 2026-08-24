<?php

$host = "localhost";
$username = "root";
$password = "";
$database = "expico";

$conn = new mysqli(
    $host,
    $username,
    $password,
    $database
);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function format_category($name) {
    return ($name !== "" && $name !== null) ? ucwords($name) : "";
}

?>