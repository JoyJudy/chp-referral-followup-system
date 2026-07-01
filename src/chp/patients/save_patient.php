<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_role(['chp', 'admin']);

$firstname = trim($_POST['firstname'] ?? '');
$lastname = trim($_POST['lastname'] ?? '');
$phone = trim($_POST['phone'] ?? '') ?: null;
$gender = $_POST['gender'] ?? '';
$gender = in_array($gender, ['male', 'female', 'other'], true) ? $gender : null;
$dateOfBirth = trim($_POST['date_of_birth'] ?? '') ?: null;
$address = trim($_POST['address'] ?? '') ?: null;

if ($firstname === '' || $lastname === '') {
    die("First name and last name are required");
}

$stmt = $conn->prepare("
    INSERT INTO patients (first_name, last_name, date_of_birth, gender, phone_number, address, registered_by)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param("ssssssi", $firstname, $lastname, $dateOfBirth, $gender, $phone, $address, $currentUser['user_id']);
$stmt->execute();

header("Location: dashboard.php");
exit();
?>
