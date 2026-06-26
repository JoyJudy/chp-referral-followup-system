<?php
session_start();
include "db.php";

$msg = "";
$loginData = null;

if(isset($_POST['login'])){

    $nid = trim($_POST['national_id_number']);
    $phone = trim($_POST['user_phonenumber']);

    $stmt = $conn->prepare("
        SELECT *
        FROM users
        WHERE national_id_number = ?
        AND user_phonenumber = ?
        LIMIT 1
    ");

    $stmt->bind_param("ss", $nid, $phone);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){

        $user = $result->fetch_assoc();

        $_SESSION['user'] = $user;

        $loginData = $user;

    }else{

        $msg = "Invalid National ID or Phone Number";

    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Login</title>

<style>

/* ================= RESET ================= */

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

/* ================= WRAPPER ================= */

.login-wrapper{
    display:flex;
    justify-content:center;
    align-items:center;
    min-height:80vh;
    padding:20px;
}

/* ================= CARD ================= */

.card{
    width:100%;
    max-width:380px;
    background:#fff;
    padding:24px;
    border-radius:18px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
    border:1px solid #f1f1f1;
}

.card h3{
    margin-bottom:15px;
}

/* ================= INPUT ================= */

input{
    width:100%;
    padding:12px;
    margin:8px 0;
    border-radius:10px;
    border:1px solid #ddd;
    background:#fafafa;
    outline:none;
    transition:0.2s;
}

input:focus{
    border-color:#111;
    background:#fff;
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
}

button:hover{
    opacity:.95;
}

/* ================= ERROR ================= */

.error{
    color:#d93025;
    margin-bottom:10px;
    font-size:13px;
}

/* ================= REGISTER ================= */

.register-btn{
    display:block;
    text-align:center;
    margin-top:12px;
    padding:12px;
    border-radius:10px;
    background:#f3f3f3;
    color:#111;
    text-decoration:none;
    font-weight:600;
}

.register-btn:hover{
    background:#e5e5e5;
}

.small-text{
    text-align:center;
    margin-top:10px;
    color:#777;
    font-size:12px;
}

</style>
</head>

<body>

<div class="header">

    <img
        class="logo"
        src="https://cdn-icons-png.flaticon.com/512/3774/3774299.png"
    >

    <div class="header-center">
        <h1>Medicare System</h1>
        <p>Secure Login</p>
    </div>

</div>

<div class="login-wrapper">

    <div class="card">

        <h3>Login</h3>

        <?php if(!empty($msg)): ?>
            <div class="error">
                <?php echo $msg; ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <input
                type="text"
                name="national_id_number"
                placeholder="National ID Number"
                required
            >

            <input
                type="text"
                name="user_phonenumber"
                placeholder="Phone Number"
                required
            >

            <button
                type="submit"
                name="login"
            >
                Login
            </button>

        </form>

        <a
            class="register-btn"
            href="register.php"
        >
            No account? Register
        </a>

        <div class="small-text">
            Access your healthcare dashboard securely
        </div>

    </div>

</div>

<?php if($loginData): ?>

<script>

let userData =
<?php echo json_encode($loginData); ?>;

// Save every database field

for(let key in userData){

    localStorage.setItem(
        key,
        userData[key]
    );

}

// Additional fields

localStorage.setItem(
    "fullname",
    userData.firstname + " " + userData.lastname
);

localStorage.setItem(
    "logged_in",
    "1"
);

// Redirect based on role

if(userData.role === "admin"){

    window.location.href = "admin/dashboard.php";

}
else if(userData.role === "doctor"){

    window.location.href = "doctor/dashboard.php";

}
else if(userData.role === "chp"){

    window.location.href = "chp/dashboard.php";

}
else{

    window.location.href = "dashboard.php";

}

</script>

<?php endif; ?>

</body>
</html>
