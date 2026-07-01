<?php
session_start();

/*
|--------------------------------------------------------------------------
| INDEX ROUTER
| Decides where user goes after opening system
|--------------------------------------------------------------------------
*/

if (isset($_SESSION['user'])) {
    // Already logged in → go to role-specific dashboard
    $redirects = [
        'admin'  => 'src/admin/doctors/dashboard.php',
        'doctor' => 'src/doctor/appointments/dashboard.php',
        'chp'    => 'src/chp/patients/dashboard.php',
    ];

    $target = $redirects[$_SESSION['user']['role']] ?? 'src/auth/login.php';

    header("Location: " . $target);
    exit();
} else {
    // Not logged in → go to login page
    header("Location: src/auth/login.php");
    exit();
}
?>