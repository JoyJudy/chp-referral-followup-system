<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

/* ===== START SESSION ===== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== LOAD INIT (DB + route helper) ===== */
require_once __DIR__ . '/../shared/db.php';

/* ===== LOGIN CHECK ===== */
if (!isset($_SESSION['user_phone'])) {
    header("Location: " . route('registration_options.php'));
    exit();
}

/* ===== FETCH USER ===== */
$phone = $_SESSION['user_phone'];

$stmt = $conn->prepare("
    SELECT firstname, lastname, user_phonenumber, gender, school_program, email, student_class, payment_status, payment_date, payment_expiry, school_id
    FROM users
    WHERE user_phonenumber = ?
");
$stmt->bind_param("s", $phone);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    session_destroy();
    header("Location: " . route('login.php'));
    exit();
}

$stmt->bind_result(
    $firstname,
    $lastname,
    $user_phonenumber,
    $gender,
    $school_program,
    $email,
    $student_class,
    $payment_status,
    $payment_date,
    $payment_expiry,
    $school_id
);
$stmt->fetch();

/* ===== PAYMENT CHECK ===== */
$today = date("Y-m-d");

if ($payment_status !== "Paid" || $payment_expiry < $today) {
    header("Location: " . route('stk.php'));
    exit();
}

/* ===== STORE USER DATA IN LOCAL STORAGE ===== */
?>
<script>
localStorage.setItem("firstname", "<?php echo addslashes($firstname); ?>");
localStorage.setItem("lastname", "<?php echo addslashes($lastname); ?>");
localStorage.setItem("phone", "<?php echo addslashes($user_phonenumber); ?>");
localStorage.setItem("gender", "<?php echo addslashes($gender); ?>");
localStorage.setItem("school_program", "<?php echo addslashes($school_program); ?>");
localStorage.setItem("email", "<?php echo addslashes($email); ?>");
localStorage.setItem("student_class", "<?php echo addslashes($student_class); ?>");
localStorage.setItem("payment_status", "<?php echo addslashes($payment_status); ?>");
localStorage.setItem("payment_date", "<?php echo addslashes($payment_date); ?>");
localStorage.setItem("payment_expiry", "<?php echo addslashes($payment_expiry); ?>");
localStorage.setItem("school_id", "<?php echo addslashes($school_id); ?>");
</script>