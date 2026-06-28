<?php
$host = "localhost";
$user = "vupocglx_methuhealth";
$pass = "AfyaBora2026";
$db   = "vupocglx_medicare";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>