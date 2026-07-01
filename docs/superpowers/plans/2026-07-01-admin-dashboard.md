# Admin Dashboard Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Give the admin role a working dashboard hub covering account lifecycle management (deactivate/reactivate/delete already-approved accounts), hospital management (add/edit), and system analytics (snapshot counts + referral pipeline breakdown) — on top of the account-approval flow already built.

**Architecture:** Plain PHP pages under `src/admin/**`, guarded by the existing `require_role(['admin'])` helper in `src/includes/auth_check.php`. Each admin capability is its own self-contained file (matches the existing pattern: `pending_users.php` is already its own page). No framework, no routing layer — direct mysqli queries against the live schema, same as every other page in this codebase.

**Tech Stack:** PHP 8.2 + mysqli (raw prepared statements), MySQL/MariaDB. No PHPUnit/test runner is wired up for these plain-PHP pages (phpunit.dist.xml only covers the untouched CodeIgniter skeleton in `app/`), so "tests" in this plan means: `php -l` for syntax, then live verification via `curl` against `php -S localhost:8123` hitting the real dev database — the same method used for every prior feature in this project. Clean up test data after each verification.

## Global Constraints

- All new/modified admin pages must call `require_once __DIR__ . '/.../includes/auth_check.php'; require_role(['admin']);` at the top, matching the existing pattern in every other `src/admin/**` file.
- All SQL must use prepared statements with bound parameters — no string-interpolated queries (matches existing codebase convention).
- All user-supplied output must go through `htmlspecialchars()` before being echoed into HTML (matches existing codebase convention).
- Dev DB credentials (already in `src/config/db.php`): host `localhost`, user `root`, pass `root123`, db `chp-referral-followup-system`, port `3306`.
- mysqli throws `mysqli_sql_exception` on error by default in this environment (PHP 8.1+ default driver behavior) — any query that might legitimately fail (e.g. a delete blocked by an FK) must be wrapped in `try/catch (mysqli_sql_exception $e)`.

---

### Task 1: Add `inactive` status and update login messaging

**Files:**
- Modify: `src/auth/login.php` (the block that currently reads `if($user && password_verify(...) && $user['status'] !== 'active'){ ... }`)
- No new files.

**Interfaces:**
- Consumes: `users.status` column, currently `ENUM('pending','active')`.
- Produces: `users.status` becomes `ENUM('pending','active','inactive')`. Later tasks (Task 2) rely on `'inactive'` being a valid value to set.

- [ ] **Step 1: Widen the status enum in the live DB**

Run this exact command (adjust nothing — these are the real dev DB credentials already in use throughout this project):

```bash
php -r "
\$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
\$conn->query(\"ALTER TABLE users MODIFY status ENUM('pending','active','inactive') NOT NULL DEFAULT 'pending'\");
echo \$conn->error ?: 'ALTER OK';
"
```

Expected output: `ALTER OK`

- [ ] **Step 2: Verify the enum change**

```bash
php -r "
\$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
\$res = \$conn->query('DESCRIBE users');
while(\$row = \$res->fetch_assoc()) { if (\$row['Field']==='status') print_r(\$row); }
"
```

Expected: `Type` shows `enum('pending','active','inactive')`.

- [ ] **Step 3: Read the current login.php block to get exact context**

Read `src/auth/login.php` and find this exact existing block:

```php
    if($user && password_verify($password, $user['password']) && $user['status'] !== 'active'){

        $msg = "Your account is pending admin approval.";

    }elseif($user && password_verify($password, $user['password'])){
```

- [ ] **Step 4: Replace it with status-specific messaging**

```php
    if($user && password_verify($password, $user['password']) && $user['status'] !== 'active'){

        if ($user['status'] === 'inactive') {
            $msg = "Your account has been deactivated. Contact an admin.";
        } else {
            $msg = "Your account is pending admin approval.";
        }

    }elseif($user && password_verify($password, $user['password'])){
```

- [ ] **Step 5: Lint**

```bash
php -l src/auth/login.php
```

Expected: `No syntax errors detected in src/auth/login.php`

- [ ] **Step 6: Verify live — deactivate a test user manually and confirm the message**

```bash
php -S localhost:8123 > /tmp/php_server.log 2>&1 &
sleep 1

# Create + approve a throwaway test user directly (fast path, no need to go through the full registration UI for this check)
php -r "
\$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
\$hash = password_hash('Str0ng!Pass', PASSWORD_DEFAULT);
\$stmt = \$conn->prepare(\"INSERT INTO users(first_name,last_name,email,phone_number,password,role,status) VALUES('Test','Deactivated','test.deact@example.com','0799999999',?,'chp','inactive')\");
\$stmt->bind_param('s', \$hash);
\$stmt->execute();
"

curl -s -X POST http://localhost:8123/src/auth/login.php \
  --data-urlencode "login=1" --data-urlencode "user_phonenumber=0799999999" --data-urlencode "password=Str0ng!Pass" \
  | grep -oE "deactivated"

# Clean up
php -r "
\$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
\$conn->query(\"DELETE FROM users WHERE phone_number='0799999999'\");
"
kill %1 2>/dev/null
```

Expected: the grep line prints `deactivated`.

- [ ] **Step 7: Commit**

```bash
git add src/auth/login.php
git commit -m "Add inactive account status with distinct login message"
```

---

### Task 2: Account management page (deactivate / reactivate / delete)

**Files:**
- Create: `src/admin/users/manage_users.php`
- No other files touched.

**Interfaces:**
- Consumes: `require_role()` from `src/includes/auth_check.php` (Task's guard); `users.status` enum from Task 1 (`'pending' | 'active' | 'inactive'`); `doctors`/`hospitals` tables (existing schema) for showing doctor detail.
- Produces: nothing consumed by later tasks — this is a standalone leaf page. Task 5 links to it by path `/src/admin/users/manage_users.php`.

- [ ] **Step 1: Write the file**

```php
<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/auth_check.php';
require_role(['admin']);

$msg = "";

if (isset($_POST['deactivate_user_id'])) {

    $userId = (int) $_POST['deactivate_user_id'];
    $stmt = $conn->prepare("UPDATE users SET status = 'inactive' WHERE user_id = ? AND status = 'active'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $msg = "Account deactivated.";

} elseif (isset($_POST['reactivate_user_id'])) {

    $userId = (int) $_POST['reactivate_user_id'];
    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ? AND status = 'inactive'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $msg = "Account reactivated.";

} elseif (isset($_POST['delete_user_id'])) {

    $userId = (int) $_POST['delete_user_id'];

    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $msg = "Account deleted.";
    } catch (mysqli_sql_exception $e) {
        $msg = "Can't delete — this account has history (patients, referrals, or followups). Deactivate it instead.";
    }
}

$users = $conn->query("
    SELECT u.user_id, u.first_name, u.last_name, u.email, u.phone_number, u.role, u.status,
           d.specialization, h.hospital_name
    FROM users u
    LEFT JOIN doctors d ON d.user_id = u.user_id
    LEFT JOIN hospitals h ON h.hospital_id = d.hospital_id
    WHERE u.status IN ('active', 'inactive')
    ORDER BY u.status ASC, u.first_name ASC
");
?>

<!DOCTYPE html>
<html>
<head>
<title>Manage Accounts</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
body{background:linear-gradient(135deg,#f5f6fa,#eef1f7);color:#222;}
.header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:#fff;border-bottom:1px solid #e9e9e9;}
.logout{padding:10px 16px;border-radius:10px;background:#111;color:#fff;text-decoration:none;font-weight:600;}
.content{padding:40px;max-width:1000px;margin:auto;}
.msg{margin-bottom:16px;color:#0b6e0b;font-size:14px;}
table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 6px 18px rgba(0,0,0,0.06);}
th, td{padding:12px 14px;text-align:left;font-size:14px;border-bottom:1px solid #eee;}
th{background:#f7f7f7;}
button{padding:8px 12px;border:none;border-radius:8px;cursor:pointer;font-weight:600;font-size:12px;margin-right:4px;}
.deactivate-btn{background:#b8860b;color:#fff;}
.reactivate-btn{background:#198754;color:#fff;}
.delete-btn{background:#dc3545;color:#fff;}
.status-badge{padding:4px 10px;border-radius:20px;font-size:11px;font-weight:700;}
.status-active{background:#e8fff1;color:#198754;}
.status-inactive{background:#ffe5e5;color:#dc3545;}
</style>
</head>
<body>

<div class="header">
    <h1>Manage Accounts</h1>
    <a class="logout" href="/src/auth/logout.php">Logout</a>
</div>

<div class="content">

    <p><a href="/src/admin/doctors/dashboard.php">&larr; Back to dashboard</a></p>

    <?php if (!empty($msg)): ?>
        <p class="msg"><?php echo htmlspecialchars($msg); ?></p>
    <?php endif; ?>

    <table>
        <tr>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Role</th>
            <th>Details</th>
            <th>Status</th>
            <th>Action</th>
        </tr>

        <?php if ($users->num_rows === 0): ?>
            <tr><td colspan="7">No active or inactive accounts yet.</td></tr>
        <?php endif; ?>

        <?php while ($row = $users->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                <td><?php echo htmlspecialchars($row['role']); ?></td>
                <td>
                    <?php if ($row['role'] === 'doctor'): ?>
                        <?php echo htmlspecialchars($row['hospital_name'] . ' — ' . $row['specialization']); ?>
                    <?php else: ?>
                        &mdash;
                    <?php endif; ?>
                </td>
                <td><span class="status-badge status-<?php echo $row['status']; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                <td>
                    <?php if ($row['status'] === 'active'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="deactivate_user_id" value="<?php echo $row['user_id']; ?>">
                            <button type="submit" class="deactivate-btn">Deactivate</button>
                        </form>
                    <?php else: ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="reactivate_user_id" value="<?php echo $row['user_id']; ?>">
                            <button type="submit" class="reactivate-btn">Reactivate</button>
                        </form>
                    <?php endif; ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Permanently delete this account?');">
                        <input type="hidden" name="delete_user_id" value="<?php echo $row['user_id']; ?>">
                        <button type="submit" class="delete-btn">Delete</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>

</div>

</body>
</html>
```

- [ ] **Step 2: Lint**

```bash
php -l src/admin/users/manage_users.php
```

Expected: `No syntax errors detected in src/admin/users/manage_users.php`

- [ ] **Step 3: Verify live — deactivate, reactivate, and both delete outcomes**

```bash
php -S localhost:8123 > /tmp/php_server.log 2>&1 &
sleep 1
rm -f /tmp/admin_cookies.txt

# Log in as the existing seeded admin (phone 0700000000 / password Qwerty1!)
curl -s -c /tmp/admin_cookies.txt -X POST http://localhost:8123/src/auth/login.php \
  --data-urlencode "login=1" --data-urlencode "user_phonenumber=0700000000" --data-urlencode "password=Qwerty1!" -o /dev/null

# Create two throwaway active users directly: one with no history (deletable), one with a patient tied to it (not deletable)
php -r "
\$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
\$hash = password_hash('Str0ng!Pass', PASSWORD_DEFAULT);
\$stmt = \$conn->prepare(\"INSERT INTO users(first_name,last_name,email,phone_number,password,role,status) VALUES('NoHistory','Test','nohist@example.com','0788000001',?,'chp','active')\");
\$stmt->bind_param('s', \$hash); \$stmt->execute();
echo 'no_history_id=' . \$conn->insert_id . PHP_EOL;

\$stmt2 = \$conn->prepare(\"INSERT INTO users(first_name,last_name,email,phone_number,password,role,status) VALUES('HasHistory','Test','hashist@example.com','0788000002',?,'chp','active')\");
\$stmt2->bind_param('s', \$hash); \$stmt2->execute();
\$hasHistoryId = \$conn->insert_id;
echo 'has_history_id=' . \$hasHistoryId . PHP_EOL;

\$stmt3 = \$conn->prepare(\"INSERT INTO patients(first_name,last_name,registered_by) VALUES('Dummy','Patient', ?)\");
\$stmt3->bind_param('i', \$hasHistoryId); \$stmt3->execute();
"

# Deactivate the no-history user, confirm status flips
curl -s -b /tmp/admin_cookies.txt -X POST http://localhost:8123/src/admin/users/manage_users.php \
  --data-urlencode "deactivate_user_id=$(php -r "
      \$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
      echo \$conn->query(\"SELECT user_id FROM users WHERE phone_number='0788000001'\")->fetch_assoc()['user_id'];
  ")" | grep -oE "deactivated"

# Reactivate it back
NOHIST_ID=$(php -r "
    \$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
    echo \$conn->query(\"SELECT user_id FROM users WHERE phone_number='0788000001'\")->fetch_assoc()['user_id'];
")
curl -s -b /tmp/admin_cookies.txt -X POST http://localhost:8123/src/admin/users/manage_users.php \
  --data-urlencode "reactivate_user_id=$NOHIST_ID" | grep -oE "reactivated"

# Delete the no-history user — should succeed
curl -s -b /tmp/admin_cookies.txt -X POST http://localhost:8123/src/admin/users/manage_users.php \
  --data-urlencode "delete_user_id=$NOHIST_ID" | grep -oE "Account deleted"

# Try deleting the has-history user — should be refused
HASHIST_ID=$(php -r "
    \$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
    \$row = \$conn->query(\"SELECT user_id FROM users WHERE phone_number='0788000002'\")->fetch_assoc();
    echo \$row['user_id'] ?? '';
")
curl -s -b /tmp/admin_cookies.txt -X POST http://localhost:8123/src/admin/users/manage_users.php \
  --data-urlencode "delete_user_id=$HASHIST_ID" | grep -oE "deactivate it instead"

# Clean up remaining test data
php -r "
\$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
\$conn->query(\"DELETE FROM patients WHERE registered_by = (SELECT user_id FROM users WHERE phone_number='0788000002')\");
\$conn->query(\"DELETE FROM users WHERE phone_number IN ('0788000001','0788000002')\");
"
rm -f /tmp/admin_cookies.txt /tmp/php_server.log
kill %1 2>/dev/null
```

Expected: four separate grep matches, in order: `deactivated`, `reactivated`, `Account deleted`, `deactivate it instead`.

- [ ] **Step 4: Commit**

```bash
git add src/admin/users/manage_users.php
git commit -m "Add admin account management: deactivate, reactivate, delete"
```

---

### Task 3: Hospital management (add + edit)

**Files:**
- Modify: `src/admin/doctors/hospitals/view_hospitals.php` (add an "Add Hospital" form + Edit links)
- Create: `src/admin/doctors/hospitals/edit_hospital.php`

**Interfaces:**
- Consumes: `require_role()` from `auth_check.php`; existing `hospitals` table (`hospital_id`, `hospital_name`, `location`, `phone_number`).
- Produces: nothing consumed by later tasks — standalone. Task 5 links to `/src/admin/doctors/hospitals/view_hospitals.php` (already linked from the doctors dashboard drawer, no new link needed there).

- [ ] **Step 1: Read the current file to get exact insertion points**

Read `src/admin/doctors/hospitals/view_hospitals.php`. Confirm these two exact anchors exist:
1. The fetch query block (`/* ================= FETCH HOSPITALS ================= */`)
2. The card loop (`<?php while($row = $res->fetch_assoc()) { ?>` ... closing `<?php } ?>`)

- [ ] **Step 2: Add the "Add Hospital" POST handler above the fetch query**

Insert this immediately before the `/* ================= FETCH HOSPITALS ================= */` comment:

```php
$addMsg = "";

if (isset($_POST['add_hospital'])) {

    $name = trim($_POST['hospital_name'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $phone = trim($_POST['phone_number'] ?? '');

    if ($name === '') {
        $addMsg = "Hospital name is required";
    } else {
        $stmt = $conn->prepare("INSERT INTO hospitals(hospital_name, location, phone_number) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $location, $phone);
        $stmt->execute();
        $addMsg = "Hospital added.";
    }
}

```

- [ ] **Step 3: Add the form + message markup, right after the `<!-- PAGE HEADER -->` block's closing `</div>` and before `<!-- GRID -->`**

```php
<?php if (!empty($addMsg)): ?>
    <p style="text-align:center;color:#0b6e0b;font-size:13px;margin-bottom:10px;"><?php echo htmlspecialchars($addMsg); ?></p>
<?php endif; ?>

<div style="max-width:500px;margin:0 auto 20px;background:#fff;border-radius:16px;padding:20px;box-shadow:0 10px 25px rgba(0,0,0,0.06);">
    <h3 style="margin-bottom:12px;font-size:15px;">Add Hospital</h3>
    <form method="POST" style="display:flex;flex-direction:column;gap:8px;">
        <input type="text" name="hospital_name" placeholder="Hospital Name" required style="padding:10px;border-radius:8px;border:1px solid #ddd;">
        <input type="text" name="location" placeholder="Location" style="padding:10px;border-radius:8px;border:1px solid #ddd;">
        <input type="text" name="phone_number" placeholder="Phone Number" style="padding:10px;border-radius:8px;border:1px solid #ddd;">
        <button type="submit" name="add_hospital" style="padding:10px;border:none;border-radius:8px;background:#111;color:#fff;font-weight:600;cursor:pointer;">Add Hospital</button>
    </form>
</div>
```

- [ ] **Step 4: Add an Edit link to each hospital card**

Inside the card loop, find:

```php
        <div class="location">
            📞 <?php echo htmlspecialchars($row['phone_number']); ?>
        </div>

    </div>

<?php } ?>
```

Replace with:

```php
        <div class="location">
            📞 <?php echo htmlspecialchars($row['phone_number']); ?>
        </div>

        <a href="edit_hospital.php?hospital_id=<?php echo $row['hospital_id']; ?>" style="display:inline-block;margin-top:8px;font-size:13px;color:#0d6efd;text-decoration:none;">Edit</a>

    </div>

<?php } ?>
```

- [ ] **Step 5: Create `edit_hospital.php`**

```php
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
```

- [ ] **Step 6: Lint both files**

```bash
php -l src/admin/doctors/hospitals/view_hospitals.php
php -l src/admin/doctors/hospitals/edit_hospital.php
```

Expected: `No syntax errors detected` for both.

- [ ] **Step 7: Verify live — add a hospital, then edit it**

```bash
php -S localhost:8123 > /tmp/php_server.log 2>&1 &
sleep 1
rm -f /tmp/admin_cookies.txt

curl -s -c /tmp/admin_cookies.txt -X POST http://localhost:8123/src/auth/login.php \
  --data-urlencode "login=1" --data-urlencode "user_phonenumber=0700000000" --data-urlencode "password=Qwerty1!" -o /dev/null

curl -s -b /tmp/admin_cookies.txt -X POST http://localhost:8123/src/admin/doctors/hospitals/view_hospitals.php \
  --data-urlencode "add_hospital=1" --data-urlencode "hospital_name=Test Hospital" \
  --data-urlencode "location=Kisumu" --data-urlencode "phone_number=0711000111" | grep -oE "Hospital added"

TEST_HOSPITAL_ID=$(php -r "
    \$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
    echo \$conn->query(\"SELECT hospital_id FROM hospitals WHERE hospital_name='Test Hospital'\")->fetch_assoc()['hospital_id'];
")

curl -s -b /tmp/admin_cookies.txt "http://localhost:8123/src/admin/doctors/hospitals/edit_hospital.php?hospital_id=$TEST_HOSPITAL_ID" | grep -oE "Test Hospital"

curl -s -b /tmp/admin_cookies.txt -X POST http://localhost:8123/src/admin/doctors/hospitals/edit_hospital.php \
  --data-urlencode "hospital_id=$TEST_HOSPITAL_ID" --data-urlencode "update_hospital=1" \
  --data-urlencode "hospital_name=Test Hospital Updated" --data-urlencode "location=Kisumu" \
  --data-urlencode "phone_number=0711000111" | grep -oE "Hospital updated"

php -r "
\$conn = new mysqli('localhost','root','root123','chp-referral-followup-system',3306);
\$conn->query(\"DELETE FROM hospitals WHERE hospital_name IN ('Test Hospital','Test Hospital Updated')\");
"
rm -f /tmp/admin_cookies.txt /tmp/php_server.log
kill %1 2>/dev/null
```

Expected: three grep matches: `Hospital added`, `Test Hospital`, `Hospital updated`.

- [ ] **Step 8: Commit**

```bash
git add src/admin/doctors/hospitals/view_hospitals.php src/admin/doctors/hospitals/edit_hospital.php
git commit -m "Add hospital creation and editing for admins"
```

---

### Task 4: Analytics page

**Files:**
- Create: `src/admin/analytics.php`

**Interfaces:**
- Consumes: `require_role()` from `auth_check.php`; `users`, `hospitals`, `patients`, `appointments`, `referrals` tables (all existing, unmodified schema).
- Produces: nothing consumed by later tasks — standalone. Task 5 links to `/src/admin/analytics.php`.

- [ ] **Step 1: Write the file**

```php
<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth_check.php';
require_role(['admin']);

$usersByRole = $conn->query("SELECT role, COUNT(*) AS c FROM users WHERE status = 'active' GROUP BY role");
$pendingCount = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status = 'pending'")->fetch_assoc()['c'];
$hospitalCount = $conn->query("SELECT COUNT(*) AS c FROM hospitals")->fetch_assoc()['c'];
$patientCount = $conn->query("SELECT COUNT(*) AS c FROM patients")->fetch_assoc()['c'];
$appointmentCount = $conn->query("SELECT COUNT(*) AS c FROM appointments")->fetch_assoc()['c'];
$referralsByStatus = $conn->query("SELECT status, COUNT(*) AS c FROM referrals GROUP BY status");
?>

<!DOCTYPE html>
<html>
<head>
<title>Analytics</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;font-family:"Segoe UI",sans-serif;}
body{background:linear-gradient(135deg,#f5f6fa,#eef1f7);color:#222;}
.header{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:#fff;border-bottom:1px solid #e9e9e9;}
.logout{padding:10px 16px;border-radius:10px;background:#111;color:#fff;text-decoration:none;font-weight:600;}
.content{padding:40px;max-width:900px;margin:auto;}
.section{background:#fff;border-radius:16px;padding:24px;margin-bottom:20px;box-shadow:0 10px 25px rgba(0,0,0,0.06);}
.section h3{margin-bottom:14px;font-size:16px;}
.stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;}
.stat{background:#f7f7f7;border-radius:12px;padding:16px;text-align:center;}
.stat .num{font-size:26px;font-weight:800;}
.stat .label{font-size:12px;color:#666;margin-top:4px;text-transform:capitalize;}
</style>
</head>
<body>

<div class="header">
    <h1>Analytics</h1>
    <a class="logout" href="/src/auth/logout.php">Logout</a>
</div>

<div class="content">

    <p><a href="/src/admin/doctors/dashboard.php">&larr; Back to dashboard</a></p>

    <div class="section">
        <h3>System Snapshot</h3>
        <div class="stat-grid">
            <?php while ($row = $usersByRole->fetch_assoc()): ?>
                <div class="stat">
                    <div class="num"><?php echo (int) $row['c']; ?></div>
                    <div class="label">Active <?php echo htmlspecialchars($row['role']); ?>s</div>
                </div>
            <?php endwhile; ?>
            <div class="stat">
                <div class="num"><?php echo (int) $pendingCount; ?></div>
                <div class="label">Pending Approvals</div>
            </div>
            <div class="stat">
                <div class="num"><?php echo (int) $hospitalCount; ?></div>
                <div class="label">Hospitals</div>
            </div>
            <div class="stat">
                <div class="num"><?php echo (int) $patientCount; ?></div>
                <div class="label">Patients</div>
            </div>
            <div class="stat">
                <div class="num"><?php echo (int) $appointmentCount; ?></div>
                <div class="label">Appointments</div>
            </div>
        </div>
    </div>

    <div class="section">
        <h3>Referral Pipeline</h3>
        <div class="stat-grid">
            <?php if ($referralsByStatus->num_rows === 0): ?>
                <p style="color:#777;">No referrals yet.</p>
            <?php endif; ?>
            <?php while ($row = $referralsByStatus->fetch_assoc()): ?>
                <div class="stat">
                    <div class="num"><?php echo (int) $row['c']; ?></div>
                    <div class="label"><?php echo htmlspecialchars($row['status']); ?></div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

</div>

</body>
</html>
```

- [ ] **Step 2: Lint**

```bash
php -l src/admin/analytics.php
```

Expected: `No syntax errors detected in src/admin/analytics.php`

- [ ] **Step 3: Verify live — check the counts reflect real data**

```bash
php -S localhost:8123 > /tmp/php_server.log 2>&1 &
sleep 1
rm -f /tmp/admin_cookies.txt

curl -s -c /tmp/admin_cookies.txt -X POST http://localhost:8123/src/auth/login.php \
  --data-urlencode "login=1" --data-urlencode "user_phonenumber=0700000000" --data-urlencode "password=Qwerty1!" -o /dev/null

# Baseline: with only the seeded admin and 3 seeded hospitals present, expect 1 active admin, 3 hospitals, 0 patients/appointments, no referral rows
curl -s -b /tmp/admin_cookies.txt http://localhost:8123/src/admin/analytics.php -o /tmp/analytics_out.html
grep -oE "Active admins|Hospitals|Patients|Appointments|No referrals yet" /tmp/analytics_out.html

rm -f /tmp/admin_cookies.txt /tmp/php_server.log /tmp/analytics_out.html
kill %1 2>/dev/null
```

Expected: grep prints `Active admins`, `Hospitals`, `Patients`, `Appointments`, `No referrals yet` (exact set depends on whatever data currently exists in the dev DB at plan-execution time — if other test/seed data is present, adjust expectations accordingly, but the page must render without PHP warnings/errors).

- [ ] **Step 4: Commit**

```bash
git add src/admin/analytics.php
git commit -m "Add admin analytics page: system snapshot and referral pipeline"
```

---

### Task 5: Wire up the dashboard hub

**Files:**
- Modify: `src/admin/doctors/dashboard.php`

**Interfaces:**
- Consumes: paths produced by Tasks 2, 3, 4 (`/src/admin/users/manage_users.php`, `/src/admin/doctors/hospitals/view_hospitals.php`, `/src/admin/analytics.php`) plus the existing `/src/admin/users/pending_users.php`.
- Produces: nothing — this is the final integration point.

- [ ] **Step 1: Read the current file**

Confirm this exact existing block in `src/admin/doctors/dashboard.php`:

```php
<div class="content">
    <h2>Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h2>
    <p>Role: <?php echo htmlspecialchars($currentUser['role']); ?></p>
    <p style="margin-top:20px;"><a href="/src/admin/users/pending_users.php">Review pending account approvals</a></p>
</div>
```

- [ ] **Step 2: Replace it with links to all four admin pages**

```php
<div class="content">
    <h2>Welcome, <?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?></h2>
    <p>Role: <?php echo htmlspecialchars($currentUser['role']); ?></p>
    <ul style="margin-top:20px;line-height:2;">
        <li><a href="/src/admin/users/pending_users.php">Review pending account approvals</a></li>
        <li><a href="/src/admin/users/manage_users.php">Manage active accounts</a></li>
        <li><a href="/src/admin/doctors/hospitals/view_hospitals.php">Manage hospitals</a></li>
        <li><a href="/src/admin/analytics.php">View analytics</a></li>
    </ul>
</div>
```

- [ ] **Step 3: Lint**

```bash
php -l src/admin/doctors/dashboard.php
```

Expected: `No syntax errors detected in src/admin/doctors/dashboard.php`

- [ ] **Step 4: Verify live — all four links are present and reachable**

```bash
php -S localhost:8123 > /tmp/php_server.log 2>&1 &
sleep 1
rm -f /tmp/admin_cookies.txt

curl -s -c /tmp/admin_cookies.txt -X POST http://localhost:8123/src/auth/login.php \
  --data-urlencode "login=1" --data-urlencode "user_phonenumber=0700000000" --data-urlencode "password=Qwerty1!" -o /dev/null

curl -s -b /tmp/admin_cookies.txt http://localhost:8123/src/admin/doctors/dashboard.php | grep -oE "pending_users.php|manage_users.php|view_hospitals.php|analytics.php"

for path in "/src/admin/users/pending_users.php" "/src/admin/users/manage_users.php" "/src/admin/doctors/hospitals/view_hospitals.php" "/src/admin/analytics.php"; do
  curl -s -o /dev/null -w "%{http_code} $path\n" -b /tmp/admin_cookies.txt "http://localhost:8123$path"
done

rm -f /tmp/admin_cookies.txt /tmp/php_server.log
kill %1 2>/dev/null
```

Expected: the first grep prints all four filenames; the loop prints `200` for all four paths.

- [ ] **Step 5: Commit**

```bash
git add src/admin/doctors/dashboard.php
git commit -m "Wire admin dashboard hub to account management, hospitals, and analytics"
```
