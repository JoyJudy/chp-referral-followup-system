<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$msg = "";
$msgType = "error";

if (isset($_POST['send_reset'])) {

    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $msg = "Please enter your email address";
    } else {

        $stmt = $conn->prepare("SELECT user_id, first_name FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);

            // Computed in MySQL (not PHP's date()) so it can't drift from the
            // NOW() comparison in reset_password.php if PHP and MySQL are in
            // different timezones.
            $update = $conn->prepare("UPDATE users SET reset_token_hash = ?, reset_token_expires = NOW() + INTERVAL 30 MINUTE WHERE user_id = ?");
            $update->bind_param("si", $tokenHash, $user['user_id']);
            $update->execute();

            $resetLink = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
                . '/src/auth/reset_password.php?token=' . $token;

            $mailConfig = require __DIR__ . '/../config/mail.php';

            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = $mailConfig['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $mailConfig['username'];
                $mail->Password = $mailConfig['password'];
                $mail->SMTPSecure = $mailConfig['encryption'];
                $mail->Port = $mailConfig['port'];

                $mail->setFrom($mailConfig['from_email'], $mailConfig['from_name']);
                $mail->addAddress($email, $user['first_name']);

                $mail->isHTML(true);
                $mail->Subject = 'Password Reset Request';
                $mail->Body = "Hello " . htmlspecialchars($user['first_name']) . ",<br><br>"
                    . "We received a request to reset your password. This link expires in 30 minutes:<br><br>"
                    . "<a href=\"" . htmlspecialchars($resetLink) . "\">" . htmlspecialchars($resetLink) . "</a><br><br>"
                    . "If you didn't request this, you can safely ignore this email.";

                $mail->send();
            } catch (Exception $e) {
                // Fall through to the generic message below regardless of send outcome.
            }
        }

        // Always show the same message, whether or not the email matched an account.
        $msg = "If that email address is registered, a password reset link has been sent.";
        $msgType = "info";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>Forgot Password</title>

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

.info{color:#0b6e0b;margin-bottom:10px;font-size:13px;}
.error{color:#d93025;margin-bottom:10px;font-size:13px;}

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
        <p>Password Recovery</p>
    </div>
</div>

<div class="wrapper">
    <div class="card">

        <h3>Forgot Password</h3>

        <?php if (!empty($msg)): ?>
            <div class="<?php echo $msgType; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="email" name="email" placeholder="Your registered email" required>
            <button type="submit" name="send_reset">Send Reset Link</button>
        </form>

        <a class="back-link" href="login.php">Back to Login</a>

    </div>
</div>

</body>
</html>
