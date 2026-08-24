<?php
/** @var array $data */
/** Helper: format phone for WhatsApp (remove +, spaces, dashes; ensure country code) */
function waPhone($phone) {
    if (!$phone) return '';
    $clean = preg_replace('/[^0-9]/', '', $phone);
    // If doesn't start with country code, assume +1 (US)
    if (strlen($clean) === 10) $clean = '1' . $clean;
    return $clean;
}
?>
<!-- ===== HERO SECTION ===== -->
<section class="hero-section">
    <div class="container">
        <div class="hero-content">
            <span class="hero-badge"><i class="bi bi-stars"></i> AI-Powered Workforce Management</span>
            <h1 class="hero-title"><?= htmlspecialchars($company['name'] ?? 'Smart Employee Attendance') ?><br>& Workforce Management</h1>
            <p class="hero-subtitle">
                Face recognition attendance, AI-powered analytics, real-time insights, and complete HR automation.
                Built for organizations, schools, hospitals, factories, and government offices.
            </p>
            <div class="hero-actions">
                <?php if (Auth::check()): ?>
                    <a href="<?= BASE_URL ?>/dashboard" class="btn-hero btn-hero-primary">
                        <i class="bi bi-speedometer2"></i> Go to Dashboard
                    </a>
                    <a href="<?= BASE_URL ?>/attendance/kiosk" class="btn-hero btn-hero-secondary" target="_blank">
                        <i class="bi bi-camera-video"></i> Mark Attendance
                    </a>
                <?php else: ?>
                    <a href="<?= BASE_URL ?>/attendance/kiosk" class="btn-hero btn-hero-primary" target="_blank">
                        <i class="bi bi-camera-video-fill"></i> Mark Attendance
                    </a>
                    <a href="<?= BASE_URL ?>/register" class="btn-hero btn-hero-secondary">
                        <i class="bi bi-person-plus"></i> Self Register
                    </a>
                    <a href="<?= BASE_URL ?>/login" class="btn-hero btn-hero-secondary" style="opacity:.7;font-size:13px;padding:8px 20px;">
                        <i class="bi bi-box-arrow-in-right"></i> Admin Login
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- ===== ATTENDANCE STATION ===== -->
<section class="att-station-section">

    <!-- Background decoration -->
    <div class="att-station-bg"></div>

    <div class="container" style="position:relative;z-index:1">

        <!-- Header -->
        <div class="att-station-head">
            <div class="att-station-pill">
                <span class="att-live-dot"></span>
                Attendance Station — Always Open
            </div>
            <h2 class="att-station-title">Mark Your Attendance</h2>
            <p class="att-station-sub">Three ways to record attendance — fast, secure, and reliable. No login required for Face &amp; QR methods.</p>
        </div>

        <!-- Method Cards -->
        <div class="att-cards-grid">

            <!-- CARD 1: Face Recognition -->
            <a href="<?= BASE_URL ?>/attendance/kiosk" class="att-card att-card-face" target="_blank">
                <div class="att-card-glow"></div>
                <div class="att-card-inner">
                    <div class="att-card-icon-wrap">
                        <div class="att-card-icon">
                            <i class="bi bi-camera-video-fill"></i>
                        </div>
                        <div class="att-card-icon-ring"></div>
                    </div>
                    <div class="att-card-content">
                        <div class="att-card-badge att-badge-free">
                            <i class="bi bi-lightning-fill"></i> No Login Needed
                        </div>
                        <h3 class="att-card-title">Face Recognition</h3>
                        <p class="att-card-desc">AI instantly identifies you from the camera. Just look at the screen — attendance records automatically.</p>
                        <div class="att-card-features">
                            <span><i class="bi bi-check2"></i> Auto detect</span>
                            <span><i class="bi bi-check2"></i> Under 5 seconds</span>
                            <span><i class="bi bi-check2"></i> 90%+ accuracy</span>
                            <span><i class="bi bi-check2"></i> Anti-spoof protection</span>
                        </div>
                    </div>
                    <div class="att-card-cta">
                        <span class="att-cta-btn att-cta-face">
                            Open Kiosk <i class="bi bi-arrow-right ms-1"></i>
                        </span>
                    </div>
                </div>
            </a>

            <!-- CARD 2: QR Code -->
            <a href="<?= BASE_URL ?>/attendance/qr-scan" class="att-card att-card-qr" target="_blank">
                <div class="att-card-glow"></div>
                <div class="att-card-inner">
                    <div class="att-card-icon-wrap">
                        <div class="att-card-icon">
                            <i class="bi bi-qr-code-scan"></i>
                        </div>
                        <div class="att-card-icon-ring"></div>
                    </div>
                    <div class="att-card-content">
                        <div class="att-card-badge att-badge-free">
                            <i class="bi bi-shield-check"></i> No Login Needed
                        </div>
                        <h3 class="att-card-title">QR Code Scan</h3>
                        <p class="att-card-desc">Show your encrypted personal QR code to any scanner. Works on your phone or printed on your badge.</p>
                        <div class="att-card-features">
                            <span><i class="bi bi-check2"></i> AES-256 encrypted</span>
                            <span><i class="bi bi-check2"></i> Camera scanner</span>
                            <span><i class="bi bi-check2"></i> Manual token entry</span>
                            <span><i class="bi bi-check2"></i> Anti-duplicate guard</span>
                        </div>
                    </div>
                    <div class="att-card-cta">
                        <span class="att-cta-btn att-cta-qr">
                            Scan QR <i class="bi bi-arrow-right ms-1"></i>
                        </span>
                    </div>
                </div>
            </a>

            <!-- CARD 3: Mobile GPS -->
            <a href="<?= BASE_URL ?>/attendance/mobile" class="att-card att-card-gps">
                <div class="att-card-glow"></div>
                <div class="att-card-inner">
                    <div class="att-card-icon-wrap">
                        <div class="att-card-icon">
                            <i class="bi bi-phone-fill"></i>
                        </div>
                        <div class="att-card-icon-ring"></div>
                    </div>
                    <div class="att-card-content">
                        <div class="att-card-badge att-badge-login">
                            <i class="bi bi-person-check"></i> Login Required
                        </div>
                        <h3 class="att-card-title">Mobile Check-In</h3>
                        <p class="att-card-desc">Check in from your phone with GPS verification and a selfie. Only works within authorized office radius.</p>
                        <div class="att-card-features">
                            <span><i class="bi bi-check2"></i> GPS geofencing</span>
                            <span><i class="bi bi-check2"></i> Selfie verification</span>
                            <span><i class="bi bi-check2"></i> 200m radius</span>
                            <span><i class="bi bi-check2"></i> Fraud detection</span>
                        </div>
                    </div>
                    <div class="att-card-cta">
                        <span class="att-cta-btn att-cta-gps">
                            Open Mobile <i class="bi bi-arrow-right ms-1"></i>
                        </span>
                    </div>
                </div>
            </a>

        </div>

        <!-- Bottom info bar -->
        <div class="att-info-bar">
            <div class="att-info-clock">
                <div class="att-info-time" id="homeClockTime">--:--:--</div>
                <div class="att-info-date" id="homeClockDate"></div>
            </div>
            <div class="att-info-divider"></div>
            <div class="att-info-rules">
                <div class="att-rule"><i class="bi bi-clock"></i> Shift: <strong>08:00 AM – 05:00 PM</strong></div>
                <div class="att-rule"><i class="bi bi-hourglass-split"></i> Grace period: <strong>15 minutes</strong></div>
                <div class="att-rule"><i class="bi bi-shield-lock"></i> All sessions <strong>encrypted &amp; logged</strong></div>
            </div>
            <div class="att-info-divider"></div>
            <div class="att-info-help">
                <div class="att-help-title">Need help?</div>
                <a href="<?= BASE_URL ?>/corrections" class="att-help-link"><i class="bi bi-pencil-square me-1"></i>Request correction</a>
                <a href="<?= BASE_URL ?>/login" class="att-help-link"><i class="bi bi-person-circle me-1"></i>Employee portal</a>
            </div>
        </div>

    </div>
</section>

<!-- ===== STATS SECTION ===== -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-tile">
                <div class="stat-tile-icon" style="background: linear-gradient(135deg, #EDE9FE, #DDD6FE); color: #7C3AED;">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-tile-value"><?= number_format($stats['employees']) ?></div>
                <div class="stat-tile-label">Active Employees</div>
            </div>
            <div class="stat-tile">
                <div class="stat-tile-icon" style="background: linear-gradient(135deg, #D1FAE5, #A7F3D0); color: #059669;">
                    <i class="bi bi-person-badge-fill"></i>
                </div>
                <div class="stat-tile-value"><?= number_format($stats['users']) ?></div>
                <div class="stat-tile-label">System Users</div>
            </div>
            <div class="stat-tile">
                <div class="stat-tile-icon" style="background: linear-gradient(135deg, #DBEAFE, #BFDBFE); color: #2563EB;">
                    <i class="bi bi-diagram-3-fill"></i>
                </div>
                <div class="stat-tile-value"><?= number_format($stats['departments']) ?></div>
                <div class="stat-tile-label">Departments</div>
            </div>
            <div class="stat-tile">
                <div class="stat-tile-icon" style="background: linear-gradient(135deg, #FEF3C7, #FDE68A); color: #D97706;">
                    <i class="bi bi-calendar-check-fill"></i>
                </div>
                <div class="stat-tile-value"><?= $stats['present_today'] ?></div>
                <div class="stat-tile-label">Present Today</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== ANALYTICS SECTION ===== -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">System Analytics</h2>
            <p class="section-subtitle">Real-time insights into your workforce distribution</p>
        </div>
        <div class="row g-3">
            <div class="col-lg-6">
                <div class="analytics-card">
                    <div class="analytics-card-title">
                        <i class="bi bi-bar-chart-fill text-primary"></i> Users by Role
                    </div>
                    <div class="analytics-card-subtitle">Distribution of system users across roles</div>
                    <?php
                    $roleColors = [
                        'super_admin' => '#7C3AED',
                        'hr_manager' => '#059669',
                        'department_manager' => '#2563EB',
                        'employee' => '#D97706',
                        'auditor' => '#CA8A04',
                    ];
                    $maxRole = max(array_column($roleDistribution, 'count') ?: [1]);
                    foreach ($roleDistribution as $r):
                        $pct = $maxRole > 0 ? ($r['count'] / $maxRole) * 100 : 0;
                        $color = $roleColors[$r['slug']] ?? '#6B7280';
                    ?>
                    <div class="role-bar-item">
                        <div class="role-bar-label"><?= htmlspecialchars($r['name']) ?></div>
                        <div class="role-bar-track">
                            <div class="role-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>;"></div>
                        </div>
                        <div class="role-bar-count"><?= $r['count'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="analytics-card">
                    <div class="analytics-card-title">
                        <i class="bi bi-pie-chart-fill text-success"></i> Employees by Department
                    </div>
                    <div class="analytics-card-subtitle">Workforce distribution across departments</div>
                    <?php
                    $deptColors = ['#6366F1','#10B981','#F59E0B','#EF4444','#3B82F6','#8B5CF6','#EC4899','#14B8A6'];
                    $maxDept = max(array_column($deptDistribution, 'count') ?: [1]);
                    foreach ($deptDistribution as $i => $d):
                        $pct = $maxDept > 0 ? ($d['count'] / $maxDept) * 100 : 0;
                        $color = $deptColors[$i % count($deptColors)];
                    ?>
                    <div class="role-bar-item">
                        <div class="role-bar-label"><?= htmlspecialchars($d['name']) ?></div>
                        <div class="role-bar-track">
                            <div class="role-bar-fill" style="width: <?= $pct ?>%; background: <?= $color ?>;"></div>
                        </div>
                        <div class="role-bar-count"><?= $d['count'] ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== USER DIRECTORY ===== -->
<section class="section" style="background: var(--bg-soft);">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">System Users Directory</h2>
            <p class="section-subtitle">Connect with our team — click any contact icon to reach out via WhatsApp, email, or phone</p>
        </div>
        <div class="row g-3">
            <?php foreach ($users as $u):
                $initials = strtoupper(substr($u['name'], 0, 1) . (strpos($u['name'], ' ') !== false ? substr($u['name'], strpos($u['name'], ' ') + 1, 1) : ''));
                $waPhone = waPhone($u['phone'] ?? '');
                $waMsg = urlencode("Hello {$u['name']}, I'm reaching out from the Smart Attendance system.");
            ?>
            <div class="col-md-6 col-lg-4 col-xl-3">
                <div class="user-card">
                    <div class="user-avatar">
                        <?php if (!empty($u['avatar']) && file_exists(UPLOAD_PATH . '/' . $u['avatar'])): ?>
                            <img src="<?= UPLOAD_URL . '/' . $u['avatar'] ?>" alt="<?= htmlspecialchars($u['name']) ?>">
                        <?php elseif (!empty($u['photo']) && file_exists(UPLOAD_PATH . '/' . $u['photo'])): ?>
                            <img src="<?= UPLOAD_URL . '/' . $u['photo'] ?>" alt="<?= htmlspecialchars($u['name']) ?>">
                        <?php else: ?>
                            <?= $initials ?>
                        <?php endif; ?>
                    </div>
                    <div class="user-name"><?= htmlspecialchars($u['name']) ?></div>
                    <span class="user-role <?= htmlspecialchars($u['role_slug']) ?>"><?= htmlspecialchars($u['role_name']) ?></span>
                    <div class="user-position"><?= htmlspecialchars($u['position'] ?: '-') ?></div>
                    <div class="user-department">
                        <?php if ($u['employee_code']): ?>
                            <i class="bi bi-person-badge"></i> <?= htmlspecialchars($u['employee_code']) ?>
                            <?php if ($u['department_name']): ?> · <?= htmlspecialchars($u['department_name']) ?><?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">System Account</span>
                        <?php endif; ?>
                    </div>
                    <div class="user-contact">
                        <?php if ($waPhone): ?>
                        <a href="https://wa.me/<?= $waPhone ?>?text=<?= $waMsg ?>" target="_blank" class="contact-btn whatsapp" title="WhatsApp: <?= htmlspecialchars($u['phone']) ?>">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($u['email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($u['email']) ?>" class="contact-btn email" title="Email: <?= htmlspecialchars($u['email']) ?>">
                            <i class="bi bi-envelope-fill"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (!empty($u['phone'])): ?>
                        <a href="tel:<?= htmlspecialchars($u['phone']) ?>" class="contact-btn phone" title="Call: <?= htmlspecialchars($u['phone']) ?>">
                            <i class="bi bi-telephone-fill"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
            <div class="col-12 text-center text-muted py-5">
                <i class="bi bi-people display-4 d-block mb-2 opacity-25"></i>
                <p>No users found.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- ===== ANNOUNCEMENTS + WEATHER ROW ===== -->
<section class="content-section">
    <div class="container">
        <div class="content-row-2col">
            <!-- Announcements -->
            <div class="announcements-panel">
                <div class="panel-header">
                    <div class="panel-title-wrap">
                        <div class="panel-icon panel-icon-warning">
                            <i class="bi bi-megaphone-fill"></i>
                        </div>
                        <div>
                            <h3 class="panel-title">Announcements</h3>
                            <p class="panel-subtitle">Latest news &amp; updates</p>
                        </div>
                    </div>
                    <span class="live-badge"><span class="live-dot"></span>LIVE</span>
                </div>
                <div class="announcements-list">
                    <?php if (!empty($announcements)): ?>
                        <?php foreach ($announcements as $a):
                            $typeStyles = [
                                'info'    => ['bg' => 'rgba(59,130,247,.08)', 'border' => '#3B82F6', 'icon' => 'bi-info-circle-fill', 'text' => '#2563EB'],
                                'success' => ['bg' => 'rgba(16,185,129,.08)', 'border' => '#10B981', 'icon' => 'bi-check-circle-fill', 'text' => '#059669'],
                                'warning' => ['bg' => 'rgba(245,158,11,.08)', 'border' => '#F59E0B', 'icon' => 'bi-exclamation-triangle-fill', 'text' => '#D97706'],
                                'danger'  => ['bg' => 'rgba(239,68,68,.08)', 'border' => '#EF4444', 'icon' => 'bi-x-circle-fill', 'text' => '#DC2626'],
                                'primary' => ['bg' => 'rgba(99,102,241,.08)', 'border' => '#6366F1', 'icon' => 'bi-star-fill', 'text' => '#4F46E5'],
                            ];
                            $s = $typeStyles[$a['type']] ?? $typeStyles['info'];
                            $contentLen = strlen($a['content']);
                            $isLong = $contentLen > 150;
                            $previewContent = $isLong ? substr($a['content'], 0, 150) . '...' : $a['content'];
                        ?>
                        <div class="announcement-card" style="border-left-color: <?= $s['border'] ?>;" 
                             onclick="openAnnouncement(<?= htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars(json_encode($s), ENT_QUOTES, 'UTF-8') ?>)">
                            <div class="announcement-card-body">
                                <div class="announcement-card-top">
                                    <div class="announcement-type-icon" style="background: <?= $s['bg'] ?>; color: <?= $s['text'] ?>;">
                                        <i class="bi <?= $s['icon'] ?>"></i>
                                    </div>
                                    <div class="announcement-card-content">
                                        <div class="announcement-card-title">
                                            <?= htmlspecialchars($a['title']) ?>
                                            <?php if ($a['is_pinned']): ?>
                                                <span class="pin-badge"><i class="bi bi-pin-angle-fill"></i></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="announcement-card-meta">
                                            <i class="bi bi-person"></i> <?= htmlspecialchars($a['author_name'] ?? 'Admin') ?>
                                            <span class="meta-dot">&middot;</span>
                                            <i class="bi bi-clock"></i> <?= date('M d, Y', strtotime($a['created_at'])) ?>
                                        </div>
                                    </div>
                                </div>
                                <p class="announcement-card-text"><?= nl2br(htmlspecialchars($previewContent)) ?></p>
                                <?php if ($isLong): ?>
                                <div class="announcement-read-more">
                                    <i class="bi bi-arrow-right-circle"></i> Read more
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state-box">
                            <i class="bi bi-megaphone"></i>
                            <h4>No Announcements</h4>
                            <p>Check back later for updates from administration.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Weather Widget -->
            <div class="weather-panel">
                <div class="weather-card-glass">
                    <div class="weather-header">
                        <div class="weather-location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <div>
                                <div class="weather-city"><?= $weather ? htmlspecialchars($weather['city_name']) : 'Not Configured' ?></div>
                                <div class="weather-country"><?= $weather ? htmlspecialchars($weather['country'] ?: '') : 'Admin can set up weather' ?></div>
                            </div>
                        </div>
                        <div class="weather-refresh">
                            <i class="bi bi-clock-history"></i>
                            <small><?= $weather ? date('H:i', strtotime($weather['fetched_at'])) : '--:--' ?></small>
                        </div>
                    </div>

                    <?php if ($weather): ?>
                    <div class="weather-main">
                        <div class="weather-icon-large">
                            <i class="bi bi-<?= htmlspecialchars($weather['icon']) ?>"></i>
                        </div>
                        <div class="weather-temp-main">
                            <?= number_format($weather['temperature'], 0) ?><span class="weather-temp-unit">&deg;C</span>
                        </div>
                    </div>
                    <div class="weather-desc"><?= htmlspecialchars($weather['description']) ?></div>
                    <div class="weather-stats-grid">
                        <div class="weather-stat">
                            <i class="bi bi-thermometer-half"></i>
                            <div>
                                <div class="weather-stat-val"><?= number_format($weather['feels_like'], 0) ?>&deg;</div>
                                <div class="weather-stat-lbl">Feels Like</div>
                            </div>
                        </div>
                        <div class="weather-stat">
                            <i class="bi bi-droplet-fill"></i>
                            <div>
                                <div class="weather-stat-val"><?= $weather['humidity'] ?>%</div>
                                <div class="weather-stat-lbl">Humidity</div>
                            </div>
                        </div>
                        <div class="weather-stat">
                            <i class="bi bi-wind"></i>
                            <div>
                                <div class="weather-stat-val"><?= number_format($weather['wind_speed'], 0) ?></div>
                                <div class="weather-stat-lbl">km/h Wind</div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="weather-empty">
                        <i class="bi bi-cloud-slash"></i>
                        <p>Weather not configured</p>
                        <small>Admin &rarr; Content Management &rarr; Weather</small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CALENDAR + UPCOMING EVENTS ===== -->
<section class="content-section content-section-alt">
    <div class="container">
        <div class="section-header-center">
            <h2 class="section-title-lg"><i class="bi bi-calendar3"></i> Calendar &amp; Events</h2>
            <p class="section-subtitle-lg">Stay on top of meetings, holidays, and important dates</p>
        </div>
        <div class="content-row-2col">
            <!-- Calendar -->
            <div class="calendar-panel">
                <div class="panel-header">
                    <div class="panel-title-wrap">
                        <div class="panel-icon panel-icon-primary">
                            <i class="bi bi-calendar3"></i>
                        </div>
                        <div>
                            <h3 class="panel-title" id="calMonthYear"><?= date('F Y') ?></h3>
                            <p class="panel-subtitle">Click dates to see events</p>
                        </div>
                    </div>
                    <div class="cal-nav">
                        <button class="cal-nav-btn" id="calPrev"><i class="bi bi-chevron-left"></i></button>
                        <button class="cal-nav-btn cal-nav-today" id="calToday">Today</button>
                        <button class="cal-nav-btn" id="calNext"><i class="bi bi-chevron-right"></i></button>
                    </div>
                </div>
                <div class="calendar-body">
                    <div id="calendarGrid"></div>
                </div>
            </div>

            <!-- Upcoming Events -->
            <div class="events-panel">
                <div class="panel-header">
                    <div class="panel-title-wrap">
                        <div class="panel-icon panel-icon-success">
                            <i class="bi bi-calendar-event-fill"></i>
                        </div>
                        <div>
                            <h3 class="panel-title">Upcoming</h3>
                            <p class="panel-subtitle">Next events &amp; meetings</p>
                        </div>
                    </div>
                </div>
                <div class="events-list">
                    <?php if (!empty($todayEvents)): ?>
                    <div class="events-today-banner">
                        <i class="bi bi-stars"></i> Today
                    </div>
                    <?php foreach ($todayEvents as $te): ?>
                    <div class="event-row event-row-today">
                        <div class="event-dot-color" style="background: <?= htmlspecialchars($te['color']) ?>;"></div>
                        <div class="event-row-body">
                            <div class="event-row-title"><?= htmlspecialchars($te['title']) ?></div>
                            <div class="event-row-meta">
                                <i class="bi bi-clock"></i> <?= $te['start_time'] ? date('g:i A', strtotime($te['start_time'])) : 'All day' ?>
                                <?php if ($te['location']): ?><span class="meta-dot">&middot;</span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($te['location']) ?><?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <?php
                    $upcomingCount = 0;
                    foreach ($events as $e):
                        if ($e['event_date'] == date('Y-m-d')) continue;
                        if ($upcomingCount >= 6) break;
                        $upcomingCount++;
                        $ed = strtotime($e['event_date']);
                    ?>
                    <div class="event-row">
                        <div class="event-date-pill" style="background: <?= htmlspecialchars($e['color']) ?>;">
                            <span class="event-date-day"><?= date('d', $ed) ?></span>
                            <span class="event-date-mon"><?= date('M', $ed) ?></span>
                        </div>
                        <div class="event-row-body">
                            <div class="event-row-title"><?= htmlspecialchars($e['title']) ?></div>
                            <div class="event-row-meta">
                                <i class="bi bi-calendar3"></i> <?= date('D, M d', $ed) ?>
                                <?= $e['start_time'] ? '<span class="meta-dot">&middot;</span><i class="bi bi-clock"></i> ' . date('g:i A', strtotime($e['start_time'])) : '' ?>
                                <?php if ($e['location']): ?><span class="meta-dot">&middot;</span><i class="bi bi-geo-alt"></i> <?= htmlspecialchars($e['location']) ?><?php endif; ?>
                            </div>
                        </div>
                        <span class="event-type-badge event-type-<?= htmlspecialchars($e['type']) ?>"><?= ucfirst($e['type']) ?></span>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($events)): ?>
                    <div class="empty-state-box">
                        <i class="bi bi-calendar-x"></i>
                        <h4>No Events</h4>
                        <p>No upcoming events scheduled.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== YOUTUBE VIDEOS ===== -->
<?php if (!empty($youtubeVideos)): ?>
<section class="content-section">
    <div class="container">
        <div class="section-header-center">
            <h2 class="section-title-lg"><i class="bi bi-youtube text-danger"></i> Video Gallery</h2>
            <p class="section-subtitle-lg">Training, tutorials &amp; important videos</p>
        </div>
        <div class="youtube-grid">
            <?php foreach ($youtubeVideos as $y): ?>
            <a href="<?= htmlspecialchars($y['url']) ?>" target="_blank" class="yt-card-link">
                <div class="yt-card">
                    <div class="yt-thumb-wrap">
                        <?php if ($y['thumbnail']): ?>
                        <div class="yt-thumb" style="background-image: url('<?= htmlspecialchars($y['thumbnail']) ?>');"></div>
                        <?php else: ?>
                        <div class="yt-thumb yt-thumb-default"><i class="bi bi-youtube"></i></div>
                        <?php endif; ?>
                        <div class="yt-play-btn"><i class="bi bi-play-circle-fill"></i></div>
                        <?php if ($y['is_featured']): ?>
                        <span class="yt-featured"><i class="bi bi-star-fill"></i> Featured</span>
                        <?php endif; ?>
                    </div>
                    <div class="yt-card-body">
                        <span class="yt-category"><?= htmlspecialchars($y['category']) ?></span>
                        <h4 class="yt-title"><?= htmlspecialchars($y['title']) ?></h4>
                        <?php if ($y['description']): ?>
                        <p class="yt-desc"><?= htmlspecialchars(substr($y['description'], 0, 80)) ?><?= strlen($y['description']) > 80 ? '...' : '' ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ===== FEATURES SECTION ===== -->
<section class="features-section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Powerful Features</h2>
            <p class="section-subtitle">Everything you need to manage your workforce efficiently</p>
        </div>
        <div class="row g-3">
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon"><i class="bi bi-camera-video-fill"></i></div>
                    <h3 class="feature-title">Face Recognition</h3>
                    <p class="feature-desc">AI-powered attendance with anti-spoofing. Blink detection, head movement, and liveness checks.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #10B981, #059669);"><i class="bi bi-cpu-fill"></i></div>
                    <h3 class="feature-title">AI Analytics</h3>
                    <p class="feature-desc">Predictive insights, anomaly detection, productivity scoring, and smart recommendations.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);"><i class="bi bi-geo-alt-fill"></i></div>
                    <h3 class="feature-title">GPS Geofencing</h3>
                    <p class="feature-desc">Location-based attendance with office radius verification. Prevents remote check-ins.</p>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="feature-card">
                    <div class="feature-icon" style="background: linear-gradient(135deg, #EF4444, #DC2626);"><i class="bi bi-shield-check-fill"></i></div>
                    <h3 class="feature-title">Enterprise Security</h3>
                    <p class="feature-desc">CSRF protection, 2FA OTP, audit logs, RBAC, bcrypt hashing, and session security.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== CTA SECTION ===== -->
<section class="cta-section">
    <div class="container">
        <h2 class="cta-title">Ready to Get Started?</h2>
        <p class="cta-subtitle">Login now or register for an account. New registrations require admin approval.</p>
        <div class="hero-actions" style="justify-content: center;">
            <?php if (Auth::check()): ?>
                <a href="<?= BASE_URL ?>/dashboard" class="btn-hero btn-hero-primary">
                    <i class="bi bi-speedometer2"></i> Go to Dashboard
                </a>
            <?php else: ?>
                <a href="<?= BASE_URL ?>/login" class="btn-hero btn-hero-primary">
                    <i class="bi bi-box-arrow-in-right"></i> Login Now
                </a>
                <a href="<?= BASE_URL ?>/register" class="btn-hero btn-hero-secondary">
                    <i class="bi bi-person-plus"></i> Create Account
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php
// Calendar + content management JavaScript (loaded after jQuery via layout)
$eventsJson = json_encode($events);
$scripts = "
    // ============ CALENDAR ============
    var events = {$eventsJson} || [];
    var calDate = new Date();

    function renderCalendar() {
        var year = calDate.getFullYear();
        var month = calDate.getMonth();
        var monthNames = ['January','February','March','April','May','June','July','August','September','October','November','December'];
        document.getElementById('calMonthYear').textContent = monthNames[month] + ' ' + year;

        var firstDay = new Date(year, month, 1).getDay();
        var daysInMonth = new Date(year, month + 1, 0).getDate();
        var today = new Date();
        var todayStr = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

        var dowNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
        var html = '<div class=\"cal-grid\">';
        for (var w = 0; w < 7; w++) {
            html += '<div class=\"cal-dow\">' + dowNames[w] + '</div>';
        }

        for (var i = 0; i < firstDay; i++) {
            html += '<div class=\"cal-cell cal-cell-empty\"></div>';
        }

        for (var d = 1; d <= daysInMonth; d++) {
            var dateStr = year + '-' + String(month + 1).padStart(2, '0') + '-' + String(d).padStart(2, '0');
            var dayEvents = events.filter(function(e) { return e.event_date === dateStr; });
            var isToday = dateStr === todayStr;
            var classes = 'cal-cell';
            if (isToday) classes += ' cal-cell-today';
            if (dayEvents.length > 0) classes += ' cal-cell-has-events';

            var titleAttr = dayEvents.length > 0 ? dayEvents.map(function(e){return e.title;}).join(', ') : '';
            html += '<div class=\"' + classes + '\"' + (titleAttr ? ' title=\"' + titleAttr + '\"' : '') + '>';
            html += '<div class=\"cal-cell-num\">' + d + '</div>';
            if (dayEvents.length > 0) {
                html += '<div class=\"cal-cell-dots\">';
                dayEvents.slice(0, 3).forEach(function(e) {
                    html += '<div class=\"cal-cell-dot\" style=\"background:' + e.color + ';\"></div>';
                });
                html += '</div>';
            }
            html += '</div>';
        }
        html += '</div>';
        document.getElementById('calendarGrid').innerHTML = html;
    }

    document.getElementById('calPrev').addEventListener('click', function() {
        calDate.setMonth(calDate.getMonth() - 1);
        renderCalendar();
    });
    document.getElementById('calNext').addEventListener('click', function() {
        calDate.setMonth(calDate.getMonth() + 1);
        renderCalendar();
    });
    document.getElementById('calToday').addEventListener('click', function() {
        calDate = new Date();
        renderCalendar();
    });
    renderCalendar();
";
?>

<!-- ===== ANNOUNCEMENT DETAIL MODAL ===== -->
<div class="modal fade" id="announcementModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content announcement-modal-content">
            <div class="modal-header announcement-modal-header" id="annModalHeader">
                <div class="d-flex align-items-center gap-3">
                    <div class="announcement-modal-icon" id="annModalIcon">
                        <i class="bi bi-info-circle-fill"></i>
                    </div>
                    <div>
                        <h5 class="modal-title" id="annModalTitle">Announcement</h5>
                        <div class="announcement-modal-meta" id="annModalMeta"></div>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body announcement-modal-body">
                <div class="announcement-modal-content-text" id="annModalContent"></div>
            </div>
            <div class="modal-footer announcement-modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    <i class="bi bi-x-lg"></i> Close
                </button>
            </div>
        </div>
    </div>
</div>

<style>
/* Announcement Modal Styles */
.announcement-modal-content {
    border-radius: 16px;
    border: none;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,.15);
}
.announcement-modal-header {
    background: linear-gradient(135deg, #6366F1, #7C3AED);
    color: #fff;
    border: none;
    padding: 20px 24px;
}
.announcement-modal-icon {
    width: 48px; height: 48px;
    border-radius: 12px;
    background: rgba(255,255,255,.2);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
    flex-shrink: 0;
}
.modal-title {
    color: #fff;
    font-size: 18px;
    font-weight: 700;
    line-height: 1.3;
}
.announcement-modal-meta {
    font-size: 12px;
    color: rgba(255,255,255,.7);
    margin-top: 2px;
}
.announcement-modal-body {
    padding: 24px 28px;
    max-height: 60vh;
    overflow-y: auto;
}
.announcement-modal-content-text {
    font-size: 15px;
    line-height: 1.8;
    color: var(--text);
    white-space: pre-wrap;
}
.announcement-modal-footer {
    border: none;
    padding: 12px 24px 20px;
    justify-content: center;
}
.announcement-modal-footer .btn {
    padding: 10px 28px;
    border-radius: 10px;
    font-weight: 600;
}

/* Read More link */
.announcement-read-more {
    margin-top: 8px;
    padding-left: 48px;
    font-size: 12px;
    font-weight: 600;
    color: var(--primary);
    display: flex;
    align-items: center;
    gap: 4px;
    transition: gap .15s;
}
.announcement-card:hover .announcement-read-more {
    gap: 8px;
}
@media (max-width: 768px) {
    .announcement-read-more { padding-left: 0; }
    .announcement-modal-content-text { font-size: 14px; }
    .modal-title { font-size: 16px; }
}

/* Make announcement cards look clickable */
.announcement-card {
    cursor: pointer;
    position: relative;
}
.announcement-card::after {
    content: '';
    position: absolute;
    top: 0; right: 0;
    width: 3px;
    height: 100%;
    background: transparent;
    transition: background .15s;
}
.announcement-card:hover::after {
    background: var(--primary);
}
</style>
<script>
(function() {
    function updateHomeClock() {
        var now  = new Date();
        var time = now.toLocaleTimeString('en-US', {hour:'2-digit', minute:'2-digit', second:'2-digit', hour12: false});
        var date = now.toLocaleDateString('en-US', {weekday:'long', year:'numeric', month:'long', day:'numeric'});
        var tEl  = document.getElementById('homeClockTime');
        var dEl  = document.getElementById('homeClockDate');
        if (tEl) tEl.textContent = time;
        if (dEl) dEl.textContent = date;
    }
    updateHomeClock();
    setInterval(updateHomeClock, 1000);
})();
</script>
