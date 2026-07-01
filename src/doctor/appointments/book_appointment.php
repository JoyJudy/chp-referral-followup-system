<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_role(['doctor', 'admin']);

$referral_id = intval($_POST['referral_id']);
$date = $_POST['date'];
$time = $_POST['time'];

/* ================= RESOLVE DOCTOR FROM REFERRAL ================= */
/* The doctor is taken from the referral itself (not a posted field), so a
   doctor can't book an appointment onto a referral that wasn't routed to them. */
$referralStmt = $conn->prepare("SELECT doctor_id FROM referrals WHERE referral_id = ?");
$referralStmt->bind_param("i", $referral_id);
$referralStmt->execute();
$referral = $referralStmt->get_result()->fetch_assoc();

if (!$referral || !$referral['doctor_id']) {
    die("Referral not found");
}

$doctor_id = $referral['doctor_id'];

/* ================= CHECK CONFLICT ================= */
$check = $conn->prepare("
    SELECT appointment_id
    FROM appointments
    WHERE doctor_id = ?
    AND appointment_date = ?
    AND appointment_time = ?
");

$check->bind_param("iss", $doctor_id, $date, $time);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    die("Slot already booked");
}

/* ================= INSERT ================= */
$stmt = $conn->prepare("
    INSERT INTO appointments (referral_id, doctor_id, appointment_date, appointment_time, status)
    VALUES (?, ?, ?, ?, 'scheduled')
");

$stmt->bind_param("iiss", $referral_id, $doctor_id, $date, $time);
$stmt->execute();

header("Location: /src/admin/doctors/view_doctor_detail.php?doctor_id=" . $doctor_id);
exit();
?>