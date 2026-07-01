<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin']);

$usersByRole = $conn->query("SELECT role, COUNT(*) AS c FROM users WHERE status = 'active' GROUP BY role");
$pendingCount = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status = 'pending'")->fetch_assoc()['c'];
$hospitalCount = $conn->query("SELECT COUNT(*) AS c FROM hospitals")->fetch_assoc()['c'];
$patientCount = $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'];
$appointmentCount = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc()['c'];
$referralsByStatus = $conn->query("SELECT status, COUNT(*) AS c FROM referrals GROUP BY status");
?>

<!DOCTYPE html>
<html>
<head>
<title>Analytics</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
body{background:linear-gradient(135deg,#f5f6fa,#eef1f7);color:#222;}
.header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:#fff;border-bottom:1px solid #e9e9e9;}
.logout{padding:10px 16px;border-radius:10px;background:#111;color:#fff;text-decoration:none;font-weight:600;}
.content{padding:40px;max-width:900px;margin:auto;}
.section{background:#fff;border-radius:16px;padding:24px;margin-bottom:20px;box-shadow:0 10px 25px rgba(0,0,0,0.06);}
.section h3{margin-bottom:14px;font-size:16px;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;}
.stat{background:#f7f7f7;border-radius:12px;padding:16px;text-align:center;}
.stat .num{font-size:26px;font-weight:800;}
.stat .label{font-size:12px;color:#666;margin-top:4px;text-transform:capitalize;}
</style>
</head>
<body>

<div class="header">
    <h1>Analytics</h1>
    <a class="logout" href="/src/auth/logout.php">Logout</a>
</div>

<div class="content">

    <p><a href="/src/admin/doctors/dashboard.php">&larr; Back to dashboard</a></p>

    <div class="section">
        <h3>System Snapshot</h3>
        <div class="stat-grid">
            <?php while ($row = $usersByRole->fetch_assoc()): ?>
                <div class="stat">
                    <div class="num"><?php echo (int) $row['c']; ?></div>
                    <div class="label">Active <?php echo htmlspecialchars($row['role']); ?>s</div>
                </div>
            <?php endwhile; ?>
            <div class="stat">
                <div class="num"><?php echo (int) $pendingCount; ?></div>
                <div class="label">Pending Approvals</div>
            </div>
            <div class="stat">
                <div class="num"><?php echo (int) $hospitalCount; ?></div>
                <div class="label">Hospitals</div>
            </div>
            <div class="stat">
                <div class="num"><?php echo (int) $patientCount; ?></div>
                <div class="label">Patients</div>
            </div>
            <div class="stat">
                <div class="num"><?php echo (int) $appointmentCount; ?></div>
                <div class="label">Appointments</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Referral Pipeline</h3>
        <div class="stat-grid">
            <?php if ($referralsByStatus->num_rows === 0): ?>
                <p style="color:#777;">No referrals yet.</p>
            <?php endif; ?>
            <?php while ($row = $referralsByStatus->fetch_assoc()): ?>
                <div class="stat">
                    <div class="num"><?php echo (int) $row['c']; ?></div>
                    <div class="label"><?php echo htmlspecialchars($row['status']); ?></div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

</div>

</body>
</html>
