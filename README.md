# Referral and Follow-up System for Community Health Promoters (CHPs)

This is a referral and follow-up system for Community Health Promoters (CHPs) operating in urban informal settlements.

## Problem Statement

In urban informal settlements, Community Health Promoters (CHPs) are often the first point of contact between residents and the formal healthcare system. When a CHP identifies a patient who needs specialist or facility-based care, referrals are typically tracked manually (paper slips, phone calls, or word of mouth). This makes it hard to:

- Confirm that a referred patient actually reached the hospital/doctor.
- Track the status of a referral through to resolution.
- Give hospital staff and administrators visibility into referral volumes and outcomes.
- Coordinate between CHPs, doctors, and hospital administrators, who each have different needs and permissions.

This system digitizes that workflow, giving each role (CHP, Doctor, Admin) a dedicated, permission-gated interface for managing patients, appointments, and referrals.

## Objectives
- Allow CHPs to register patients and initiate referrals/follow-ups from the field.
- Allow Doctors to view and manage appointments booked against them.
- Allow Admins to approve new accounts, manage hospitals, and monitor system-wide activity.


## Features

- **Authentication & account management**
  - Registration for three roles: CHP, Doctor, Admin (`src/auth/register.php`).
  - New accounts start in a `pending` state and require admin approval before login is allowed.
  - Login, logout, and "forgot password" / "reset password" flows (email-based, via PHPMailer).
  - Enforced password complexity rules (min. 8 characters, uppercase letter, number, special character).
- **Admin**
  - Dashboard hub linking to all admin capabilities.
  - Approve or reject pending account registrations.
  - Manage active accounts: deactivate, reactivate, or delete (with safeguards against deleting accounts that have referral/patient history, and against self-lockout).
  - Manage hospitals: add new hospitals, edit existing hospital details.
  - View doctors and doctor detail pages.
  - Analytics page: system snapshot (active users by role, pending approvals, hospital/patient/appointment counts) and referral pipeline breakdown by status.
- **Doctor**
  - Dashboard listing the doctor's appointments.
  - Book and view appointments.
- **CHP (Community Health Promoter)**
  - Dashboard listing registered patients.
  - Register new patients from the field.
- **Access control**
  - Every protected page is guarded by a shared `require_role()` helper (`src/includes/auth_check.php`), so pages redirect unauthenticated or unauthorized users back to login.

## Technologies Used

- **Language:** PHP 8.2
- **Database:** MySQL / MariaDB (accessed via `mysqli` with prepared statements)
- **Mail:** PHPMailer (SMTP, used for password reset emails)
- **Frontend:** Server-rendered HTML/CSS with vanilla JavaScript 
- **Dependency management:** Composer

## Installation Guide

### Prerequisites

- PHP 8.2 or later, with the `mysqli` extension enabled
- MySQL or MariaDB server
- [Composer](https://getcomposer.org/)

### Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd chp-referral-followup-system
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

## Setup Instructions

1. **Create the database**

   Create a MySQL/MariaDB database (default name used throughout the codebase is `chp-referral-followup-system`):
   ```sql
   CREATE DATABASE `chp-referral-followup-system`;
   ```

   > **Note:** This repository does not yet include a schema file or migrations for the application tables (`users`, `hospitals`, `doctors`, `patients`, `appointments`, `referrals`). These tables must currently be created to match the columns referenced in `src/**` before the app will run against a fresh database.

2. **Configure the database connection**

   Edit `src/config/db.php` with your local database credentials:
   ```php
   $host = "";
   $user = "";
   $pass = "";
   $db   = "";
   $port = ;
   ```

3. **Configure outgoing mail (for password reset)**

   Edit `src/config/mail.php` with SMTP credentials (this file is gitignored, so your real credentials won't be committed):
   ```php
   return [
       'host'       => 'smtp.gmail.com',
       'port'       => 587,
       'encryption' => 'tls',
       'username'   => 'your-email@example.com',
       'password'   => 'your-app-password',
       'from_email' => 'your-email@example.com',
       'from_name'  => 'Referral-Followup System',
   ];
   ```

## Usage Instructions

### Running the application

The live application is plain PHP served directly from the repository root — no framework bootstrapping is required. Start PHP's built-in server from the project root:

```bash
php -S localhost:8000
```

Then open [http://localhost:8000](http://localhost:8000) in a browser. You will be redirected to the login page (`src/auth/login.php`) if not signed in.

### Typical workflow

1. **Register** an account via `src/auth/register.php`, choosing a role (CHP, Doctor, or Admin). Doctors must also select a hospital and specialization.
2. **Admin approval:** new accounts are created with `pending` status. An existing admin must approve the account from `src/admin/users/pending_users.php` before the new user can log in.
3. **Login** at `src/auth/login.php`; you'll be redirected to the dashboard for your role:
   - Admin → `src/admin/doctors/dashboard.php`
   - Doctor → `src/doctor/appointments/dashboard.php`
   - CHP → `src/chp/patients/dashboard.php`
4. From there:
   - CHPs can register patients (`src/chp/patients/add_patient.php`).
   - Doctors can view and book appointments (`src/doctor/appointments/`).
   - Admins can manage accounts, hospitals, and view analytics from the dashboard hub links.

## Folder Structure

```
chp-referral-followup-system/
├── src/                        # The application (plain PHP, no framework routing)
│   ├── admin/                  # Admin-only pages
│   │   ├── analytics.php       # System snapshot + referral pipeline
│   │   ├── doctors/            # Doctor dashboard, doctor list/detail
│   │   │   └── hospitals/      # Hospital add/edit/view
│   │   └── users/              # Pending approvals, active account management
│   ├── auth/                   # Login, registration, password reset, logout
│   ├── chp/patients/           # CHP patient registration and dashboard
│   ├── doctor/appointments/    # Doctor appointment booking and dashboard
│   ├── config/                 # Raw db.php / mail.php config (gitignored)
│   └── includes/               # Shared helpers: auth_check.php, password_rules.php
├── docs/                       # Implementation plans and project docs
├── vendor/                     # Composer dependencies (generated)
├── composer.json
├── index.php                   # Root router: redirects based on session role
└── README.md
```

## License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.
