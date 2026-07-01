<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_role(['admin']);

$msg = "";

if (isset($_POST['approve_user_id'])) {

    $userId = (int) $_POST['approve_user_id'];
    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ? AND status = 'pending'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $msg = "Account approved.";

} elseif (isset($_POST['reject_user_id'])) {

    $userId = (int) $_POST['reject_user_id'];

    $check = $conn->prepare("SELECT status FROM users WHERE user_id = ?");
    $check->bind_param("i", $userId);
    $check->execute();
    $statusRow = $check->get_result()->fetch_assoc();

    if ($statusRow && $statusRow['status'] === 'pending') {

        $deleteDoctor = $conn->prepare("DELETE FROM doctors WHERE user_id = ?");
        $deleteDoctor->bind_param("i", $userId);
        $deleteDoctor->execute();

        $deleteUser = $conn->prepare("DELETE FROM users WHERE user_id = ? AND status = 'pending'");
        $deleteUser->bind_param("i", $userId);
        $deleteUser->execute();

        $msg = "Account rejected and removed.";
    } else {
        $msg = "Account is no longer pending.";
    }
}

$pending = $conn->query("
    SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone_number, u.role,
           d.specialization, h.hospital_name
    FROM users u
    LEFT JOIN doctors d ON d.user_id = u.user_id
    LEFT JOIN hospitals h ON h.hospital_id = d.hospital_id
    WHERE u.status = 'pending'
    ORDER BY u.created_at ASC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Pending Approvals</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
body{background:linear-gradient(135deg,#f5f6fa,#eef1f7);color:#222;}
.header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:#fff;border-bottom:1px solid #e9e9e9;}
.logout{padding:10px 16px;border-radius:10px;background:#111;color:#fff;text-decoration:none;font-weight:600;}
.content{padding:40px;max-width:900px;margin:auto;}
.msg{margin-bottom:16px;color:#0b6e0b;font-size:14px;}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.06);}
th, td{padding:12px 14px;text-align:left;font-size:14px;border-bottom:1px solid #eee;}
th{background:#f7f7f7;}
.empty{padding:20px;color:#777;}
button{padding:8px 14px;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:13px;}
.approve-btn{background:#198754;color:#fff;margin-right:6px;}
.reject-btn{background:#dc3545;color:#fff;}
</style>
</head>
<body>

<div class="header">
    <h1>Pending Account Approvals</h1>
    <a class="logout" href="/src/auth/logout.php">Logout</a>
</div>

<div class="content">

    <p><a href="/src/admin/doctors/dashboard.php">&larr; Back to dashboard</a></p>

    <?php if (!empty($msg)): ?>
        <p class="msg"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Details</th>
            <th>Action</th>
        </tr>

        <?php if ($pending->num_rows === 0): ?>
            <tr><td colspan="6" class="empty">No pending accounts.</td></tr>
        <?php endif; ?>

        <?php while ($row = $pending->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                <td><?php echo htmlspecialchars($row['role']); ?></td>
                <td>
                    <?php if ($row['role'] === 'doctor'): ?>
                        <?php echo htmlspecialchars(($row['hospital_name'] ?? '') . ' — ' . ($row['specialization'] ?? '')); ?>
                    <?php else: ?>
                        &mdash;
                    <?php endif; ?>
                </td>
                <td>
                    <form method="POST" style="display:inline;">
                        <input type="hidden" name="approve_user_id" value="<?php echo $row['user_id']; ?>">
                        <button type="submit" class="approve-btn">Approve</button>
                    </form>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Reject and delete this account?');">
                        <input type="hidden" name="reject_user_id" value="<?php echo $row['user_id']; ?>">
                        <button type="submit" class="reject-btn">Reject</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

</div>

</body>
</html>
