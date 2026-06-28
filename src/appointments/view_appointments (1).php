<?php
session_start();
include "db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$res = $conn->query("
    SELECT *
    FROM doctor_appointments
    ORDER BY appointment_date DESC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Appointments Calendar</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<style>

/* ================= BASE ================= */

*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:"Segoe UI",sans-serif;
}

body{
    background:linear-gradient(135deg,#f5f6fa,#eef1f7);
    color:#222;
}

/* ================= HEADER ================= */

.header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:14px 18px;
    background:#fff;
    border-bottom:1px solid #e9e9e9;
    box-shadow:0 2px 10px rgba(0,0,0,0.04);
    position:relative;
}

.logo{
    height:52px;
    border-radius:50%;
    box-shadow:0 6px 18px rgba(0,0,0,0.08);
}

.header-center{
    position:absolute;
    left:50%;
    transform:translateX(-50%);
    text-align:center;
}

.header-center h1{
    font-size:18px;
    font-weight:700;
}

.header-center p{
    font-size:12px;
    color:#777;
}

/* ================= MENU ================= */

.menu-btn{
    font-size:28px;
    cursor:pointer;
    padding:6px 10px;
    border-radius:8px;
}

.menu-btn:hover{
    background:#f0f0f0;
}

/* ================= OVERLAY ================= */

.overlay{
    position:fixed;
    top:0;
    left:0;
    width:100%;
    height:100%;
    background:rgba(0,0,0,0.55);
    display:none;
    z-index:999;
}

/* ================= DRAWER ================= */

.drawer{
    position:fixed;
    top:0;
    right:-270px;
    width:270px;
    height:100%;
    background:linear-gradient(180deg,#111,#1c1c1c);
    transition:0.3s ease;
    padding-top:70px;
    z-index:1000;
}

.drawer.open{
    right:0;
}

.drawer a{
    display:block;
    padding:14px 18px;
    color:#fff;
    text-decoration:none;
    border-bottom:1px solid rgba(255,255,255,0.08);
}

.drawer a:hover{
    background:rgba(255,255,255,0.08);
    padding-left:24px;
}

/* ================= PAGE HEADER ================= */

.page-header{
    text-align:center;
    padding:25px 15px 10px;
}

.page-header h1{
    font-size:22px;
    margin-bottom:5px;
}

.page-header p{
    color:#666;
    font-size:13px;
}

/* ================= CALENDAR LAYOUT ================= */

.container{
    max-width:900px;
    margin:auto;
    padding:20px;
}

/* DATE GROUP */
.date-group{
    margin-bottom:22px;
}

/* DATE HEADER */
.date-title{
    font-size:14px;
    font-weight:800;
    color:#0d6efd;
    margin-bottom:10px;
    padding-left:6px;
    border-left:4px solid #0d6efd;
}

/* APPOINTMENT ROW */
.item{
    background:#fff;
    padding:14px 16px;
    border-radius:12px;
    margin-bottom:10px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    box-shadow:0 8px 18px rgba(0,0,0,0.05);
    border:1px solid #f1f1f1;
    transition:0.2s ease;
}

.item:hover{
    transform:translateX(4px);
}

/* LEFT SIDE */
.left{
    display:flex;
    flex-direction:column;
}

.patient{
    font-size:14px;
    font-weight:700;
}

.meta{
    font-size:12px;
    color:#777;
}

/* STATUS */
.status{
    font-size:11px;
    font-weight:700;
    padding:6px 10px;
    border-radius:20px;
}

/* STATUS COLORS */
.pending{
    background:#fff4d6;
    color:#b8860b;
}

.confirmed{
    background:#e8fff1;
    color:#198754;
}

.cancelled{
    background:#ffe5e5;
    color:#dc3545;
}

/* ================= MOBILE ================= */

@media(max-width:600px){

    .header{
        flex-direction:column;
        gap:10px;
    }

    .header-center{
        position:static;
        transform:none;
    }

    .item{
        flex-direction:column;
        align-items:flex-start;
        gap:8px;
    }
}

</style>

</head>

<body>

<!-- HEADER -->
<div class="header">

    <img class="logo"
         src="https://cdn-icons-png.flaticon.com/512/3774/3774299.png">

    <div class="header-center">
        <h1>Medicare System</h1>
        <p>Appointments Calendar</p>
    </div>

    <div class="menu-btn" onclick="openMenu()">☰</div>

</div>

<!-- OVERLAY -->
<div class="overlay" id="overlay" onclick="closeMenu()"></div>

<!-- DRAWER -->
<div class="drawer" id="drawer">

    <a href="dashboard.php">Home</a>
    <a href="view_hospitals.php">Hospitals</a>
    <a href="view_doctors.php">Doctors</a>
    <a href="view_appointments.php">Appointments</a>
    <a href="logout.php">Logout</a>

</div>

<!-- PAGE HEADER -->
<div class="page-header">
    <h1>Appointments Calendar</h1>
    <p>Simple timeline view of all bookings</p>
</div>

<!-- CONTENT -->
<div class="container">

<?php
$currentDate = "";
?>

<?php while($row = $res->fetch_assoc()) { ?>

<?php
$date = $row['appointment_date'];
$status = strtolower($row['status']);

$statusClass = "pending";
if ($status === "confirmed") $statusClass = "confirmed";
elseif ($status === "cancelled") $statusClass = "cancelled";
?>

<?php if ($date !== $currentDate) { ?>
    
    <?php if ($currentDate !== "") echo "</div>"; ?>

    <div class="date-group">
        <div class="date-title">📅 <?php echo htmlspecialchars($date); ?></div>

<?php
$currentDate = $date;
} ?>

    <div class="item">

        <div class="left">
            <div class="patient">
                👤 <?php echo htmlspecialchars($row['patient_name']); ?>
            </div>
            <div class="meta">
                Appointment booking record
            </div>
        </div>

        <div class="status <?php echo $statusClass; ?>">
            <?php echo htmlspecialchars($row['status']); ?>
        </div>

    </div>

<?php } ?>

<?php if ($currentDate !== "") echo "</div>"; ?>

</div>

<script>

function openMenu(){
    document.getElementById("drawer").classList.add("open");
    document.getElementById("overlay").style.display="block";
}

function closeMenu(){
    document.getElementById("drawer").classList.remove("open");
    document.getElementById("overlay").style.display="none";
}

</script>

</body>
</html>