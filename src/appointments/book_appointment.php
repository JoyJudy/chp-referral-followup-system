<?php
session_start();
include "db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$doctor_id = intval($_POST['doctor_id']);
$date = $_POST['date'];
$start = $_POST['start_time'];
$end = $_POST['end_time'];

/* ================= CHECK CONFLICT ================= */
$check = $conn->prepare("
    SELECT appointment_id
    FROM doctor_appointments
    WHERE doctor_id = ?
    AND appointment_date = ?
    AND (
        (appointment_time_start < ? AND appointment_time_end > ?)
        OR
        (appointment_time_start < ? AND appointment_time_end > ?)
    )
");

$check->bind_param("isssss", $doctor_id, $date, $end, $start, $start, $end);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    die("Slot already booked");
}

/* ================= INSERT ================= */
$stmt = $conn->prepare("
    INSERT INTO doctor_appointments
    (doctor_id, patient_name, patient_phonenumber, patient_email, appointment_date, appointment_time_start, appointment_time_end, status)
    VALUES (?, 'SYSTEM', '', '', ?, ?, ?, 'scheduled')
");

$stmt->bind_param("isss", $doctor_id, $date, $start, $end);
$stmt->execute();

header("Location: view_doctor_detail.php?doctor_id=".$doctor_id);
exit();
?>