<?php
/**
 * Public Entry Point
 * Smart Employee Attendance & Workforce Management System
 */

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Autoload core classes
spl_autoload_register(function ($className) {
    $paths = [
        APP_PATH . '/core/' . $className . '.php',
        APP_PATH . '/helpers/' . $className . '.php',
        APP_PATH . '/models/' . $className . '.php',
        APP_PATH . '/middleware/' . $className . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Start session
Session::getInstance();

// Get URL path (strip base path and query string)
// Examples:
//   /smart-attendance/public         -> ""        (homepage)
//   /smart-attendance/public/        -> ""        (homepage)
//   /smart-attendance/public/login   -> "/login"
//   /smart-attendance/public/employees/5 -> "/employees/5"
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$url = substr($requestUri, strlen($scriptName));  // e.g. "/login" or "" or "/"

// Normalize: empty or "/" -> "" (homepage); otherwise keep leading slash
if ($url === false || $url === '' || $url === '/') {
    $url = '';
} else {
    // Ensure leading slash and remove trailing slash (except for homepage)
    if ($url[0] !== '/') $url = '/' . $url;
    $url = rtrim($url, '/');
}

// Define routes
require_once APP_PATH . '/routes.php';

// Dispatch
try {
    Router::dispatch($url);
} catch (Exception $e) {
    if (APP_ENV === 'development') {
        echo '<h1>Application Error</h1><pre>' . $e->getMessage() . "\n" . $e->getTraceAsString() . '</pre>';
    } else {
        http_response_code(500);
        echo '<h1>Internal Server Error</h1><p>Please contact administrator.</p>';
    }
}
