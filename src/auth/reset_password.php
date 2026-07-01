<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/password_rules.php';

$msg = "";
$msgType = "error";
$tokenValid = false;
$token = $_POST['token'] ?? $_GET['token'] ?? '';

if ($token === '') {
    $msg = "Missing or invalid reset link.";
} else {

    $tokenHash = hash('sha256', $token);

    $stmt = $conn->prepare("
        SELECT user_id FROM users
        WHERE reset_token_hash = ? AND reset_token_expires > NOW()
    ");
    $stmt->bind_param("s", $tokenHash);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    if (!$user) {
        $msg = "This reset link is invalid or has expired. Please request a new one.";
    } else {

        $tokenValid = true;

        if (isset($_POST['reset_password'])) {

            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';

            if ($password !== $confirmPassword) {
                $msg = "Passwords do not match";
            } elseif (($passwordError = validate_password_strength($password)) !== null) {
                $msg = $passwordError;
            } else {

                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $update = $conn->prepare("
                    UPDATE users
                    SET password = ?, reset_token_hash = NULL, reset_token_expires = NULL
                    WHERE user_id = ?
                ");
                $update->bind_param("si", $hashedPassword, $user['user_id']);
                $update->execute();

                $msg = "Your password has been reset. You can now log in.";
                $msgType = "success";
                $tokenValid = false; // token is now consumed, hide the form
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Reset Password</title>

<style>

*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
body{background:linear-gradient(135deg,#f5f6fa,#eef1f7);color:#222;}

.header{
    display:flex;
    align-items:center;
    justify-content:space-between;
    padding:14px 18px;
    background:#fff;
    border-bottom:1px solid #e9e9e9;
    box-shadow:0 2px 10px rgba(0,0,0,0.04);
}

.logo{height:52px;border-radius:50%;box-shadow:0 6px 18px rgba(0,0,0,0.08);}

.header-center{position:absolute;left:50%;transform:translateX(-50%);text-align:center;}
.header-center h1{font-size:18px;font-weight:700;}
.header-center p{font-size:12px;color:#777;}

.wrapper{display:flex;justify-content:center;align-items:center;min-height:80vh;padding:20px;}

.card{
    width:100%;
    max-width:380px;
    background:#fff;
    padding:24px;
    border-radius:18px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
    border:1px solid #f1f1f1;
}

.card h3{margin-bottom:15px;}

input{
    width:100%;
    padding:12px;
    margin:8px 0;
    border-radius:10px;
    border:1px solid #ddd;
    background:#fafafa;
    outline:none;
}

input:focus{border-color:#111;background:#fff;}

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

.success{color:green;margin-bottom:10px;font-size:13px;}
.error{color:#d93025;margin-bottom:10px;font-size:13px;}

.hint{
    font-size:11px;
    color:#888;
    margin:-4px 0 8px;
}

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

.back-link{
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

</style>
</head>

<body>

<div class="header">
    <img class="logo" src="https://cdn-icons-png.flaticon.com/512/3774/3774299.png">
    <div class="header-center">
        <h1>Medicare System</h1>
        <p>Reset Password</p>
    </div>
</div>

<div class="wrapper">
    <div class="card">

        <h3>Reset Password</h3>

        <?php if (!empty($msg)): ?>
            <div class="<?php echo $msgType; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <?php if ($tokenValid): ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div class="password-field">
                    <input type="password" name="password" placeholder="New Password" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)">Show</button>
                </div>
                <div class="hint"><?php echo htmlspecialchars(PASSWORD_HINT); ?></div>
                <div class="password-field">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)">Show</button>
                </div>

                <button type="submit" name="reset_password">Reset Password</button>
            </form>
        <?php endif; ?>

        <a class="back-link" href="login.php">Back to Login</a>

    </div>
</div>

<script>
function togglePasswordVisibility(button) {
    const input = button.previousElementSibling;
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    button.textContent = isHidden ? 'Hide' : 'Show';
}
</script>

</body>
</html>
