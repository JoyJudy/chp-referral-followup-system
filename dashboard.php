<?php
session_start();

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$user = $_SESSION['user'];
?>

<!DOCTYPE html>
<html>
<head>
<title>Dashboard</title>

<style>

/* ================= RESET ================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:"Segoe UI", sans-serif;
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
    background:#ffffff;
    border-bottom:1px solid #e9e9e9;
    position:relative;
    box-shadow:0 2px 10px rgba(0,0,0,0.04);
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

/* ================= GRID DASHBOARD ================= */
.container{
    padding:18px;
}

/* GRID LAYOUT */
.grid{
    display:grid;
    grid-template-columns:repeat(auto-fit, minmax(150px, 1fr));
    gap:15px;
    margin-top:10px;
}

/* BUTTON TILES */
.tile{
    background:#fff;
    padding:18px;
    border-radius:16px;
    text-align:center;
    box-shadow:0 8px 20px rgba(0,0,0,0.06);
    transition:0.25s ease;
    border:1px solid #f1f1f1;
    text-decoration:none;
    color:#111;
}

.tile:hover{
    transform:translateY(-5px);
    box-shadow:0 14px 30px rgba(0,0,0,0.12);
}

/* ICON STYLE */
.tile span{
    font-size:26px;
    display:block;
    margin-bottom:8px;
}

/* TITLE */
.tile h3{
    font-size:14px;
    font-weight:700;
    margin-bottom:4px;
}

/* DESCRIPTION */
.tile p{
    font-size:12px;
    color:#777;
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

.hide-tile{
    display:none;
}

</style>

</head>

<body>

<!-- HEADER -->
<div class="header">

    <img class="logo" src="https://cdn-icons-png.flaticon.com/512/3774/3774299.png">

    <div class="header-center">
        <h1>Welcome <?php echo $user['firstname']; ?></h1>
        <p>Medicare Dashboard</p>
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

<!-- CONTENT -->
<div class="container">

    <div class="grid">

        <a class="tile" href="view_hospitals.php">
            <span>🏥</span>
            <h3>Hospitals</h3>
            <p>View records</p>
        </a>

        <a class="tile" href="view_doctors.php">
            <span>👨‍⚕️</span>
            <h3>Doctors</h3>
            <p>Browse doctors</p>
        </a>

        <a class="tile" href="view_appointments.php">
            <span>📅</span>
            <h3>Appointments</h3>
            <p>Schedules</p>
        </a>
        
        <a class="tile" href="add_patient.php">
            <span>➕</span>
            <h3>Add Patient</h3>
            <p>Register new patient</p>
        </a>

        <a class="tile hide-tile" href="mc_hospital.php">
            <span>💊</span>
            <h3>Payments</h3>
            <p>Hospital billing</p>
        </a>
        
        <a class="tile hide-tile" href="medicine_table.php">
            <span>🧾</span>
            <h3>Medicine</h3>
            <p>Drug list</p>
        </a>
        
        <a class="tile hide-tile" href="profile.php">
            <span>👤</span>
            <h3>Profile</h3>
            <p>Your account</p>
        </a>

    </div>

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