<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Content Management</h4>
        <p class="text-muted mb-0">Manage announcements, YouTube videos, weather, and calendar events</p>
    </div>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-violet">
            <div class="stat-icon"><i class="bi bi-megaphone-fill"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['announcements'] ?></div>
                <div class="stat-label">Announcements (<?= $stats['active_announcements'] ?> active)</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-rose">
            <div class="stat-icon"><i class="bi bi-youtube"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['youtube'] ?></div>
                <div class="stat-label">YouTube Videos</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-emerald">
            <div class="stat-icon"><i class="bi bi-calendar-event-fill"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['events'] ?></div>
                <div class="stat-label">Calendar Events</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-sky">
            <div class="stat-icon"><i class="bi bi-cloud-sun-fill"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $weather ? '1' : '0' ?></div>
                <div class="stat-label">Weather Configured</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3" id="contentTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#announcements-tab" type="button">
            <i class="bi bi-megaphone-fill"></i> Announcements
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#youtube-tab" type="button">
            <i class="bi bi-youtube"></i> YouTube
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#events-tab" type="button">
            <i class="bi bi-calendar3"></i> Events
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#weather-tab" type="button">
            <i class="bi bi-cloud-sun-fill"></i> Weather
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- ============ ANNOUNCEMENTS TAB ============ -->
    <div class="tab-pane fade show active" id="announcements-tab">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><i class="bi bi-plus-circle me-2"></i>New Announcement</div>
                    <div class="card-body">
                        <form method="POST" action="<?= BASE_URL ?>/content/announcements/store">
                            <?= $csrf ?>
                            <div class="mb-3">
                                <label class="form-label required">Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">Content</label>
                                <textarea name="content" class="form-control" rows="4" required></textarea>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-select">
                                        <option value="info">Info (Blue)</option>
                                        <option value="success">Success (Green)</option>
                                        <option value="warning">Warning (Yellow)</option>
                                        <option value="danger">Danger (Red)</option>
                                        <option value="primary">Primary (Purple)</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Pinned?</label>
                                    <select name="is_pinned" class="form-select">
                                        <option value="0">No</option>
                                        <option value="1">Yes (stays on top)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Start Date (optional)</label>
                                    <input type="datetime-local" name="start_date" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">End Date (optional)</label>
                                    <input type="datetime-local" name="end_date" class="form-control">
                                </div>
                            </div>
                            <button class="btn btn-primary w-100"><i class="bi bi-send"></i> Publish Announcement</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><i class="bi bi-list me-2"></i>Existing Announcements</div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($announcements as $a): ?>
                        <div class="announcement-item" style="border-bottom: 1px solid var(--border); padding: 14px 18px;">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 style="margin-bottom: 4px;">
                                        <?php if ($a['is_pinned']): ?><i class="bi bi-pin-angle-fill text-warning"></i><?php endif; ?>
                                        <?= htmlspecialchars($a['title']) ?>
                                        <span class="badge bg-<?= $a['type'] === 'danger' ? 'danger' : ($a['type'] === 'success' ? 'success' : ($a['type'] === 'warning' ? 'warning' : 'info')) ?>"><?= ucfirst($a['type']) ?></span>
                                    </h6>
                                    <p class="text-muted-sm mb-1"><?= htmlspecialchars(substr($a['content'], 0, 80)) ?><?= strlen($a['content']) > 80 ? '...' : '' ?></p>
                                    <small class="text-muted">By <?= htmlspecialchars($a['author_name'] ?? 'System') ?> · <?= date('M d, Y H:i', strtotime($a['created_at'])) ?></small>
                                </div>
                                <form method="POST" action="<?= BASE_URL ?>/content/announcements/<?= $a['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this announcement?')">
                                    <?= $csrf ?>
                                    <button class="btn btn-sm btn-light btn-icon-sm text-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($announcements)): ?>
                        <div class="text-center text-muted py-4"><i class="bi bi-inbox display-6 d-block mb-2 opacity-25"></i>No announcements yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ YOUTUBE TAB ============ -->
    <div class="tab-pane fade" id="youtube-tab">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Add YouTube Video</div>
                    <div class="card-body">
                        <form method="POST" action="<?= BASE_URL ?>/content/youtube/store">
                            <?= $csrf ?>
                            <div class="mb-3">
                                <label class="form-label required">Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label required">YouTube URL</label>
                                <input type="url" name="url" class="form-control" required placeholder="https://www.youtube.com/watch?v=...">
                                <small class="text-muted">Video ID is auto-extracted from URL</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Category</label>
                                    <input type="text" name="category" class="form-control" value="General" placeholder="Training, Tutorial, etc.">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Sort Order</label>
                                    <input type="number" name="sort_order" class="form-control" value="0">
                                </div>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="featYt">
                                <label class="form-check-label" for="featYt">Featured video (shown first)</label>
                            </div>
                            <button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add Video</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><i class="bi bi-list me-2"></i>Existing Videos</div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($youtubeLinks as $y): ?>
                        <div class="d-flex align-items-center gap-3 p-3" style="border-bottom: 1px solid var(--border);">
                            <?php if ($y['thumbnail']): ?>
                            <img src="<?= htmlspecialchars($y['thumbnail']) ?>" alt="" style="width: 80px; height: 45px; object-fit: cover; border-radius: 6px;">
                            <?php else: ?>
                            <div style="width:80px;height:45px;background:#FF0000;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;"><i class="bi bi-youtube"></i></div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <div class="fw-600 text-sm"><?= htmlspecialchars($y['title']) ?> <?= $y['is_featured'] ? '<span class="badge bg-warning"><i class="bi bi-star-fill"></i></span>' : '' ?></div>
                                <small class="text-muted"><?= htmlspecialchars($y['category']) ?> · <?= htmlspecialchars($y['video_id'] ?: '-') ?></small>
                            </div>
                            <form method="POST" action="<?= BASE_URL ?>/content/youtube/<?= $y['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Remove this video?')">
                                <?= $csrf ?>
                                <button class="btn btn-sm btn-light btn-icon-sm text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($youtubeLinks)): ?>
                        <div class="text-center text-muted py-4"><i class="bi bi-youtube display-6 d-block mb-2 opacity-25"></i>No videos added yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ EVENTS TAB ============ -->
    <div class="tab-pane fade" id="events-tab">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><i class="bi bi-plus-circle me-2"></i>Add Calendar Event</div>
                    <div class="card-body">
                        <form method="POST" action="<?= BASE_URL ?>/content/events/store">
                            <?= $csrf ?>
                            <div class="mb-3">
                                <label class="form-label required">Event Title</label>
                                <input type="text" name="title" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2"></textarea>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label required">Event Date</label>
                                    <input type="date" name="event_date" class="form-control" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">End Date (optional)</label>
                                    <input type="date" name="end_date" class="form-control">
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Start Time</label>
                                    <input type="time" name="start_time" class="form-control">
                                </div>
                                <div class="col-6">
                                    <label class="form-label">End Time</label>
                                    <input type="time" name="end_time" class="form-control">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Location</label>
                                <input type="text" name="location" class="form-control" placeholder="Conference Room A, Online, etc.">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label">Type</label>
                                    <select name="type" class="form-select">
                                        <option value="event">Event</option>
                                        <option value="meeting">Meeting</option>
                                        <option value="holiday">Holiday</option>
                                        <option value="training">Training</option>
                                        <option value="deadline">Deadline</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="form-label">Color</label>
                                    <input type="color" name="color" class="form-control form-control-color" value="#6366F1">
                                </div>
                            </div>
                            <button class="btn btn-primary w-100"><i class="bi bi-plus-lg"></i> Add Event</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><i class="bi bi-list me-2"></i>Upcoming Events</div>
                    <div class="card-body p-0" style="max-height: 600px; overflow-y: auto;">
                        <?php foreach ($events as $e): ?>
                        <div class="d-flex align-items-center gap-3 p-3" style="border-bottom: 1px solid var(--border);">
                            <div style="width:42px;height:42px;border-radius:10px;background:<?= htmlspecialchars($e['color']) ?>;color:#fff;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-shrink:0;">
                                <div style="font-size:14px;font-weight:800;line-height:1;"><?= date('d', strtotime($e['event_date'])) ?></div>
                                <div style="font-size:9px;text-transform:uppercase;"><?= date('M', strtotime($e['event_date'])) ?></div>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-600 text-sm"><?= htmlspecialchars($e['title']) ?> <span class="badge bg-light text-dark"><?= ucfirst($e['type']) ?></span></div>
                                <small class="text-muted">
                                    <?= date('D, M d, Y', strtotime($e['event_date'])) ?>
                                    <?= $e['start_time'] ? ' · ' . date('g:i A', strtotime($e['start_time'])) : '' ?>
                                    <?= $e['location'] ? ' · ' . htmlspecialchars($e['location']) : '' ?>
                                </small>
                            </div>
                            <form method="POST" action="<?= BASE_URL ?>/content/events/<?= $e['id'] ?>/delete" class="d-inline" onsubmit="return confirm('Delete this event?')">
                                <?= $csrf ?>
                                <button class="btn btn-sm btn-light btn-icon-sm text-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                        <?php endforeach; ?>
                        <?php if (empty($events)): ?>
                        <div class="text-center text-muted py-4"><i class="bi bi-calendar-x display-6 d-block mb-2 opacity-25"></i>No events yet.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ============ WEATHER TAB ============ -->
    <div class="tab-pane fade" id="weather-tab">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><i class="bi bi-cloud-sun-fill me-2"></i>Configure Weather</div>
                    <div class="card-body">
                        <form method="POST" action="<?= BASE_URL ?>/content/weather/update">
                            <?= $csrf ?>
                            <div class="mb-3">
                                <label class="form-label required">City Name</label>
                                <input type="text" name="location" class="form-control" required placeholder="e.g. New York, Kigali, London" value="<?= htmlspecialchars($weather['location'] ?? '') ?>">
                                <small class="text-muted">Enter a city name. Weather data is fetched from Open-Meteo (free, no API key needed).</small>
                            </div>
                            <button class="btn btn-primary w-100"><i class="bi bi-arrow-repeat"></i> Fetch &amp; Update Weather</button>
                        </form>
                        <div class="alert alert-info mt-3 mb-0 small">
                            <i class="bi bi-info-circle"></i> Weather is fetched live from Open-Meteo API and cached in the database. Update it periodically to keep the home page widget current.
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header"><i class="bi bi-eye me-2"></i>Current Weather Display</div>
                    <div class="card-body">
                        <?php if ($weather): ?>
                        <div class="text-center py-3">
                            <i class="bi bi-<?= htmlspecialchars($weather['icon']) ?>" style="font-size: 64px; color: var(--primary);"></i>
                            <div style="font-size: 48px; font-weight: 800; color: var(--text);"><?= number_format($weather['temperature'], 0) ?>°C</div>
                            <div class="text-muted"><?= htmlspecialchars($weather['description']) ?></div>
                            <div class="text-muted-sm"><?= htmlspecialchars($weather['city_name']) ?>, <?= htmlspecialchars($weather['country'] ?: '') ?></div>
                            <hr>
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="text-muted-sm">Feels Like</div>
                                    <div class="fw-600"><?= number_format($weather['feels_like'], 0) ?>°C</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted-sm">Humidity</div>
                                    <div class="fw-600"><?= $weather['humidity'] ?>%</div>
                                </div>
                                <div class="col-4">
                                    <div class="text-muted-sm">Wind</div>
                                    <div class="fw-600"><?= number_format($weather['wind_speed'], 1) ?> km/h</div>
                                </div>
                            </div>
                            <hr>
                            <small class="text-muted">Last updated: <?= date('M d, Y H:i:s', strtotime($weather['fetched_at'])) ?></small>
                        </div>
                        <?php else: ?>
                        <div class="text-center text-muted py-5">
                            <i class="bi bi-cloud-slash display-4 d-block mb-2 opacity-25"></i>
                            <p>No weather data yet. Configure a city on the left to fetch weather.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Info Edit Link -->
<div class="card mt-4">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-1"><i class="bi bi-gear-fill text-primary me-2"></i>System Information</h6>
                <p class="text-muted-sm mb-0">Edit system name, company info, and other settings</p>
            </div>
            <a href="<?= BASE_URL ?>/settings" class="btn btn-light"><i class="bi bi-pencil"></i> Edit Settings</a>
        </div>
    </div>
</div>

<style>
.announcement-item:last-child { border-bottom: none !important; }
</style>
