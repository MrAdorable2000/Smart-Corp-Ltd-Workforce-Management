<?php
/**
 * Application Configuration
 * Smart Employee Attendance & Workforce Management System
 * All constants wrapped with if(!defined()) to prevent duplicate-define warnings.
 */

// ── Error reporting ──────────────────────────────────────────────────
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ── Session security (before any output) ────────────────────────────
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
ini_set('session.use_strict_mode', 1);
ini_set('session.gc_maxlifetime', 1800);
ini_set('session.cookie_samesite', 'Lax');

// ── Timezone ─────────────────────────────────────────────────────────
date_default_timezone_set('UTC');

// ── Application ───────────────────────────────────────────────────────
if (!defined('APP_NAME'))    define('APP_NAME',    'Smart Employee Attendance System');
if (!defined('APP_VERSION')) define('APP_VERSION', '3.0.0');
if (!defined('APP_ENV'))     define('APP_ENV',     'development'); // development | production

// ── Paths ─────────────────────────────────────────────────────────────
if (!defined('ROOT_PATH'))    define('ROOT_PATH',    dirname(__DIR__));
if (!defined('APP_PATH'))     define('APP_PATH',     ROOT_PATH . '/app');
if (!defined('CONFIG_PATH'))  define('CONFIG_PATH',  ROOT_PATH . '/config');
if (!defined('PUBLIC_PATH'))  define('PUBLIC_PATH',  ROOT_PATH . '/public');
if (!defined('STORAGE_PATH')) define('STORAGE_PATH', ROOT_PATH . '/storage');
if (!defined('UPLOAD_PATH'))  define('UPLOAD_PATH',  PUBLIC_PATH . '/uploads');

// ── URLs ──────────────────────────────────────────────────────────────
if (!defined('BASE_URL'))   define('BASE_URL',   '/smart-attendance/public');
if (!defined('ASSET_URL'))  define('ASSET_URL',  BASE_URL . '/assets');
if (!defined('UPLOAD_URL')) define('UPLOAD_URL', BASE_URL . '/uploads');

// ── Database ──────────────────────────────────────────────────────────
if (!defined('DB_HOST'))    define('DB_HOST',    '127.0.0.1');
if (!defined('DB_PORT'))    define('DB_PORT',    '3306');
if (!defined('DB_NAME'))    define('DB_NAME',    'smart_attendance');
if (!defined('DB_USER'))    define('DB_USER',    'root');
if (!defined('DB_PASS'))    define('DB_PASS',    '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ── Security ──────────────────────────────────────────────────────────
if (!defined('CSRF_TOKEN_NAME'))   define('CSRF_TOKEN_NAME',   '_csrf_token');
if (!defined('CSRF_TOKEN_LENGTH')) define('CSRF_TOKEN_LENGTH', 32);
if (!defined('PASSWORD_ALGO'))     define('PASSWORD_ALGO',     PASSWORD_BCRYPT);
if (!defined('PASSWORD_COST'))     define('PASSWORD_COST',     12);
if (!defined('MAX_LOGIN_ATTEMPTS'))define('MAX_LOGIN_ATTEMPTS',5);
if (!defined('LOCKOUT_DURATION'))  define('LOCKOUT_DURATION',  900);   // 15 min
if (!defined('SESSION_TIMEOUT'))   define('SESSION_TIMEOUT',   1800);  // 30 min

// ── File uploads ──────────────────────────────────────────────────────
if (!defined('MAX_UPLOAD_SIZE'))    define('MAX_UPLOAD_SIZE',    10 * 1024 * 1024);
if (!defined('ALLOWED_IMAGE_TYPES'))define('ALLOWED_IMAGE_TYPES',['image/jpeg','image/png','image/gif','image/webp']);
if (!defined('ALLOWED_DOC_TYPES'))  define('ALLOWED_DOC_TYPES',  ['application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document','image/jpeg','image/png']);

// ── Face recognition ─────────────────────────────────────────────────
// FACE_MATCH_THRESHOLD: euclidean distance — lower = stricter
// 0.40 corresponds to ~90% confidence in face-api.js
if (!defined('FACE_MATCH_THRESHOLD'))  define('FACE_MATCH_THRESHOLD',  0.40);
if (!defined('FACE_MIN_CONFIDENCE'))   define('FACE_MIN_CONFIDENCE',   90.0); // percentage
if (!defined('FACE_FRAUD_LOG'))        define('FACE_FRAUD_LOG',        true);
if (!defined('FACE_DESCRIPTOR_DIR'))   define('FACE_DESCRIPTOR_DIR',   STORAGE_PATH . '/faces');
if (!defined('FACE_BLINK_REQUIRED'))   define('FACE_BLINK_REQUIRED',   2);
if (!defined('FACE_HEAD_MOVE_REQUIRED'))define('FACE_HEAD_MOVE_REQUIRED',1);

// ── Mail (SMTP) ───────────────────────────────────────────────────────
if (!defined('SMTP_HOST'))       define('SMTP_HOST',       '');
if (!defined('SMTP_PORT'))       define('SMTP_PORT',       587);
if (!defined('SMTP_USER'))       define('SMTP_USER',       '');
if (!defined('SMTP_PASS'))       define('SMTP_PASS',       '');
if (!defined('SMTP_FROM_EMAIL')) define('SMTP_FROM_EMAIL', 'noreply@smartcorp.com');
if (!defined('SMTP_FROM_NAME'))  define('SMTP_FROM_NAME',  APP_NAME);
if (!defined('SMTP_ENCRYPTION')) define('SMTP_ENCRYPTION', 'tls');

// ── Modules ───────────────────────────────────────────────────────────
if (!defined('MODULES')) define('MODULES', [
    'dashboard','users','roles','company','departments','employees',
    'attendance','shifts','leaves','payroll','holidays','face',
    'reports','audit','settings','notifications','gps','qr',
]);

// ── QR Code attendance ────────────────────────────────────────────────
if (!defined('QR_SECRET_KEY'))     define('QR_SECRET_KEY',     'SA_QR_' . md5('smartattend_secure_key_2024'));
if (!defined('QR_EXPIRY_MINUTES')) define('QR_EXPIRY_MINUTES', 0);          // 0 = permanent
if (!defined('QR_ROTATION_DAYS'))  define('QR_ROTATION_DAYS',  30);

// ── Mobile attendance ─────────────────────────────────────────────────
if (!defined('MOBILE_GPS_RADIUS_DEFAULT')) define('MOBILE_GPS_RADIUS_DEFAULT', 200);  // metres
if (!defined('MOBILE_PHOTO_REQUIRED'))     define('MOBILE_PHOTO_REQUIRED',     true);
if (!defined('MOBILE_GPS_ACCURACY_MAX'))   define('MOBILE_GPS_ACCURACY_MAX',   100);  // metres

// ── Attendance rules ──────────────────────────────────────────────────
if (!defined('LATE_GRACE_MINUTES'))     define('LATE_GRACE_MINUTES',     15);
if (!defined('HALF_DAY_HOURS'))         define('HALF_DAY_HOURS',         4.0);
if (!defined('OVERTIME_START_AFTER'))   define('OVERTIME_START_AFTER',   8.0);
if (!defined('ABSENT_MARK_AFTER_HOURS'))define('ABSENT_MARK_AFTER_HOURS',4);
if (!defined('EARLY_LEAVE_THRESHOLD'))  define('EARLY_LEAVE_THRESHOLD',  15); // minutes

// ── Default shift times ───────────────────────────────────────────────
if (!defined('DEFAULT_SHIFT_START')) define('DEFAULT_SHIFT_START', '08:00');
if (!defined('DEFAULT_SHIFT_END'))   define('DEFAULT_SHIFT_END',   '17:00');

// ── Real-time dashboard ───────────────────────────────────────────────
if (!defined('DASHBOARD_REFRESH_INTERVAL')) define('DASHBOARD_REFRESH_INTERVAL', 30000); // ms

// ── Cache ─────────────────────────────────────────────────────────────
if (!defined('CACHE_ENABLED')) define('CACHE_ENABLED', false);
if (!defined('CACHE_TTL'))     define('CACHE_TTL',     300); // seconds

// ── Scan cooldown ─────────────────────────────────────────────────────
if (!defined('KIOSK_SCAN_COOLDOWN')) define('KIOSK_SCAN_COOLDOWN', 8); // seconds between kiosk scans
