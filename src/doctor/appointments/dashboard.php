<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_role(['doctor']);
?>
<!DOCTYPE html>
<html>
<head>
<title>Doctor Dashboard</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
body{background:linear-gradient(135deg,#f5f6fa,#eef1f7);color:#222;}
.header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:#fff;border-bottom:1px solid #e9e9e9;}
.logout{padding:10px 16px;border-radius:10px;background:#111;color:#fff;text-decoration:none;font-weight:600;}
.content{padding:40px;}
</style>
</head>
<body>

<div class="header">
    <h1>Doctor Dashboard</h1>
    <a class="logout" href="/src/auth/logout.php">Logout</a>
</div>

<div class="content">
    <h2>Welcome, Dr. <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h2>
    <p>Role: <?php echo htmlspecialchars($currentUser['role']); ?></p>
</div>

</body>
</html>
