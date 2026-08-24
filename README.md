# Smart Employee Attendance & Workforce Management System

A complete enterprise-grade attendance and workforce management system built with **PHP 8 OOP, MySQL, Bootstrap 5, and JavaScript face-api.js**. Designed for organizations, institutions, companies, schools, hospitals, factories, and government offices.

![Version](https://img.shields.io/badge/version-1.0.0-blue) ![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple) ![MySQL](https://img.shields.io/badge/MySQL-8.0%2B-orange) ![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-success)

---

## 🚀 Features

### Core Modules
- **🔐 Authentication & Authorization** — 5 roles (Super Admin, HR Manager, Department Manager, Employee, Auditor), RBAC, OTP 2FA, password reset, remember-me, account lockout
- **👥 Employee Management** — Full CRUD, photos, documents, emergency contacts, employment status, salary
- **🏢 Department Management** — Multi-branch, hierarchical departments, department statistics
- **📅 Attendance Management** — Daily/weekly/monthly/yearly views, calendar, analytics
- **🎥 Face Recognition** — face-api.js powered, 128-d descriptor matching, anti-spoofing (blink, head movement, single-face, face size)
- **⏰ Shift Management** — Morning, Evening, Night, Flexible shifts with grace periods and overtime rules
- **🏖️ Leave Management** — 5 leave types, approval workflow, balance tracking, auto-attendance sync
- **💰 Payroll Module** — Automatic salary calculation (basic + OT + allowances - tax - penalties), printable payslips
- **📍 GPS Attendance** — Geofencing with Haversine distance, branch radius verification
- **📊 Reports** — Attendance, employee, payroll, leave, department reports with CSV/Excel/PDF export
- **🔔 Notifications** — In-app, email, SMS-ready, WhatsApp API-ready channels
- **🛡️ Audit Logs** — Complete activity tracking with IP, user agent, severity levels
- **⚙️ Settings** — Configurable system settings by group

### Security
- ✅ Bcrypt password hashing
- ✅ CSRF protection on all forms
- ✅ XSS protection via output escaping
- ✅ SQL injection protection (PDO prepared statements)
- ✅ Session security (HttpOnly, SameSite, regeneration, timeout)
- ✅ Account lockout after failed attempts
- ✅ Activity & audit logging
- ✅ Two-Factor Authentication (OTP)
- ✅ Role-Based Access Control (RBAC)

---

## 📦 Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | PHP 8 OOP, MVC Architecture, PDO |
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript ES6, jQuery, AJAX |
| Database | MySQL (XAMPP) |
| Face Recognition | face-api.js (TinyFaceDetector + 68 landmark + Recognition nets) |
| Charts | Chart.js |
| Icons | Bootstrap Icons |
| Reports | DomPDF / PhpSpreadsheet ready (CSV/Excel export built-in) |
| Fonts | Inter (Google Fonts) |

---

## 🛠️ Installation Guide

### Step 1: Prerequisites
Install [XAMPP](https://www.apachefriends.org/) (with PHP 8+ and MySQL).

### Step 2: Copy Project
1. Copy the `smart-attendance` folder to your XAMPP htdocs directory:
   ```
   C:\xampp\htdocs\smart-attendance\      (Windows)
   /opt/lampp/htdocs/smart-attendance/    (Linux)
   /Applications/XAMPP/htdocs/smart-attendance/  (macOS)
   ```

### Step 3: Database Setup
1. Open phpMyAdmin: http://localhost/phpmyadmin
2. Click **Import**
3. Select `database/schema.sql` and click **Go** — creates the `smart_attendance` database with all 20 tables
4. Import `database/seed.sql` — loads roles, permissions, demo company, departments, employees, sample attendance, and the Super Admin user

### Step 4: Configure Database
Edit `config/config.php` if your MySQL credentials differ:
```php
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'smart_attendance');
define('DB_USER', 'root');     // default XAMPP
define('DB_PASS', '');         // default XAMPP (empty)
```

### Step 5: Set Permissions
Make these directories writable:
```bash
chmod -R 755 public/uploads/
chmod -R 755 storage/
```

### Step 6: Access the System
Open your browser and navigate to:
```
http://localhost/smart-attendance/public/
```

---

## 🔑 Default Login Credentials

### Super Admin
- **Email:** `ethiennemugisha35@gmail.com`
- **Password:** `password`

### Demo Users (password for all: `password`)
| Role | Email |
|------|-------|
| HR Manager | hr@smartcorp.com |
| Department Manager | manager@smartcorp.com |
| Auditor | auditor@smartcorp.com |
| Employee | john.smith@smartcorp.com |

---

## 📁 Project Structure

```
smart-attendance/
├── app/
│   ├── controllers/          # PHP controllers (one per module + Api/)
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── EmployeeController.php
│   │   ├── DepartmentController.php
│   │   ├── AttendanceController.php
│   │   ├── FaceController.php
│   │   ├── ShiftController.php
│   │   ├── LeaveController.php
│   │   ├── PayrollController.php
│   │   ├── ReportController.php
│   │   ├── NotificationController.php
│   │   ├── SettingController.php
│   │   ├── AuditLogController.php
│   │   ├── ProfileController.php
│   │   ├── CompanyController.php
│   │   ├── BranchController.php
│   │   ├── HolidayController.php
│   │   ├── UserController.php
│   │   └── Api/                # REST API controllers
│   │       ├── Auth.php
│   │       ├── Dashboard.php
│   │       └── Attendance.php
│   ├── core/                  # Framework classes
│   │   ├── Controller.php
│   │   ├── Model.php
│   │   └── Router.php
│   ├── helpers/               # Utility helpers
│   │   ├── Auth.php           # Authentication & authorization
│   │   ├── Session.php        # Secure session management
│   │   ├── CSRF.php           # CSRF tokens
│   │   ├── Flash.php          # Flash messages
│   │   ├── Url.php            # URL helpers
│   │   ├── Validator.php      # Input validation
│   │   └── Uploader.php       # File uploads
│   ├── middleware/            # Auth & Role middleware
│   ├── models/                # Domain models
│   │   └── Employee.php
│   ├── views/                 # PHP views (Bootstrap 5)
│   │   ├── layouts/           # app.php, auth.php, print.php
│   │   ├── auth/              # login, forgot, reset, verify_otp
│   │   ├── dashboard/
│   │   ├── employees/
│   │   ├── departments/
│   │   ├── attendance/
│   │   ├── face/              # Face enrollment UI
│   │   ├── leaves/
│   │   ├── shifts/
│   │   ├── payroll/
│   │   ├── reports/
│   │   ├── settings/
│   │   ├── notifications/
│   │   ├── audit_logs/
│   │   ├── users/
│   │   ├── branches/
│   │   ├── holidays/
│   │   ├── company/
│   │   ├── profile/
│   │   └── errors/
│   └── routes.php             # Route definitions
├── config/
│   ├── config.php             # Application configuration
│   └── database.php           # PDO database connection (singleton)
├── database/
│   ├── schema.sql             # Full MySQL schema (20 tables)
│   └── seed.sql               # Seed data (roles, permissions, demo users)
├── public/                    # Web root
│   ├── index.php              # Front controller
│   ├── .htaccess              # Apache rewrite rules + security headers
│   └── assets/
│       ├── css/
│       │   ├── style.css      # Main app styles
│       │   └── auth.css       # Login/auth page styles
│       ├── js/
│       │   ├── app.js         # Main JS (AJAX, CSRF, UI helpers)
│       │   └── face-recognition.js  # Face-api.js wrapper (enroll, verify, match, anti-spoof)
│       └── uploads/           # User uploads (employees, faces, documents)
├── api/                       # API endpoint hooks
├── storage/                   # Logs, cache, face data
└── README.md
```

---

## 🗄️ Database Schema (20 Tables)

| Table | Purpose |
|-------|---------|
| `roles` | User roles (Super Admin, HR, Manager, Employee, Auditor) |
| `permissions` | Granular permissions per module |
| `role_permissions` | Role-permission mapping |
| `users` | System users (login accounts) |
| `companies` | Company profile |
| `branches` | Company branches with geofencing |
| `departments` | Departments (HR, Finance, IT, etc.) |
| `employees` | Employee records |
| `employee_documents` | Uploaded documents |
| `employee_faces` | Face descriptors (128-d vectors) |
| `attendance` | Daily attendance records |
| `attendance_logs` | Raw face-scan events |
| `shifts` | Work shifts |
| `leave_types` | Annual, Sick, Maternity, Emergency, Unpaid |
| `leave_requests` | Leave applications |
| `leave_balances` | Yearly leave entitlements |
| `holidays` | Public/religious/company holidays |
| `payroll` | Payroll records per period |
| `payroll_items` | Individual earnings/deductions |
| `notifications` | In-app, email, SMS notifications |
| `audit_logs` | System audit trail |
| `activity_logs` | Login/session activity |
| `settings` | System configuration |
| `working_policies` | Company working policies |

All tables include:
- Foreign keys with appropriate CASCADE/RESTRICT rules
- Indexes on frequently queried columns
- Constraints (UNIQUE, NOT NULL, ENUMs)
- Timestamps (created_at, updated_at)

---

## 🎥 Face Recognition Implementation

### How It Works
1. **Enrollment**: Employee looks at camera → face-api.js detects face → extracts 128-d descriptor → stores as JSON in `employee_faces` table
2. **Verification**: Probe descriptor compared against stored descriptors using Euclidean distance
3. **Matching**: When marking attendance, descriptor matched against ALL enrolled employees (within threshold)
4. **Anti-Spoofing**: Multi-layer liveness detection:
   - Blink detection via Eye Aspect Ratio (EAR) — requires 2+ blinks
   - Head movement detection (nose tip trajectory)
   - Single face enforcement (rejects multiple faces)
   - Minimum face size check (prevents photo attacks from distance)
   - Detection time check (must observe face for ≥1.5s)

### Configuration
Edit `config/config.php`:
```php
define('FACE_MATCH_THRESHOLD', 0.55);  // Lower = stricter
define('FACE_MIN_CONFIDENCE', 0.90);
```

### Models
face-api.js models are loaded from CDN by default. For offline use, download weights to `public/assets/models/` and update `MODELS_URL` in `public/assets/js/face-recognition.js`.

---

## 🔌 REST API Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth/login` | API login (returns token) |
| POST | `/api/auth/logout` | Invalidate token |
| GET | `/api/dashboard/stats` | Dashboard KPIs |
| GET | `/api/attendance/today` | Today's attendance |
| GET | `/api/attendance/stats` | Attendance stats |
| GET | `/api/employees` | List employees |
| GET | `/api/employees/{id}` | Get employee |
| GET | `/api/departments` | List departments |
| GET | `/api/notifications` | User notifications |
| POST | `/face/store` | Store face descriptor |
| POST | `/face/verify` | Verify employee face |
| POST | `/face/match` | Match face against all enrolled |
| GET | `/face/descriptors` | Get all descriptors (client-side matching) |
| POST | `/attendance/check-in` | Check-in (face or GPS) |
| POST | `/attendance/check-out` | Check-out |
| POST | `/reports/export` | Export report (CSV/Excel/PDF) |

---

## 🎯 Usage Guide

### For Employees
1. Login → Click **Face Check-In/Out** in sidebar
2. Click **Start Camera** → position face in frame
3. Blink 2+ times and move head slightly (anti-spoofing)
4. System matches your face → automatic check-in/out
5. View your attendance under **My Attendance**
6. Apply for leave under **Leave Requests**

### For HR Managers
1. Add employees via **Employees → Add Employee**
2. Enroll employee face via **Enroll Face** button on employee profile
3. Process monthly payroll via **Payroll → Generate Payroll**
4. Approve/reject leave requests under **Leave Requests**
5. Configure shifts, holidays, branches

### For Super Admin
1. Manage all system users under **Users**
2. Configure system settings under **Settings**
3. View complete audit trail under **Audit Logs**
4. Manage company profile and branches

---

## 🛡️ Security Best Practices Implemented

1. **SQL Injection** — All queries use PDO prepared statements with parameter binding
2. **XSS** — All output escaped with `htmlspecialchars()` and Bootstrap's text utilities
3. **CSRF** — Every form includes CSRF token; AJAX requests send `X-CSRF-TOKEN` header
4. **Session Fixation** — Session ID regenerated on login and periodically
5. **Password Hashing** — Bcrypt with cost 10
6. **Brute Force** — Account lockout after 5 failed attempts (15-minute lockout)
7. **Session Hijacking** — HttpOnly + SameSite cookies, secure flag on HTTPS
8. **File Upload** — MIME type validation, random filenames, separate upload directory
9. **Activity Logging** — All sensitive actions logged with IP and user agent
10. **RBAC** — Permission checks on every protected route

---

## 🎨 UI/UX Features

- Responsive design (mobile, tablet, desktop)
- Modern dashboard with Chart.js visualizations
- Bootstrap 5 components
- Toast notifications
- Modal dialogs for quick actions
- Live face recognition with real-time feedback
- Print-friendly payslips and reports
- Clean, professional enterprise look

---

## 📝 Development Notes

### Production Deployment
1. Set `APP_ENV` to `production` in `config/config.php`
2. Disable `display_errors` in PHP config
3. Set up HTTPS with valid SSL certificate
4. Configure SMTP for email notifications
5. Set up cron jobs for:
   - Daily attendance reminders (e.g., 8:00 AM)
   - Late arrival alerts (e.g., 9:30 AM)
   - Monthly payroll processing (e.g., 1st of month)
6. Configure backup strategy for MySQL database
7. Set up SMS gateway (Twilio, Africa's Talking, etc.) for SMS notifications
8. Configure WhatsApp Business API for WhatsApp notifications

### Extending the System
- **Add new module**: Create `app/controllers/XxxController.php`, add routes in `app/routes.php`, create views in `app/views/xxx/`
- **Add new permission**: Insert into `permissions` table, assign to roles via `role_permissions`
- **Add API endpoint**: Create controller in `app/controllers/Api/`, add route, return JSON
- **Customize face recognition**: Edit `public/assets/js/face-recognition.js` (match threshold, anti-spoof rules)

### Third-Party Libraries
- [face-api.js](https://github.com/justadudewhohacks/face-api.js) — Face recognition
- [Bootstrap 5](https://getbootstrap.com/) — UI framework
- [Chart.js](https://chartjs.org/) — Charts
- [jQuery](https://jquery.com/) — AJAX/DOM
- [Bootstrap Icons](https://icons.getbootstrap.com/) — Icons

---

## 📄 License

This project is provided as-is for educational and commercial use. Customize freely for your organization's needs.

---

## 🤝 Support

For issues, questions, or customization requests, refer to:
- The in-system **Audit Logs** for debugging
- `storage/logs/` for PHP error logs
- Browser DevTools console for face recognition issues (camera permissions, model loading)

**Built with ❤️ for modern workforce management.**
