<?php
require_once __DIR__ . '/../../includes/auth_check.php';
require_role(['chp', 'admin']);
?>

<!DOCTYPE html>
<html>
<head>
<title>Add Patient</title>

<style>

/* ===== RESET ===== */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
    font-family:"Segoe UI", sans-serif;
}

body{
    background:linear-gradient(135deg,#f5f6fa,#eef1f7);
}

/* ===== HEADER ===== */
.header{
    padding:14px 18px;
    background:#fff;
    border-bottom:1px solid #e9e9e9;
    box-shadow:0 2px 10px rgba(0,0,0,0.04);
    text-align:center;
}

.header h2{
    font-size:20px;
}

/* ===== FORM CONTAINER ===== */
.container{
    max-width:600px;
    margin:30px auto;
    background:#fff;
    padding:25px;
    border-radius:16px;
    box-shadow:0 10px 25px rgba(0,0,0,0.08);
}

/* ===== INPUTS ===== */
label{
    display:block;
    margin-top:15px;
    font-weight:600;
    font-size:13px;
    color:#333;
}

input, select, textarea{
    width:100%;
    padding:12px;
    margin-top:6px;
    border:1px solid #ddd;
    border-radius:10px;
    outline:none;
    font-size:14px;
    transition:0.2s;
}

input:focus, select:focus, textarea:focus{
    border-color:#4a90e2;
    box-shadow:0 0 0 3px rgba(74,144,226,0.15);
}

/* ===== BUTTON ===== */
button{
    width:100%;
    margin-top:20px;
    padding:12px;
    background:#111;
    color:#fff;
    border:none;
    border-radius:10px;
    font-size:15px;
    cursor:pointer;
    transition:0.2s;
}

button:hover{
    background:#333;
}

/* ===== GRID ===== */
.row{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px;
}

@media(max-width:600px){
    .row{
        grid-template-columns:1fr;
    }
}

.back{
    display:inline-block;
    margin-top:15px;
    text-decoration:none;
    color:#4a90e2;
    font-size:13px;
}

</style>
</head>

<body>

<div class="header">
    <h2>➕ Add New Patient</h2>
</div>

<div class="container">

<form method="POST" action="save_patient.php">

    <div class="row">

        <div>
            <label>First Name</label>
            <input type="text" name="firstname" required>
        </div>

        <div>
            <label>Last Name</label>
            <input type="text" name="lastname" required>
        </div>

    </div>

    <div class="row">

        <div>
            <label>Phone Number</label>
            <input type="text" name="phone">
        </div>

        <div>
            <label>Gender</label>
            <select name="gender">
                <option value="">Select</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
        </div>

    </div>

    <label>Date of Birth</label>
    <input type="date" name="date_of_birth">

    <label>Address</label>
    <textarea name="address" rows="3"></textarea>

    <button type="submit">Save Patient</button>

</form>

<a class="back" href="dashboard.php">← Back to Dashboard</a>

</div>

</body>
</html>