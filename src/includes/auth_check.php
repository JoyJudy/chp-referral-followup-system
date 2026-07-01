<?php
/* ===== START SESSION ===== */
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/* ===== LOGIN CHECK ===== */
if (!isset($_SESSION['user'])) {
    header("Location: /src/auth/login.php");
    exit();
}

$currentUser = $_SESSION['user'];

/**
 * Call from a dashboard page to restrict it to specific roles.
 * Example: require_role(['admin']);
 */
function require_role(array $allowedRoles): void
{
    global $currentUser;

    if (!in_array($currentUser['role'], $allowedRoles, true)) {
        header("Location: /src/auth/login.php");
        exit();
    }
}
?>
