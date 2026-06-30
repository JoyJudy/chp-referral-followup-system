<?php
$host = "localhost";
$user = "root";
$pass = "root123";
$db   = "chp-referral-followup-system";
$port = 3306;

$conn = new mysqli($host, $user, $pass, $db, $port);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");
?>