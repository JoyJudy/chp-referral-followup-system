<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_role(['admin']);

$msg = "";

if (isset($_POST['deactivate_user_id'])) {

    $userId = (int) $_POST['deactivate_user_id'];

    if ($userId === (int) $currentUser['user_id']) {
        $msg = "You can't deactivate your own account while logged in.";
    } else {

        $target = $conn->prepare("SELECT role, status FROM users WHERE user_id = ?");
        $target->bind_param("i", $userId);
        $target->execute();
        $targetRow = $target->get_result()->fetch_assoc();

        $blockedAsLastAdmin = false;
        if ($targetRow && $targetRow['role'] === 'admin' && $targetRow['status'] === 'active') {
            $adminCount = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND status = 'active'")->fetch_assoc()['c'];
            $blockedAsLastAdmin = ((int) $adminCount <= 1);
        }

        if ($blockedAsLastAdmin) {
            $msg = "Can't deactivate the last active admin account.";
        } else {
            $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ? AND status = 'active'");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $msg = "Account deactivated.";
        }
    }

} elseif (isset($_POST['reactivate_user_id'])) {

    $userId = (int) $_POST['reactivate_user_id'];
    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ? AND status = 'inactive'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $msg = "Account reactivated.";

} elseif (isset($_POST['delete_user_id'])) {

    $userId = (int) $_POST['delete_user_id'];

    if ($userId === (int) $currentUser['user_id']) {
        $msg = "You can't delete your own account while logged in.";
    } else {

        $target = $conn->prepare("SELECT role, status FROM users WHERE user_id = ?");
        $target->bind_param("i", $userId);
        $target->execute();
        $targetRow = $target->get_result()->fetch_assoc();

        $blockedAsLastAdmin = false;
        if ($targetRow && $targetRow['role'] === 'admin' && $targetRow['status'] === 'active') {
            $adminCount = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role = 'admin' AND status = 'active'")->fetch_assoc()['c'];
            $blockedAsLastAdmin = ((int) $adminCount <= 1);
        }

        if ($blockedAsLastAdmin) {
            $msg = "Can't delete the last active admin account.";
        } else {
            try {
                $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $msg = "Account deleted.";
            } catch (mysqli_sql_exception $e) {
                $msg = "Can't delete — this account has history (patients, referrals, or followups). Deactivate it instead.";
            }
        }
    }
}

$users = $conn->query("
    SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone_number, u.role, u.status,
           d.specialization, h.hospital_name
    FROM users u
    LEFT JOIN doctors d ON d.user_id = u.user_id
    LEFT JOIN hospitals h ON h.hospital_id = d.hospital_id
    WHERE u.status IN ('active', 'inactive')
    ORDER BY u.status ASC, u.first_name ASC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Accounts</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
body{background:linear-gradient(135deg,#f5f6fa,#eef1f7);color:#222;}
.header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:#fff;border-bottom:1px solid #e9e9e9;}
.logout{padding:10px 16px;border-radius:10px;background:#111;color:#fff;text-decoration:none;font-weight:600;}
.content{padding:40px;max-width:1000px;margin:auto;}
.msg{margin-bottom:16px;color:#0b6e0b;font-size:14px;}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.06);}
th, td{padding:12px 14px;text-align:left;font-size:14px;border-bottom:1px solid #eee;}
th{background:#f7f7f7;}
button{padding:8px 12px;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:12px;margin-right:4px;}
.deactivate-btn{background:#b8860b;color:#fff;}
.reactivate-btn{background:#198754;color:#fff;}
.delete-btn{background:#dc3545;color:#fff;}
.status-badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.status-active{background:#e8fff1;color:#198754;}
.status-inactive{background:#ffe5e5;color:#dc3545;}
</style>
</head>
<body>

<div class="header">
    <h1>Manage Accounts</h1>
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
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if ($users->num_rows === 0): ?>
            <tr><td colspan="7">No active or inactive accounts yet.</td></tr>
        <?php endif; ?>

        <?php while ($row = $users->fetch_assoc()): ?>
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
                <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                <td>
                    <?php if ((int) $row['user_id'] === (int) $currentUser['user_id']): ?>
                        <em>This is your account</em>
                    <?php else: ?>
                        <?php if ($row['status'] === 'active'): ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="deactivate_user_id" value="<?php echo $row['user_id']; ?>">
                                <button type="submit" class="deactivate-btn">Deactivate</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="reactivate_user_id" value="<?php echo $row['user_id']; ?>">
                                <button type="submit" class="reactivate-btn">Reactivate</button>
                            </form>
                        <?php endif; ?>
                        <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this account?');">
                            <input type="hidden" name="delete_user_id" value="<?php echo $row['user_id']; ?>">
                            <button type="submit" class="delete-btn">Delete</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

</div>

</body>
</html>
