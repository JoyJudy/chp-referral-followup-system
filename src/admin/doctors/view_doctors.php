<?php
session_start();
require_once __DIR__ . '/../shared/db.php';

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

$res = $conn->query("SELECT * FROM doctor_table ORDER BY first_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Doctors Directory</title>

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

/* ================= PAGE TITLE ================= */

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
    grid-template-columns:repeat(auto-fill,minmax(300px,1fr));
    gap:22px;
}

/* ================= DOCTOR CARD (NEW STYLE) ================= */

.card{
    background:#fff;
    border-radius:20px;
    padding:20px;
    box-shadow:0 12px 30px rgba(0,0,0,0.06);
    border:1px solid #f1f1f1;
    transition:0.25s ease;
    position:relative;
    overflow:hidden;
}

.card:hover{
    transform:translateY(-6px);
    box-shadow:0 18px 40px rgba(0,0,0,0.12);
}

/* top accent bar */
.card::before{
    content:"";
    position:absolute;
    top:0;
    left:0;
    width:100%;
    height:5px;
    background:linear-gradient(90deg,#0d6efd,#6f42c1);
}

/* avatar circle */
.avatar{
    width:60px;
    height:60px;
    border-radius:50%;
    background:linear-gradient(135deg,#0d6efd,#6f42c1);
    display:flex;
    align-items:center;
    justify-content:center;
    color:#fff;
    font-weight:700;
    font-size:18px;
    margin-bottom:12px;
}

/* name */
.doctor-name{
    font-size:18px;
    font-weight:800;
    margin-bottom:6px;
}

/* hospital */
.hospital{
    font-size:13px;
    color:#666;
    margin-bottom:10px;
}

/* specialization badge */
.specialization{
    display:inline-block;
    padding:7px 12px;
    border-radius:30px;
    font-size:12px;
    font-weight:700;
    color:#0d6efd;
    background:#eef4ff;
    margin-bottom:12px;
}

/* footer info line */
.info{
    font-size:12px;
    color:#888;
    margin-top:10px;
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
        <h1>Medicare System</h1>
        <p>Find Trusted Doctors</p>
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
    <h1>Doctors Directory</h1>
    <p>Browse specialists and healthcare professionals</p>
</div>

<!-- GRID -->
<div class="container">

<?php while($row = $res->fetch_assoc()) { ?>

<?php
$first = $row['first_name'] ?? '';
$last  = $row['last_name'] ?? '';
$initials = strtoupper(substr($first,0,1).substr($last,0,1));
?>

<a href="view_doctor_detail.php?doctor_id=<?php echo $row['doctor_id']; ?>" style="text-decoration:none;color:inherit;">
    <div class="card">

        <div class="avatar">
            <?php echo $initials; ?>
        </div>

        <div class="doctor-name">
            <?php echo htmlspecialchars($first . " " . $last); ?>
        </div>

        <div class="specialization">
            <?php echo htmlspecialchars($row['specialization']); ?>
        </div>

        <div class="hospital">
            🏥 <?php echo htmlspecialchars($row['hospital_name']); ?>
        </div>

        <div class="info">
            Click to view availability
        </div>

    </div>
</a>
<?php } ?>

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