<?php
require_once __DIR__ . '/../shared/db.php';

$msg = "";

if (isset($_POST['register'])) {

    $fn = $_POST['firstname'];
    $ln = $_POST['lastname'];
    $nid = $_POST['national_id_number'];
    $email = $_POST['email'];
    $phone = $_POST['user_phonenumber'];

    $check = $conn->prepare("SELECT id FROM users WHERE national_id_number=? OR user_phonenumber=?");
    $check->bind_param("ss", $nid, $phone);
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        $msg = "User already exists";
    } else {

        $stmt = $conn->prepare("
            INSERT INTO users(firstname, lastname, national_id_number, email, user_phonenumber)
            VALUES(?,?,?,?,?)
        ");

        $stmt->bind_param("sssss", $fn, $ln, $nid, $email, $phone);
        $stmt->execute();

        $msg = "Registration successful";
    }
}
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
input{
    width:100%;
    padding:12px;
    margin:8px 0;
    border-radius:10px;
    border:1px solid #ddd;
    background:#fafafa;
    transition:0.2s;
}

input:focus{
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
<p class="<?php echo ($msg=="Registration successful") ? "success" : "error"; ?>">
    <?php echo $msg; ?>
</p>

<form method="POST">

<input name="firstname" placeholder="First Name" required>
<input name="lastname" placeholder="Last Name" required>
<input name="national_id_number" placeholder="National ID" required>
<input name="email" placeholder="Email">
<input name="user_phonenumber" placeholder="Phone" required>

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

</body>
</html>