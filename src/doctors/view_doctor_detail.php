<?php
session_start();
include "db.php";

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['doctor_id'])) {
    die("Doctor not found");
}

$doctor_id = intval($_GET['doctor_id']);

/* ================= DOCTOR ================= */
$stmt = $conn->prepare("SELECT * FROM doctor_table WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

if (!$doctor) {
    die("Doctor not found");
}

/* ================= APPOINTMENTS ================= */
$res = $conn->prepare("
    SELECT *
    FROM doctor_appointments
    WHERE doctor_id = ?
    ORDER BY appointment_date ASC, appointment_time_start ASC
");

$res->bind_param("i", $doctor_id);
$res->execute();
$appointments = $res->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<title>Doctor Schedule</title>

<meta name="viewport" content="width=device-width, initial-scale=1">

<style>

/* ===== BASE ===== */
*{margin:0;padding:0;box-sizing:border-box;font-family:Segoe UI;}
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
    font-size:25px;
    font-weight:700;
}

.header-center p{
    font-size:16px;
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

/* CONTAINER */
.container{
    max-width:800px;
    margin:auto;
    padding:20px;
}

/* DOCTOR CARD */
.card{
    background:#fff;
    padding:20px;
    border-radius:16px;
    margin-bottom:20px;
    box-shadow:0 10px 25px rgba(0,0,0,0.06);
}

.name{font-size:20px;font-weight:800;}
.meta{color:#666;font-size:13px;margin-top:5px;}

/* SLOT */
.slot{
    background:#fff;
    padding:12px 14px;
    margin-bottom:10px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-left:5px solid #0d6efd;
}

.time{font-weight:700;}
.date{font-size:12px;color:#777;}

/* STATUS */
.status{
    font-size:12px;
    font-weight:700;
    padding:6px 10px;
    border-radius:20px;
}

.scheduled{background:#fff4d6;color:#b8860b;}
.completed{background:#e8fff1;color:#198754;}
.cancelled{background:#ffe5e5;color:#dc3545;}

/* BOOK BUTTON */
.book-btn{
    background:#0d6efd;
    color:#fff;
    border:none;
    padding:8px 12px;
    border-radius:10px;
    cursor:pointer;
    font-size:12px;
}

.book-btn:hover{opacity:0.9;}

/* AVAILABLE SLOT */
.available-slot{
    background:#fff;
    padding:12px 14px;
    margin-bottom:10px;
    border-radius:12px;
    display:flex;
    justify-content:space-between;
    align-items:center;
    border-left:5px solid #198754;
}

/* POPUP */
.modal{
    display:none;
    position:fixed;
    top:0;left:0;
    width:100%;height:100%;
    background:rgba(0,0,0,0.6);
    justify-content:center;
    align-items:center;
}

.modal-content{
    background:#fff;
    padding:20px;
    border-radius:14px;
    width:90%;
    max-width:400px;
}

.modal-content h3{
    margin-bottom:12px;
}

input{
    width:100%;
    padding:10px;
    margin:6px 0;
    border-radius:8px;
    border:1px solid #ddd;
}

.submit-btn{
    width:100%;
    padding:10px;
    background:#198754;
    color:#fff;
    border:none;
    border-radius:10px;
    cursor:pointer;
    margin-top:10px;
}

.close{
    float:right;
    cursor:pointer;
    font-size:18px;
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
        <p>Doctor Schedule</p>
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

<div class="container">

<!-- DOCTOR INFO -->
<div class="card">
    <div class="name">
        <?php echo htmlspecialchars($doctor['first_name']." ".$doctor['last_name']); ?>
    </div>
    <div class="meta">
        <?php echo htmlspecialchars($doctor['specialization']); ?> •
        <?php echo htmlspecialchars($doctor['hospital_name']); ?>
    </div>
</div>

<!-- EXISTING APPOINTMENTS -->
<?php while($row = $appointments->fetch_assoc()) { ?>

<div class="slot">

    <div>
        <div class="time">
            <?php echo $row['appointment_time_start']; ?> - <?php echo $row['appointment_time_end']; ?>
        </div>
        <div class="date">
            <?php echo $row['appointment_date']; ?>
        </div>
    </div>

    <div class="status scheduled">
        Booked
    </div>

</div>

<?php } ?>

<!-- AVAILABLE SLOT (EXAMPLE) -->
<div class="available-slot">

    <div>
        <div class="time"> Create an appointment</div>
        <div class="date">Click button on right to book appointment</div>
    </div>

    <button class="book-btn" onclick="openModal()">Book</button>

</div>

</div>

<!-- POPUP -->
<div class="modal" id="modal">

    <div class="modal-content">

        <span class="close" onclick="closeModal()">×</span>

        <h3>Book Appointment</h3>

        <form method="POST" action="book_appointment.php">

            <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">

            <label>Date</label>
            <input type="date" name="date" required>

            <label>Start Time</label>
            <input type="time" name="start_time" required>

            <label>End Time</label>
            <input type="time" name="end_time" required>

            <button class="submit-btn" type="submit">Confirm Booking</button>

        </form>

    </div>

</div>

<script>

function openModal(){
    document.getElementById("modal").style.display="flex";
}

function closeModal(){
    document.getElementById("modal").style.display="none";
}

</script>

</body>
</html>