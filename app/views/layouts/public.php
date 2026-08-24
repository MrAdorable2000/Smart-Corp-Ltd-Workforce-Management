<?php
/**
 * Public Layout - for home page, login, register (no sidebar)
 */
$currentUser = Auth::check() ? Auth::user() : null;
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(Session::getInstance()->csrfToken()) ?>">
    <title><?= isset($title) ? $title . ' - ' : '' ?><?= APP_NAME ?></title>

    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= Performance::asset('css/style.css') ?>" rel="stylesheet">
    <link href="<?= ASSET_URL ?>/css/public.css" rel="stylesheet">

    <script>
        (function() {
            const saved = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', saved);
        })();
    </script>
</head>
<body>
<!-- Public Navbar -->
<nav class="public-navbar">
    <div class="container">
        <div class="navbar-content">
            <a href="<?= BASE_URL ?>/" class="navbar-brand">
                <i class="bi bi-shield-check"></i>
                <span>SmartAttend</span>
            </a>

            <div class="navbar-actions">
                <button class="theme-toggle" id="themeToggle" title="Toggle theme">
                    <i class="bi bi-sun-fill sun-icon"></i>
                    <i class="bi bi-moon-stars-fill moon-icon"></i>
                </button>

                <!-- Prominent attendance button always visible -->
                <a href="<?= BASE_URL ?>/attendance/kiosk" class="btn-nav-attend" target="_blank" title="Mark Attendance">
                    <i class="bi bi-camera-video-fill"></i>
                    <span>Mark Attendance</span>
                </a>

                <?php if (Auth::check()): ?>
                    <a href="<?= BASE_URL ?>/dashboard" class="btn btn-primary btn-sm">
                        <i class="bi bi-arrow-left-circle"></i> Back to Dashboard
                    </a>
                    <div class="dropdown">
                        <button class="user-toggle" data-bs-toggle="dropdown">
                            <div class="avatar-sm">
                                <?php if (!empty($currentUser['avatar']) && file_exists(UPLOAD_PATH . '/' . $currentUser['avatar'])): ?>
                                    <img src="<?= UPLOAD_URL . '/' . $currentUser['avatar'] ?>" alt="">
                                <?php else: ?>
                                    <?= strtoupper(substr($currentUser['name'], 0, 1)) ?>
                                <?php endif; ?>
                            </div>
                            <span class="d-none d-sm-inline"><?= htmlspecialchars($currentUser['name']) ?></span>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end">
                            <a class="dropdown-item" href="<?= BASE_URL ?>/profile"><i class="bi bi-person me-2"></i>Profile</a>
                            <a class="dropdown-item text-danger" href="<?= BASE_URL ?>/logout"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/login" class="btn btn-light btn-sm">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </a>
                    <a href="<?= BASE_URL ?>/register" class="btn btn-primary btn-sm">
                        <i class="bi bi-person-plus"></i> Register
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>

<!-- Page Content -->
<main class="public-content">
    <?php if (Flash::has()):
        $flash = Flash::get(); ?>
        <div class="container mt-3">
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : $flash['type'] ?> alert-dismissible fade show">
                <?= htmlspecialchars($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
    <?php endif; ?>
    <?= $content ?? '' ?>
</main>

<!-- Public Footer -->
<footer class="public-footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-brand">
                <i class="bi bi-shield-check"></i>
                <span>SmartAttend</span>
            </div>
            <div class="footer-links">
                <a href="<?= BASE_URL ?>/">Home</a>
                <a href="<?= BASE_URL ?>/login">Login</a>
                <a href="<?= BASE_URL ?>/register">Register</a>
            </div>
            <div class="footer-copy">
                &copy; <?= date('Y') ?> <?= APP_NAME ?> v<?= APP_VERSION ?>
            </div>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
window.toggleTheme = function() {
    const current = document.documentElement.getAttribute('data-theme') || 'light';
    const next = current === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', next);
    localStorage.setItem('theme', next);
};
document.getElementById('themeToggle').addEventListener('click', window.toggleTheme);
</script>
<script src="<?= Performance::asset('js/app.js') ?>"></script>
<?php if (isset($scripts)): ?>
<script>
// Define openAnnouncement GLOBALLY so inline onclick attributes can access it
// (must be outside the load event listener)
window.openAnnouncement = function(ann, style) {
    var modalEl = document.getElementById('announcementModal');
    if (!modalEl) { alert(ann.title + '\n\n' + ann.content); return; }
    var modal = new bootstrap.Modal(modalEl);
    document.getElementById('annModalTitle').textContent = ann.title;
    document.getElementById('annModalContent').textContent = ann.content;

    var metaText = '';
    if (ann.author_name) metaText += 'By ' + ann.author_name;
    if (ann.created_at) {
        var d = new Date(ann.created_at);
        if (metaText) metaText += ' \u00b7 ';
        metaText += d.toLocaleDateString('en', {month:'short', day:'numeric', year:'numeric'});
    }
    if (ann.is_pinned) {
        if (metaText) metaText += ' \u00b7 ';
        metaText += 'Pinned';
    }
    document.getElementById('annModalMeta').textContent = metaText;

    var iconEl = document.getElementById('annModalIcon');
    iconEl.style.background = style.bg;
    iconEl.style.color = style.text;
    iconEl.innerHTML = '<i class="bi ' + style.icon + '"></i>';
    document.getElementById('annModalHeader').style.borderBottom = '4px solid ' + style.border;

    modal.show();
};
</script>
<script>window.addEventListener('load', function() { <?= $scripts ?> });</script>
<?php endif; ?>
</body>
</html>
