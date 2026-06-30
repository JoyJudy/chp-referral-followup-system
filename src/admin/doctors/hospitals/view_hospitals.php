<?php
session_start();
require_once __DIR__ . '/../shared/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

/* ================= GET USER ================= */
$user = $_SESSION['user'];

/* ================= FETCH HOSPITALS ================= */
$res = $conn->query("
    SELECT *
    FROM mc_hospital
    ORDER BY hospital_name ASC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Hospitals Dashboard</title>

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

/* ================= MENU BUTTON ================= */

.menu-btn{
    font-size:28px;
    cursor:pointer;
    padding:6px 10px;
    border-radius:8px;
}

.menu-btn:hover{
    background:#f0f0f0;
}

/* ================= PAGE HEADER ================= */

.page-header{
    text-align:center;
    padding:30px 15px 10px;
}

.page-header h1{
    font-size:24px;
    margin-bottom:6px;
}

.page-header p{
    color:#666;
    font-size:14px;
}

/* ================= GRID ================= */

.container{
    max-width:1200px;
    margin:auto;
    padding:20px;
    display:grid;
    grid-template-columns:repeat(auto-fill,minmax(280px,1fr));
    gap:20px;
}

/* ================= CARD ================= */

.card{
    background:#fff;
    border-radius:18px;
    padding:20px;
    box-shadow:0 10px 25px rgba(0,0,0,0.06);
    border:1px solid #f1f1f1;
    transition:.25s ease;
}

.card:hover{
    transform:translateY(-5px);
    box-shadow:0 14px 30px rgba(0,0,0,0.10);
}

.hospital-name{
    font-size:18px;
    font-weight:700;
    margin-bottom:8px;
}

.location{
    font-size:14px;
    color:#666;
    margin-bottom:12px;
}

/* ================= BADGES ================= */

.fee{
    display:inline-block;
    background:#eef6ff;
    color:#0d6efd;
    padding:8px 12px;
    border-radius:30px;
    font-weight:600;
    font-size:13px;
    margin-bottom:12px;
}

.status{
    display:inline-block;
    padding:7px 12px;
    border-radius:30px;
    font-size:12px;
    font-weight:600;
    margin-top:10px;
}

.active{
    background:#e8fff1;
    color:#198754;
}

.pending{
    background:#fff4d6;
    color:#b8860b;
}

.inactive{
    background:#ffe5e5;
    color:#dc3545;
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
}

</style>

</head>

<body>

<!-- HEADER -->
<div class="header">

    <img class="logo"
         src="https://cdn-icons-png.flaticon.com/512/3774/3774299.png">

    <div class="header-center">
        <h1>Welcome <?php echo htmlspecialchars($user['firstname'] ?? 'User'); ?></h1>
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

<!-- PAGE HEADER -->
<div class="page-header">
    <h1>Partner Hospitals</h1>
    <p>Browse healthcare facilities and consultation details</p>
</div>

<!-- GRID -->
<div class="container">

<?php while($row = $res->fetch_assoc()) { ?>

<?php
$status = strtolower($row['hospital_payment_status'] ?? '');

$statusClass = "inactive";
if ($status === "active") {
    $statusClass = "active";
} elseif ($status === "pending") {
    $statusClass = "pending";
}
?>

    <div class="card">

        <div class="hospital-name">
            <?php echo htmlspecialchars($row['hospital_name']); ?>
        </div>

        <div class="location">
            📍 <?php echo htmlspecialchars($row['hospital_location']); ?>
        </div>

        <div class="fee">
            Consultation Fee: KSh
            <?php echo number_format($row['hospital_consultancy_fee']); ?>
        </div>

        <div>
            <span class="status <?php echo $statusClass; ?>">
                <?php echo htmlspecialchars($row['hospital_payment_status']); ?>
            </span>
        </div>

    </div>

<?php } ?>

</div>

<script>
function openMenu(){
    document.getElementById("drawer").classList.add("open");
    document.getElementById("overlay").style.display = "block";
}

function closeMenu(){
    document.getElementById("drawer").classList.remove("open");
    document.getElementById("overlay").style.display = "none";
}
</script>

</body>
</html>