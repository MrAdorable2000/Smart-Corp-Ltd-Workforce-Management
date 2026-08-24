<?php
/**
 * Installation Verification Script
 * Run this once after setting up XAMPP to verify installation
 * Access via: http://localhost/smart-attendance/install.php
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Attendance - Installation Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background: #F3F4F6; padding: 40px 20px; }
        .check-card { max-width: 800px; margin: 0 auto; background: #fff; border-radius: 16px; padding: 32px; box-shadow: 0 10px 25px rgba(0,0,0,.05); }
        .check-item { padding: 12px 16px; border-radius: 8px; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; }
        .check-item.pass { background: #D1FAE5; color: #065F46; }
        .check-item.fail { background: #FEE2E2; color: #991B1B; }
        .check-item.warn { background: #FEF3C7; color: #92400E; }
    </style>
</head>
<body>
<div class="check-card">
    <h2 class="mb-4"><i class="bi bi-shield-check text-primary"></i> Smart Attendance - Installation Check</h2>

    <?php
    $checks = [];

    // PHP version check
    $phpOk = version_compare(PHP_VERSION, '8.0.0', '>=');
    $checks[] = [
        'status' => $phpOk ? 'pass' : 'fail',
        'icon' => $phpOk ? 'check-circle' : 'x-circle',
        'label' => 'PHP Version',
        'value' => PHP_VERSION . ' (requires 8.0+)'
    ];

    // Required extensions
    $requiredExts = ['pdo', 'pdo_mysql', 'mbstring', 'openssl', 'json', 'curl', 'gd', 'fileinfo'];
    foreach ($requiredExts as $ext) {
        $loaded = extension_loaded($ext);
        $checks[] = [
            'status' => $loaded ? 'pass' : 'fail',
            'icon' => $loaded ? 'check-circle' : 'x-circle',
            'label' => "Extension: {$ext}",
            'value' => $loaded ? 'Loaded' : 'MISSING'
        ];
    }

    // Database connection
    $dbOk = false;
    $dbError = '';
    try {
        $dsn = "mysql:host=127.0.0.1;port=3306;dbname=smart_attendance;charset=utf8mb4";
        $pdo = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $dbOk = count($tables) >= 20;
        $checks[] = [
            'status' => $dbOk ? 'pass' : 'fail',
            'icon' => $dbOk ? 'check-circle' : 'x-circle',
            'label' => 'Database Connection',
            'value' => $dbOk ? count($tables) . ' tables found' : 'Schema not imported (run database/schema.sql)'
        ];
    } catch (Exception $e) {
        $checks[] = [
            'status' => 'fail',
            'icon' => 'x-circle',
            'label' => 'Database Connection',
            'value' => $e->getMessage()
        ];
    }

    // Required directories
    $dirs = [
        'public/uploads',
        'public/uploads/employees',
        'public/uploads/faces',
        'storage/logs',
        'storage/faces'
    ];
    foreach ($dirs as $dir) {
        $path = __DIR__ . '/' . $dir;
        $exists = is_dir($path);
        $writable = $exists && is_writable($path);
        $checks[] = [
            'status' => $writable ? 'pass' : ($exists ? 'warn' : 'fail'),
            'icon' => $writable ? 'check-circle' : ($exists ? 'exclamation-triangle' : 'x-circle'),
            'label' => "Directory: {$dir}",
            'value' => $writable ? 'Writable' : ($exists ? 'Not writable' : 'Missing')
        ];
    }

    // Config check
    $configFile = __DIR__ . '/config/config.php';
    $checks[] = [
        'status' => file_exists($configFile) ? 'pass' : 'fail',
        'icon' => file_exists($configFile) ? 'check-circle' : 'x-circle',
        'label' => 'Config File',
        'value' => file_exists($configFile) ? 'Found' : 'Missing config/config.php'
    ];

    // Display checks
    foreach ($checks as $c) {
        echo "<div class='check-item {$c['status']}'>
            <i class='bi bi-{$c['icon']}' style='font-size:20px;'></i>
            <div>
                <div><strong>{$c['label']}</strong></div>
                <small>{$c['value']}</small>
            </div>
        </div>";
    }
    ?>

    <hr class="my-4">

    <h5>Next Steps:</h5>
    <ol>
        <li>If any check failed, address it before proceeding.</li>
        <li>Import <code>database/schema.sql</code> via phpMyAdmin to create the database schema.</li>
        <li>Import <code>database/seed.sql</code> to load demo data and the Super Admin account.</li>
        <li>Visit <a href="public/">public/</a> to access the login page.</li>
        <li>Login with <code>ethiennemugisha35@gmail.com</code> / <code>password</code>.</li>
    </ol>

    <div class="alert alert-info mt-3">
        <i class="bi bi-info-circle"></i> <strong>Note:</strong> Delete this file (<code>install.php</code>) after successful installation for security.
    </div>

    <a href="public/" class="btn btn-primary btn-lg w-100">
        <i class="bi bi-box-arrow-in-right"></i> Go to Login
    </a>
</div>
</body>
</html>
