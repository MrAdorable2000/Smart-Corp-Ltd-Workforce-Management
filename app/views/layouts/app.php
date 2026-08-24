<?php
/**
 * Main Application Layout - v2.0 with Dark Mode + AI Insights
 */
$user = Auth::check() ? Auth::user() : null;
$notifCount = 0;
if (Auth::check()) {
    try {
        $notifCount = Database::getInstance()->count('notifications',
            'user_id = :uid AND status != :s',
            ['uid' => Auth::id(), 's' => 'read']);
    } catch (Exception $e) { $notifCount = 0; }
}
$userName = $user['name'] ?? 'Guest';
$userRole = $user['role_name'] ?? 'Unknown';
$userAvatar = $user['avatar'] ?? null;
$userRoleSlug = $user['role_slug'] ?? null;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(Session::getInstance()->csrfToken()) ?>">
    <title><?= isset($title) ? $title . ' - ' : '' ?><?= APP_NAME ?></title>

    <!-- Preconnect for faster CDN loads -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <link href="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.min.css" rel="stylesheet">
    <!-- Custom CSS (versioned for cache busting) -->
    <link href="<?= Performance::asset('css/style.css') ?>" rel="stylesheet">
    <?php if (isset($extraCss)): foreach ($extraCss as $css): ?>
        <link href="<?= $css ?>" rel="stylesheet">
    <?php endforeach; endif; ?>

    <!-- Apply theme IMMEDIATELY to prevent flash of wrong theme -->
    <script>
        (function() {
            const saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</head>
<body>
<div class="wrapper">
    <!-- Sidebar -->
    <nav id="sidebar" class="sidebar">
        <div class="sidebar-header">
            <a href="<?= BASE_URL ?>/dashboard" class="brand-link">
                <i class="bi bi-shield-check"></i>
                <span class="brand-text">SmartAttend</span>
            </a>
            <button id="sidebarToggle" class="btn-toggle d-md-none">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <div class="sidebar-user">
            <div class="avatar">
                <?php if (!empty($userAvatar) && file_exists(UPLOAD_PATH . '/' . $userAvatar)): ?>
                    <img src="<?= UPLOAD_URL . '/' . $userAvatar ?>" alt="">
                <?php else: ?>
                    <?= strtoupper(substr($userName, 0, 1)) ?>
                <?php endif; ?>
            </div>
            <div class="user-info">
                <div class="user-name"><?= htmlspecialchars($userName) ?></div>
                <div class="user-role"><?= htmlspecialchars($userRole) ?></div>
            </div>
        </div>

        <ul class="sidebar-menu">
            <li>
                <a href="<?= BASE_URL ?>/" class="text-info"><i class="bi bi-house-door"></i> <span>Home Page</span></a>
            </li>
            <li class="<?= Url::isActive('/dashboard') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/dashboard"><i class="bi bi-speedometer2"></i> <span>Dashboard</span></a>
            </li>

            <li class="menu-section">Workforce</li>
            <?php if (Auth::can('view_employees') || Auth::can('manage_employees')): ?>
            <li class="<?= Url::isActive('/employees') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/employees"><i class="bi bi-people"></i> <span>Employees</span></a>
            </li>
            <?php endif; ?>
            <?php if (Auth::can('manage_departments')): ?>
            <li class="<?= Url::isActive('/departments') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/departments"><i class="bi bi-diagram-3"></i> <span>Departments</span></a>
            </li>
            <?php endif; ?>
            <?php if (Auth::can('manage_shifts')): ?>
            <li class="<?= Url::isActive('/shifts') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/shifts"><i class="bi bi-clock-history"></i> <span>Shifts</span></a>
            </li>
            <?php endif; ?>

            <li class="menu-section">Attendance</li>
            <li class="<?= Url::isActive('/attendance') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/attendance"><i class="bi bi-calendar-check"></i> <span>Attendance</span></a>
            </li>
            <li class="<?= Url::isActive('/attendance/face-scan') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/attendance/face-scan"><i class="bi bi-camera-video"></i> <span>Face Check-In</span></a>
            </li>
            <li class="<?= Url::isActive('/attendance/qr') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/attendance/qr"><i class="bi bi-qr-code"></i> <span>My QR Code</span></a>
            </li>
            <li class="<?= Url::isActive('/attendance/mobile') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/attendance/mobile"><i class="bi bi-phone"></i> <span>Mobile Check-In</span></a>
            </li>
            <li class="<?= Url::isActive('/my-attendance') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/my-attendance"><i class="bi bi-speedometer2"></i> <span>My Dashboard</span></a>
            </li>
            <li class="<?= Url::isActive('/attendance/my') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/attendance/my"><i class="bi bi-person-check"></i> <span>My Attendance</span></a>
            </li>
            <li class="<?= Url::isActive('/corrections') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/corrections"><i class="bi bi-pencil-square"></i> <span>Corrections</span>
                    <?php
                    try {
                        $pendCorr = Auth::can('manage_attendance')
                            ? Database::getInstance()->count('attendance_corrections', 'status = "pending"')
                            : 0;
                        if ($pendCorr > 0): ?>
                            <span class="badge bg-warning text-dark float-end"><?= $pendCorr ?></span>
                    <?php endif; } catch(Exception $e) {} ?>
                </a>
            </li>
            <?php if (Auth::can('manage_holidays')): ?>
            <li class="<?= Url::isActive('/holidays') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/holidays"><i class="bi bi-calendar-event"></i> <span>Holidays</span></a>
            </li>
            <?php endif; ?>

            <li class="menu-section">HR</li>
            <?php if (Auth::can('apply_leave') || Auth::can('manage_leaves')): ?>
            <li class="<?= Url::isActive('/leaves') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/leaves"><i class="bi bi-airplane"></i> <span>Leave Requests</span>
                    <?php
                    $pending = 0;
                    if (Auth::check()) {
                        try { $pending = Database::getInstance()->count('leave_requests', 'status = "pending"'); } catch(Exception $e) {}
                    }
                    if ($pending > 0 && Auth::can('manage_leaves')): ?>
                        <span class="badge bg-danger float-end"><?= $pending ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (Auth::can('manage_payroll') || Auth::can('view_payroll')): ?>
            <li class="<?= Url::isActive('/payroll') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/payroll"><i class="bi bi-wallet2"></i> <span>Payroll</span></a>
            </li>
            <?php endif; ?>

            <li class="menu-section">Insights</li>
            <?php if (Auth::can('manage_attendance')): ?>
            <li class="<?= Url::isActive('/corrections/admin') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/corrections/admin"><i class="bi bi-check2-all"></i> <span>Correction Requests</span>
                    <?php try { $pc2 = Database::getInstance()->count('attendance_corrections','status="pending"'); if($pc2>0): ?>
                        <span class="badge bg-danger float-end"><?= $pc2 ?></span>
                    <?php endif; } catch(Exception $e) {} ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (Auth::can('manage_users')): ?>
            <li class="<?= Url::isActive('/registration/admin') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/registration/admin"><i class="bi bi-person-plus"></i> <span>Registration Requests</span>
                    <?php try { $pendReg = Database::getInstance()->count('registration_requests','status IN ("pending","under_review")'); if($pendReg>0): ?>
                        <span class="badge bg-warning text-dark float-end"><?= $pendReg ?></span>
                    <?php endif; } catch(Exception $e) {} ?>
                </a>
            </li>
            <?php endif; ?>
            <?php if (Auth::can('generate_reports')): ?>
            <li class="<?= Url::isActive('/reports') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/reports"><i class="bi bi-graph-up"></i> <span>Reports</span></a>
            </li>
            <?php endif; ?>
            <?php if (Auth::can('view_audit_logs')): ?>
            <li class="<?= Url::isActive('/audit-logs') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/audit-logs"><i class="bi bi-shield-exclamation"></i> <span>Audit Logs</span></a>
            </li>
            <li class="<?= Url::isActive('/database-activity') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/database-activity"><i class="bi bi-database-fill-gear"></i> <span>Database Activity</span></a>
            </li>
            <?php endif; ?>

            <li class="menu-section">System</li>
            <?php if (Auth::can('manage_users') || $userRoleSlug === 'super_admin'): ?>
            <li class="<?= Url::isActive('/users') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/users"><i class="bi bi-person-badge"></i> <span>Users</span></a>
            </li>
            <?php endif; ?>
            <?php if (Auth::can('manage_company')): ?>
            <li class="<?= Url::isActive('/company') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/company"><i class="bi bi-building"></i> <span>Company</span></a>
            </li>
            <li class="<?= Url::isActive('/branches') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/branches"><i class="bi bi-shop"></i> <span>Branches</span></a>
            </li>
            <?php endif; ?>
            <?php if (Auth::can('manage_settings')): ?>
            <li class="<?= Url::isActive('/settings') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/settings"><i class="bi bi-gear"></i> <span>Settings</span></a>
            </li>
            <li class="<?= Url::isActive('/content') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/content"><i class="bi bi-megaphone-fill"></i> <span>Content Management</span></a>
            </li>
            <?php endif; ?>
            <li class="<?= Url::isActive('/profile') ? 'active' : '' ?>">
                <a href="<?= BASE_URL ?>/profile"><i class="bi bi-person-circle"></i> <span>My Profile</span></a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/notifications"><i class="bi bi-bell"></i> <span>Notifications</span>
                    <?php if ($notifCount > 0): ?>
                        <span class="badge bg-danger float-end"><?= $notifCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?= BASE_URL ?>/logout" class="text-danger"><i class="bi bi-box-arrow-right"></i> <span>Logout</span></a>
            </li>
        </ul>
    </nav>

    <!-- Main Content -->
    <div class="main-content" id="content">
        <!-- Topbar -->
        <nav class="topbar">
            <button id="mobileMenuBtn" class="btn btn-link text-dark d-md-none">
                <i class="bi bi-list fs-4"></i>
            </button>
            <div class="page-title"><?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?></div>
            <div class="topbar-actions">
                <!-- Home Page Button -->
                <a href="<?= BASE_URL ?>/" class="btn btn-light btn-sm" title="Go to public home page">
                    <i class="bi bi-house-door"></i> <span class="d-none d-md-inline">Home</span>
                </a>
                <!-- Dark Mode Toggle -->
                <button class="theme-toggle" id="themeToggle" title="Toggle dark mode">
                    <i class="bi bi-sun-fill sun-icon"></i>
                    <i class="bi bi-moon-stars-fill moon-icon"></i>
                </button>
                <!-- Notifications -->
                <div class="dropdown">
                    <button class="btn-icon" data-bs-toggle="dropdown">
                        <i class="bi bi-bell"></i>
                        <?php if ($notifCount > 0): ?><span class="dot"></span><?php endif; ?>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end notification-dropdown">
                        <h6 class="dropdown-header">Notifications</h6>
                        <div class="notification-list">
                            <?php
                            $notifs = Auth::check() ? Database::getInstance()->fetchAll(
                                "SELECT * FROM notifications WHERE user_id = :uid ORDER BY created_at DESC LIMIT 5",
                                ['uid' => Auth::id()]
                            ) : [];
                            foreach ($notifs as $n): ?>
                                <a href="<?= BASE_URL ?>/notifications" class="dropdown-item notification-item">
                                    <div class="n-icon n-<?= htmlspecialchars($n['type']) ?>"><i class="bi bi-info-circle"></i></div>
                                    <div class="n-content">
                                        <div class="n-title"><?= htmlspecialchars($n['title']) ?></div>
                                        <div class="n-time"><?= date('M d, H:i', strtotime($n['created_at'])) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; if (empty($notifs)): ?>
                                <div class="text-center text-muted py-3">No notifications</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <!-- User Menu -->
                <div class="dropdown">
                    <button class="user-toggle" data-bs-toggle="dropdown">
                        <div class="avatar-sm">
                            <?php if (!empty($userAvatar) && file_exists(UPLOAD_PATH . '/' . $userAvatar)): ?>
                                <img src="<?= UPLOAD_URL . '/' . $userAvatar ?>" alt="">
                            <?php else: ?>
                                <?= strtoupper(substr($userName, 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <span class="d-none d-sm-inline"><?= htmlspecialchars($userName) ?></span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a class="dropdown-item" href="<?= BASE_URL ?>/profile"><i class="bi bi-person me-2"></i>Profile</a>
                        <a class="dropdown-item" href="<?= BASE_URL ?>/settings"><i class="bi bi-gear me-2"></i>Settings</a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Page Content -->
        <main class="page-content">
            <?php if (Flash::has()):
                $flash = Flash::get(); ?>
                <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($flash['message']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?= $content ?? '' ?>
        </main>

        <footer class="page-footer">
            <div>&copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?>. All rights reserved.</div>
            <div>Powered by <strong>SmartAttend AI Platform</strong></div>
        </footer>
    </div>
</div>

<!-- Scripts -->
<script>window.ASSET_URL = '<?= ASSET_URL ?>';</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Dark mode toggle (global, available before app.js loads)
window.toggleTheme = function() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
    // Update Chart.js charts to new theme colors
    if (window.Chart && Chart.instances) {
        const isDark = next === 'dark';
        Chart.defaults.color = isDark ? '#CBD5E1' : '#6B7280';
        Chart.defaults.borderColor = isDark ? 'rgba(255,255,255,.05)' : '#F3F4F6';
        Object.values(Chart.instances).forEach(c => c.update());
    }
};
document.getElementById('themeToggle').addEventListener('click', window.toggleTheme);
</script>
<script src="<?= Performance::asset('js/app.js') ?>"></script>
<?php if (isset($extraJs)): foreach ($extraJs as $js): ?>
    <script src="<?= $js ?>"></script>
<?php endforeach; endif; ?>

<!-- View-specific inline scripts (rendered AFTER all JS libraries are loaded) -->
<?php if (isset($inlineScript)): ?>
<script><?= $inlineScript ?></script>
<?php endif; ?>
<?php if (isset($scripts)): ?>
<script>
window.addEventListener('load', function() {
    <?= $scripts ?>
});
</script>
<?php endif; ?>
</body>
</html>
