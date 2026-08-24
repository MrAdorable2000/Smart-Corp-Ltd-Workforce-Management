<?php
/**
 * EMERGENCY FIX SCRIPT
 * Run this ONCE in your browser to fix the "Class App\Helpers\Auth not found" error.
 * Usage: http://localhost/smart-attendance/fix.php
 *
 * This script patches:
 *   - app/views/layouts/app.php  (removes bad namespace import)
 *   - Any other view using "use App\Helpers\Auth"
 *
 * After running, DELETE this file.
 */

header('Content-Type: text/plain; charset=utf-8');
echo "=== SMART ATTENDANCE EMERGENCY FIX ===\n\n";

$root = __DIR__;
$fixes = [];
$errors = [];

// =====================================================================
// FIX 1: Patch app/views/layouts/app.php
// =====================================================================
$file = $root . '/app/views/layouts/app.php';
echo "Checking: app/views/layouts/app.php\n";
if (!file_exists($file)) {
    $errors[] = "Layout file not found at: $file";
} else {
    $content = file_get_contents($file);
    $original = $content;

    // Remove the bad namespace import line
    $content = preg_replace('/^use\s+App\\\\Helpers\\\\Auth;\s*$/m', '', $content);
    $content = preg_replace('/^use\s+App\\\\Helpers\\\\\w+;\s*$/m', '', $content);
    $content = preg_replace('/^use\s+App\\\\Core\\\\\w+;\s*$/m', '', $content);

    // If the file still has "$user = Auth::user();" without null check, make it safe
    if (preg_match('/^\$user\s*=\s*Auth::user\(\);/m', $content) &&
        !preg_match('/Auth::check\(\)\s*\?\s*Auth::user/', $content)) {
        $content = preg_replace(
            '/^\$user\s*=\s*Auth::user\(\);/m',
            '$user = Auth::check() ? Auth::user() : null;',
            $content
        );
    }

    if ($content !== $original) {
        if (is_writable($file)) {
            file_put_contents($file, $content);
            $fixes[] = "PATCHED: app/views/layouts/app.php (removed namespace import, added null-safety)";
            echo "  -> PATCHED\n";
        } else {
            $errors[] = "Cannot write to: $file (check permissions)";
            echo "  -> PERMISSION DENIED\n";
        }
    } else {
        echo "  -> Already OK (no changes needed)\n";
    }
}

// =====================================================================
// FIX 2: Scan ALL view files for bad namespace imports
// =====================================================================
echo "\nScanning all view files for namespace imports...\n";
$viewDir = $root . '/app/views';
$di = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($viewDir, RecursiveDirectoryIterator::SKIP_DOTS)
);
foreach ($di as $fileInfo) {
    if ($fileInfo->getExtension() !== 'php') continue;
    $path = $fileInfo->getPathname();
    $relPath = str_replace($root . '/', '', $path);
    $content = file_get_contents($path);
    $original = $content;

    // Remove any "use App\..." line
    $content = preg_replace('/^\s*use\s+App\\\\[\w\\\\]+;\s*$/m', '', $content);

    if ($content !== $original) {
        if (is_writable($path)) {
            file_put_contents($path, $content);
            $fixes[] = "PATCHED: $relPath (removed namespace import)";
            echo "  -> PATCHED: $relPath\n";
        } else {
            $errors[] = "Cannot write: $path";
            echo "  -> PERMISSION DENIED: $relPath\n";
        }
    }
}

// =====================================================================
// FIX 3: Verify Auth helper class exists and is loadable
// =====================================================================
echo "\nVerifying Auth helper class...\n";
$authFile = $root . '/app/helpers/Auth.php';
if (!file_exists($authFile)) {
    $errors[] = "MISSING: app/helpers/Auth.php";
    echo "  -> MISSING!\n";
} else {
    echo "  -> Found: app/helpers/Auth.php\n";

    // Check the autoloader in public/index.php
    $indexFile = $root . '/public/index.php';
    if (file_exists($indexFile)) {
        $indexContent = file_get_contents($indexFile);
        if (strpos($indexContent, 'APP_PATH . \'/helpers/\'') === false) {
            $errors[] = "Autoloader in public/index.php does not load helpers directory";
            echo "  -> WARNING: Autoloader may not load helpers\n";
        } else {
            echo "  -> Autoloader OK\n";
        }
    }
}

// =====================================================================
// FIX 4: Clear all caches (performance cache + AI insights)
// =====================================================================
echo "\nClearing caches...\n";
$cacheDir = $root . '/storage/cache';
if (is_dir($cacheDir)) {
    $files = glob($cacheDir . '/*.json');
    $cleared = 0;
    foreach ($files as $f) {
        if (unlink($f)) $cleared++;
    }
    echo "  -> Cleared {$cleared} cache files\n";
} else {
    if (mkdir($cacheDir, 0755, true)) {
        echo "  -> Created cache directory\n";
    }
}

// =====================================================================
// FIX 5: Check PHP version compatibility
// =====================================================================
echo "\nPHP Version: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    $errors[] = "PHP 8.0+ required, you have " . PHP_VERSION;
}

// =====================================================================
// REPORT
// =====================================================================
echo "\n======================================\n";
echo "FIX REPORT\n";
echo "======================================\n\n";

if (!empty($fixes)) {
    echo "✅ APPLIED FIXES (" . count($fixes) . "):\n";
    foreach ($fixes as $f) {
        echo "   - $f\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "❌ ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "   - $e\n";
    }
    echo "\n";
    echo "Please fix these errors manually, then refresh.\n";
} else if (empty($fixes)) {
    echo "ℹ️  No fixes were needed — files were already correct.\n";
    echo "   If you're still seeing the error, it may be cached.\n";
    echo "   Try: Ctrl+F5 (hard refresh) or clear browser cache.\n";
} else {
    echo "🎉 All fixes applied successfully!\n\n";
    echo "NEXT STEPS:\n";
    echo "  1. Open: http://localhost/smart-attendance/public/login\n";
    echo "  2. Login with: ethiennemugisha35@gmail.com / password\n";
    echo "  3. After confirming it works, DELETE this fix.php file\n";
}

echo "\n======================================\n";
echo "Important: Delete this file (fix.php) after use!\n";
echo "======================================\n";
