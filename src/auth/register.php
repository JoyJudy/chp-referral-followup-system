<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/password_rules.php';

$msg = "";
$msgType = "error";

$allowedRoles = ['admin', 'chp', 'doctor'];

if (isset($_POST['register'])) {

    $fn = trim($_POST['firstname'] ?? '');
    $ln = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['user_phonenumber'] ?? '');
    $role = $_POST['role'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $hospitalId = $_POST['hospital_id'] ?? '';
    $specialization = trim($_POST['specialization'] ?? '');

    if ($fn === '' || $ln === '' || $email === '' || $phone === '' || $role === '' || $password === '') {
        $msg = "All fields are required";
    } elseif (!in_array($role, $allowedRoles, true)) {
        $msg = "Invalid role selected";
    } elseif ($role === 'doctor' && ($hospitalId === '' || $specialization === '')) {
        $msg = "Facility and specialization are required for doctors";
    } elseif ($password !== $confirmPassword) {
        $msg = "Passwords do not match";
    } elseif (($passwordError = validate_password_strength($password)) !== null) {
        $msg = $passwordError;
    } else {

        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone_number = ?");
        $check->bind_param("ss", $email, $phone);
        $check->execute();
        $res = $check->get_result();

        if ($res->num_rows > 0) {
            $msg = "An account with that email or phone number already exists";
        } else {

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO users(first_name, last_name, email, phone_number, password, role, status)
                VALUES(?, ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->bind_param("ssssss", $fn, $ln, $email, $phone, $hashedPassword, $role);
            $stmt->execute();

            if ($role === 'doctor') {
                $newUserId = $conn->insert_id;
                $hospitalIdInt = (int) $hospitalId;
                $doctorStmt = $conn->prepare("INSERT INTO doctors(user_id, hospital_id, specialization) VALUES (?, ?, ?)");
                $doctorStmt->bind_param("iis", $newUserId, $hospitalIdInt, $specialization);
                $doctorStmt->execute();
            }

            $msg = "Registration submitted. Your account is pending admin approval before you can log in.";
            $msgType = "success";
        }
    }
}

$hospitals = $conn->query("SELECT hospital_id, hospital_name FROM hospitals ORDER BY hospital_name ASC");
?>

<!DOCTYPE html>
<html>
<head>
<title>Register</title>

<style>

/* ================= GLOBAL ================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:"Segoe UI", sans-serif;
}

body{
    background:linear-gradient(135deg,#f5f6fa,#eef1f7);
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

/* ================= WRAPPER ================= */
.wrapper{
    min-height:85vh;
    display:flex;
    justify-content:center;
    align-items:center;
    padding:20px;
}

/* ================= CARD ================= */
.card{
    width:100%;
    max-width:380px;
    background:#fff;
    padding:25px;
    border-radius:18px;
    box-shadow:0 12px 30px rgba(0,0,0,0.08);
    border:1px solid #f1f1f1;
}

/* ================= INPUT ================= */
input, select{
    width:100%;
    padding:12px;
    margin:8px 0;
    border-radius:10px;
    border:1px solid #ddd;
    background:#fafafa;
    transition:0.2s;
    font-size:14px;
}

input:focus, select:focus{
    border-color:#111;
    background:#fff;
    outline:none;
}

/* ================= BUTTON ================= */
button{
    width:100%;
    padding:12px;
    margin-top:10px;
    border:none;
    border-radius:10px;
    background:linear-gradient(135deg,#111,#333);
    color:#fff;
    font-weight:600;
    cursor:pointer;
    transition:0.2s;
}

button:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 20px rgba(0,0,0,0.15);
}

/* ================= MESSAGE ================= */
.success{
    font-size:13px;
    color:green;
    margin-bottom:8px;
}

.error{
    font-size:13px;
    color:red;
    margin-bottom:8px;
}

/* ================= LOGIN LINK ================= */
.login-link{
    display:block;
    text-align:center;
    margin-top:15px;
    padding:12px;
    border-radius:10px;
    background:#f1f1f1;
    color:#111;
    text-decoration:none;
    font-weight:600;
    transition:0.2s;
}

.login-link:hover{
    background:#e2e2e2;
}

.small-text{
    text-align:center;
    font-size:12px;
    color:#777;
    margin-top:10px;
}

.hint{
    font-size:11px;
    color:#888;
    margin:-4px 0 8px;
}

/* ================= PASSWORD TOGGLE ================= */

.password-field{
    position:relative;
}

.password-field input{
    padding-right:56px;
}

.toggle-password{
    position:absolute;
    right:6px;
    top:50%;
    transform:translateY(-50%);
    width:auto;
    margin:0;
    padding:6px 8px;
    background:none;
    border:none;
    color:#555;
    font-size:12px;
    font-weight:600;
    cursor:pointer;
}

.toggle-password:hover{
    color:#111;
}

</style>

</head>

<body>

<!-- HEADER -->
<div class="header">

    <img class="logo" src="https://cdn-icons-png.flaticon.com/512/3774/3774299.png">

    <div class="header-center">
        <h1>Medicare System</h1>
        <p>Create Account</p>
    </div>

</div>

<!-- WRAPPER -->
<div class="wrapper">

<div class="card">

<h3>Create Account</h3>

<!-- MESSAGE -->
<?php if (!empty($msg)): ?>
<p class="<?php echo $msgType; ?>">
    <?php echo htmlspecialchars($msg); ?>
</p>
<?php endif; ?>

<form method="POST">

<input name="firstname" placeholder="First Name" value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>" required>
<input name="lastname" placeholder="Last Name" value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>" required>
<input type="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
<input name="user_phonenumber" placeholder="Phone" value="<?php echo htmlspecialchars($_POST['user_phonenumber'] ?? ''); ?>" required>

<select name="role" id="role" onchange="toggleDoctorFields(this.value)" required>
    <option value="" disabled <?php echo empty($_POST['role']) ? 'selected' : ''; ?>>Select Role</option>
    <option value="chp" <?php echo (($_POST['role'] ?? '') === 'chp') ? 'selected' : ''; ?>>Community Health Promoter</option>
    <option value="doctor" <?php echo (($_POST['role'] ?? '') === 'doctor') ? 'selected' : ''; ?>>Doctor</option>
    <option value="admin" <?php echo (($_POST['role'] ?? '') === 'admin') ? 'selected' : ''; ?>>Admin</option>
</select>

<div id="doctor-fields" style="display:<?php echo (($_POST['role'] ?? '') === 'doctor') ? 'block' : 'none'; ?>;">

    <select name="hospital_id">
        <option value="" disabled <?php echo empty($_POST['hospital_id']) ? 'selected' : ''; ?>>Select Facility</option>
        <?php while ($hospital = $hospitals->fetch_assoc()): ?>
            <option value="<?php echo $hospital['hospital_id']; ?>" <?php echo (($_POST['hospital_id'] ?? '') == $hospital['hospital_id']) ? 'selected' : ''; ?>>
                <?php echo htmlspecialchars($hospital['hospital_name']); ?>
            </option>
        <?php endwhile; ?>
    </select>

    <input name="specialization" placeholder="Specialization (e.g. General Practice)" value="<?php echo htmlspecialchars($_POST['specialization'] ?? ''); ?>">

</div>

<div class="password-field">
    <input type="password" name="password" placeholder="Password" required>
    <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)">Show</button>
</div>
<div class="hint"><?php echo htmlspecialchars(PASSWORD_HINT); ?></div>
<div class="password-field">
    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
    <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)">Show</button>
</div>

<button name="register">Register</button>

</form>

<!-- LOGIN LINK -->
<a class="login-link" href="login.php">
    Already have an account? Login here
</a>

<div class="small-text">
    Secure healthcare access system
</div>

</div>

</div>

<script>
function togglePasswordVisibility(button) {
    const input = button.previousElementSibling;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    button.textContent = isHidden ? 'Hide' : 'Show';
}

function toggleDoctorFields(role) {
    document.getElementById('doctor-fields').style.display = (role === 'doctor') ? 'block' : 'none';
}
</script>

</body>
</html>
