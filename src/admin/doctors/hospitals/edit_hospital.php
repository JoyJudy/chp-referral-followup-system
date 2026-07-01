<?php
require_once __DIR__ . '/../../../config/db.php';
require_once __DIR__ . '/../../../includes/auth_check.php';
require_role(['admin']);

if (!isset($_GET['hospital_id']) && !isset($_POST['hospital_id'])) {
    die("Hospital not found");
}

$hospitalId = (int) ($_POST['hospital_id'] ?? $_GET['hospital_id']);
$msg = "";

if (isset($_POST['update_hospital'])) {

    $name = trim($_POST['hospital_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');

    if ($name === '') {
        $msg = "Hospital name is required";
    } else {
        $stmt = $conn->prepare("UPDATE hospitals SET hospital_name = ?, location = ?, phone_number = ? WHERE hospital_id = ?");
        $stmt->bind_param("sssi", $name, $location, $phone, $hospitalId);
        $stmt->execute();
        $msg = "Hospital updated.";
    }
}

$stmt = $conn->prepare("SELECT hospital_id, hospital_name, location, phone_number FROM hospitals WHERE hospital_id = ?");
$stmt->bind_param("i", $hospitalId);
$stmt->execute();
$hospital = $stmt->get_result()->fetch_assoc();

if (!$hospital) {
    die("Hospital not found");
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Edit Hospital</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
body{background:linear-gradient(135deg,#f5f6fa,#eef1f7);color:#222;}
.wrapper{display:flex;justify-content:center;align-items:center;min-height:100vh;padding:20px;}
.card{width:100%;max-width:400px;background:#fff;padding:24px;border-radius:18px;box-shadow:0 10px 25px rgba(0,0,0,0.08);}
input{width:100%;padding:12px;margin:8px 0;border-radius:10px;border:1px solid #ddd;}
button{width:100%;padding:12px;margin-top:10px;border:none;border-radius:10px;background:#111;color:#fff;font-weight:600;cursor:pointer;}
.msg{color:#0b6e0b;font-size:13px;margin-bottom:10px;}
.back{display:block;text-align:center;margin-top:12px;color:#555;font-size:13px;}
</style>
</head>
<body>

<div class="wrapper">
    <div class="card">
        <h3>Edit Hospital</h3>

        <?php if (!empty($msg)): ?>
            <p class="msg"><?php echo htmlspecialchars($msg); ?></p>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="hospital_id" value="<?php echo $hospital['hospital_id']; ?>">
            <input type="text" name="hospital_name" value="<?php echo htmlspecialchars($hospital['hospital_name']); ?>" required>
            <input type="text" name="location" value="<?php echo htmlspecialchars($hospital['location']); ?>">
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($hospital['phone_number']); ?>">
            <button type="submit" name="update_hospital">Save Changes</button>
        </form>

        <a class="back" href="view_hospitals.php">&larr; Back to Hospitals</a>
    </div>
</div>

</body>
</html>
