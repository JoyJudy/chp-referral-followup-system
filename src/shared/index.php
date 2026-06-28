<?php
session_start();

/*
|--------------------------------------------------------------------------
| INDEX ROUTER
| Decides where user goes after opening system
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user'])) {
    // Already logged in → go to dashboard
    header("Location: dashboard.php");
    exit();
} else {
    // Not logged in → go to login page
    header("Location: login.php");
    exit();
}
?>