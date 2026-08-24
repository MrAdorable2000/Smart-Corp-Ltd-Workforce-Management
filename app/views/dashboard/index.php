<?php /** @var array $data */ ?>
<!-- AI Narrative Summary -->
<div class="ai-narrative fade-in">
    <div class="ai-narrative-emoji"><?= $ai['summary']['emoji'] ?></div>
    <div class="ai-narrative-content">
        <span class="ai-narrative-badge <?= $ai['summary']['status'] ?>">
            <i class="bi bi-cpu"></i> AI Status: <?= ucfirst($ai['summary']['status']) ?>
        </span>
        <h4>Good <?= date('A') === 'AM' ? 'morning' : (date('H') < 17 ? 'afternoon' : 'evening') ?>, <?= htmlspecialchars(Auth::name()) ?>!</h4>
        <p><?= $ai['summary']['message'] ?> <strong><?= $ai['summary']['trend_text'] ?></strong>. Average working hours today: <strong><?= $ai['summary']['avg_hours'] ?>h</strong>.</p>
    </div>
</div>

<!-- Smart Alerts -->
<?php if (!empty($ai['smart_alerts'])): ?>
<div class="row g-2 mb-3 fade-in">
    <?php foreach ($ai['smart_alerts'] as $alert): ?>
    <div class="col-md-4">
        <div class="ai-card border-<?= $alert['level'] === 'critical' ? 'danger' : ($alert['level'] === 'warning' ? 'warning' : 'info') ?>" style="border-left: 4px solid var(--<?= $alert['level'] === 'critical' ? 'danger' : ($alert['level'] === 'warning' ? 'warning' : 'info') ?>); padding: 12px 16px;">
            <div class="d-flex align-items-center gap-2">
                <i class="bi <?= $alert['icon'] ?> text-<?= $alert['level'] === 'critical' ? 'danger' : ($alert['level'] === 'warning' ? 'warning' : 'info') ?>" style="font-size: 18px;"></i>
                <div class="flex-grow-1">
                    <div class="fw-600" style="font-size: 13px; color: var(--text);"><?= htmlspecialchars($alert['title']) ?></div>
                    <div class="text-muted-sm"><?= htmlspecialchars($alert['message']) ?></div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Primary KPI Cards -->
<div class="row g-3 mb-3 fade-in">
    <div class="col-6 col-xl-3">
        <a href="<?= BASE_URL ?>/employees" class="text-decoration-none">
            <div class="stat-card stat-violet">
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= number_format($stats['total_employees']) ?></div>
                    <div class="stat-label">Total Employees</div>
                    <div class="stat-trend up"><i class="bi bi-arrow-up-short"></i> Active workforce</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a href="<?= BASE_URL ?>/attendance" class="text-decoration-none">
            <div class="stat-card stat-emerald">
                <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="present"><?= $stats['present_today'] ?></div>
                    <div class="stat-label">Present Today</div>
                    <div class="stat-trend up"><i class="bi bi-camera-video"></i> Face verified</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a href="<?= BASE_URL ?>/attendance?date=<?= date('Y-m-d') ?>" class="text-decoration-none">
            <div class="stat-card stat-amber">
                <div class="stat-icon"><i class="bi bi-clock"></i></div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="late"><?= $stats['late_today'] ?></div>
                    <div class="stat-label">Late Arrivals</div>
                    <div class="stat-trend"><i class="bi bi-exclamation-circle"></i> Need attention</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-3">
        <a href="<?= BASE_URL ?>/attendance" class="text-decoration-none">
            <div class="stat-card stat-rose">
                <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="absent"><?= $stats['absent_today'] ?></div>
                    <div class="stat-label">Absent Today</div>
                    <div class="stat-trend down"><i class="bi bi-arrow-down-short"></i> Follow up</div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- AI Insights Grid -->
<div class="row g-3 mb-3 fade-in">
    <!-- Tomorrow's Prediction -->
    <div class="col-md-6 col-xl-3">
        <div class="ai-card h-100">
            <div class="ai-card-header">
                <div class="ai-icon" style="background: linear-gradient(135deg, #6366F1, #4F46E5);">
                    <i class="bi bi-stars"></i>
                </div>
                AI Prediction
            </div>
            <div class="text-center my-2">
                <div class="prediction-ring" style="--pct: <?= $ai['predictions']['predicted_rate'] ?? 0 ?>;">
                    <div class="prediction-ring-content">
                        <div class="prediction-ring-value"><?= $ai['predictions']['predicted_rate'] ?? 0 ?>%</div>
                        <div class="prediction-ring-label">Predicted</div>
                    </div>
                </div>
            </div>
            <div class="text-center mt-2">
                <div class="fw-600" style="color: var(--text);">Tomorrow (<?= $ai['predictions']['tomorrow'] ?? 'N/A' ?>)</div>
                <div class="text-muted-sm">~<?= $ai['predictions']['expected_present'] ?? 0 ?> present, <?= $ai['predictions']['expected_late'] ?? 0 ?> late</div>
            </div>
            <div class="mt-2">
                <div class="d-flex justify-content-between text-xs">
                    <span class="text-muted">Confidence</span>
                    <span class="fw-600"><?= ucfirst($ai['predictions']['confidence'] ?? 'low') ?> (<?= $ai['predictions']['confidence_score'] ?? 0 ?>%)</span>
                </div>
                <div class="confidence-bar">
                    <div class="confidence-bar-fill" style="width: <?= $ai['predictions']['confidence_score'] ?? 0 ?>%;"></div>
                </div>
            </div>
            <div class="text-xs mt-2" style="color: var(--text-secondary);">
                <i class="bi bi-lightbulb text-warning"></i> <?= htmlspecialchars($ai['predictions']['advice'] ?? 'Insufficient data for prediction.') ?>
            </div>
        </div>
    </div>

    <!-- Productivity Score -->
    <div class="col-md-6 col-xl-3">
        <div class="ai-card h-100">
            <div class="ai-card-header">
                <div class="ai-icon" style="background: linear-gradient(135deg, #10B981, #059669);">
                    <i class="bi bi-graph-up-arrow"></i>
                </div>
                Productivity Score
            </div>
            <div class="productivity-score-display">
                <div class="productivity-score-value"><?= $ai['productivity_score']['score'] ?? 0 ?></div>
                <div class="productivity-score-grade">Grade <?= $ai['productivity_score']['grade'] ?? 'N/A' ?></div>
            </div>
            <div class="mt-2">
                <?php foreach (($ai['productivity_score']['factors'] ?? []) as $factor): ?>
                <div class="factor-bar">
                    <div class="factor-bar-label"><?= htmlspecialchars($factor['name']) ?></div>
                    <div class="factor-bar-track">
                        <div class="factor-bar-fill" style="width: <?= $factor['value'] ?>%;"></div>
                    </div>
                    <div class="factor-bar-value"><?= $factor['value'] ?>%</div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Trend Analysis -->
    <div class="col-md-6 col-xl-3">
        <div class="ai-card h-100">
            <div class="ai-card-header">
                <div class="ai-icon" style="background: linear-gradient(135deg, #F59E0B, #D97706);">
                    <i class="bi bi-trend-<?= $ai['trend_analysis']['direction'] === 'improving' ? 'up' : ($ai['trend_analysis']['direction'] === 'declining' ? 'down' : 'up') ?>"></i>
                </div>
                Trend Analysis
            </div>
            <div class="my-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <?php
                    $trendIcon = $ai['trend_analysis']['direction'] === 'improving' ? 'arrow-up-circle-fill' : ($ai['trend_analysis']['direction'] === 'declining' ? 'arrow-down-circle-fill' : 'dash-circle-fill');
                    $trendColor = $ai['trend_analysis']['direction'] === 'improving' ? 'var(--success)' : ($ai['trend_analysis']['direction'] === 'declining' ? 'var(--danger)' : 'var(--text-muted)');
                    ?>
                    <i class="bi bi-<?= $trendIcon ?>" style="font-size: 24px; color: <?= $trendColor ?>;"></i>
                    <div>
                        <div class="fw-600" style="color: var(--text);"><?= ucfirst($ai['trend_analysis']['direction']) ?></div>
                        <div class="text-muted-sm"><?= $ai['trend_analysis']['change_pct'] > 0 ? '+' : '' ?><?= $ai['trend_analysis']['change_pct'] ?>% change</div>
                    </div>
                </div>
                <div class="text-xs" style="color: var(--text-secondary); line-height: 1.5;">
                    <?= htmlspecialchars($ai['trend_analysis']['insight']) ?>
                </div>
            </div>
            <?php if (!empty($ai['trend_analysis']['best_day'])): ?>
            <div class="mt-2 pt-2" style="border-top: 1px solid var(--border);">
                <div class="d-flex justify-content-between text-xs mb-1">
                    <span class="text-muted"><i class="bi bi-trophy-fill text-warning"></i> Best day</span>
                    <span class="fw-600"><?= $ai['trend_analysis']['best_day']['day'] ?> (<?= $ai['trend_analysis']['best_day']['rate'] ?>%)</span>
                </div>
                <div class="d-flex justify-content-between text-xs">
                    <span class="text-muted"><i class="bi bi-battery-quarter text-danger"></i> Worst day</span>
                    <span class="fw-600"><?= $ai['trend_analysis']['worst_day']['day'] ?> (<?= $ai['trend_analysis']['worst_day']['rate'] ?>%)</span>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Performers -->
    <div class="col-md-6 col-xl-3">
        <div class="ai-card h-100">
            <div class="ai-card-header">
                <div class="ai-icon" style="background: linear-gradient(135deg, #EC4899, #DB2777);">
                    <i class="bi bi-trophy-fill"></i>
                </div>
                Top Performers
            </div>
            <?php if (!empty($ai['top_performers'])): ?>
                <?php foreach (array_slice($ai['top_performers'], 0, 3) as $i => $p): ?>
                <div class="d-flex align-items-center gap-2 py-2 <?= $i < 2 ? 'border-bottom' : '' ?>" style="border-color: var(--border) !important;">
                    <div class="position-relative">
                        <div class="employee-avatar" style="width: 36px; height: 36px;">
                            <?php if (!empty($p['photo']) && file_exists(UPLOAD_PATH . '/' . $p['photo'])): ?>
                                <img src="<?= UPLOAD_URL . '/' . $p['photo'] ?>" alt="">
                            <?php else: ?>
                                <?= strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div style="position: absolute; top: -4px; right: -4px; background: <?= $i === 0 ? 'linear-gradient(135deg,#F59E0B,#D97706)' : ($i === 1 ? 'linear-gradient(135deg,#94A3B8,#64748B)' : 'linear-gradient(135deg,#CD7F32,#A0522D)') ?>; color: #fff; width: 18px; height: 18px; border-radius: 50%; font-size: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                            <?= $i + 1 ?>
                        </div>
                    </div>
                    <div class="flex-grow-1 overflow-hidden">
                        <div class="fw-600 text-sm text-truncate" style="color: var(--text);"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></div>
                        <div class="text-muted-sm text-truncate"><?= htmlspecialchars($p['department'] ?? '') ?></div>
                    </div>
                    <div class="text-end">
                        <div class="fw-600 text-sm" style="color: var(--success);"><?= $p['attendance_rate'] ?>%</div>
                        <div class="text-muted-sm"><?= round($p['total_ot'], 1) ?>h OT</div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-center text-muted py-4">
                    <i class="bi bi-trophy display-6 d-block mb-2 opacity-25"></i>
                    <small>No data yet</small>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Secondary Stats -->
<div class="row g-3 mb-3 fade-in">
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-sky">
            <div class="stat-icon"><i class="bi bi-airplane"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['on_leave'] ?></div>
                <div class="stat-label">On Leave Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <a href="<?= BASE_URL ?>/attendance" class="text-decoration-none">
            <div class="stat-card stat-emerald">
                <div class="stat-icon"><i class="bi bi-box-arrow-in-right"></i></div>
                <div class="stat-content">
                    <div class="stat-value" data-stat="checked_in"><?= $stats['checked_in'] ?></div>
                    <div class="stat-label">Checked In</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-teal">
            <div class="stat-icon"><i class="bi bi-box-arrow-right"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['checked_out'] ?></div>
                <div class="stat-label">Checked Out</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-violet">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= round($stats['total_overtime'], 1) ?>h</div>
                <div class="stat-label">Overtime Today</div>
                <div class="stat-trend"><i class="bi bi-people"></i> <?= $stats['overtime_employees'] ?> employees</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-indigo">
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['attendance_rate'] ?>%</div>
                <div class="stat-label">Monthly Rate</div>
                <div class="stat-trend up"><i class="bi bi-arrow-up-short"></i> <?= $stats['attendance_rate'] >= 80 ? 'Excellent' : 'Needs work' ?></div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <a href="<?= BASE_URL ?>/leaves" class="text-decoration-none">
            <div class="stat-card stat-orange">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= $stats['pending_leaves'] ?></div>
                    <div class="stat-label">Pending Leaves</div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- AI Recommendations + Anomalies -->
<div class="row g-3 mb-3 fade-in">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h5><i class="bi bi-lightbulb-fill text-warning me-2"></i>AI Recommendations</h5>
                    <small class="text-muted">Smart suggestions based on your data</small>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($ai['recommendations'])): ?>
                    <?php foreach ($ai['recommendations'] as $rec): ?>
                    <div class="recommendation-item priority-<?= $rec['priority'] ?>">
                        <div class="recommendation-title">
                            <i class="bi <?= $rec['icon'] ?> text-<?= $rec['priority'] === 'high' ? 'danger' : ($rec['priority'] === 'medium' ? 'warning' : 'info') ?>"></i>
                            <?= htmlspecialchars($rec['title']) ?>
                            <span class="badge bg-<?= $rec['priority'] === 'high' ? 'danger' : ($rec['priority'] === 'medium' ? 'warning' : 'info') ?> ms-auto"><?= ucfirst($rec['priority']) ?></span>
                        </div>
                        <div class="recommendation-desc"><?= htmlspecialchars($rec['description']) ?></div>
                        <div class="recommendation-action mt-2">
                            <i class="bi bi-arrow-right-circle"></i> Impact: <?= htmlspecialchars($rec['impact']) ?>
                        </div>
                        <?php if (!empty($rec['action_url'])): ?>
                        <a href="<?= $rec['action_url'] ?>" class="btn btn-sm btn-primary mt-2">
                            <?= $rec['action_label'] ?> <i class="bi bi-arrow-right"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-check-circle-fill text-success"></i>
                        <h6>All clear!</h6>
                        <p>No recommendations at this time. Your system is running optimally.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h5><i class="bi bi-shield-exclamation text-danger me-2"></i>Anomaly Detection</h5>
                    <small class="text-muted">Unusual patterns detected by AI</small>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($ai['anomalies'])): ?>
                    <?php foreach ($ai['anomalies'] as $anomaly): ?>
                    <div class="anomaly-item <?= $anomaly['severity'] ?>">
                        <div class="anomaly-title">
                            <i class="bi <?= $anomaly['icon'] ?> text-<?= $anomaly['severity'] ?>"></i>
                            <?= htmlspecialchars($anomaly['title']) ?>
                        </div>
                        <div class="anomaly-desc"><?= htmlspecialchars($anomaly['description']) ?></div>
                        <div class="anomaly-action"><i class="bi bi-arrow-right-circle"></i> <?= htmlspecialchars($anomaly['action']) ?></div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bi bi-shield-check text-success"></i>
                        <h6>No anomalies detected</h6>
                        <p>All patterns look normal. AI monitoring is active.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-3 fade-in">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h5><i class="bi bi-bar-chart-line me-2 text-primary"></i>Attendance Trend</h5>
                    <small class="text-muted">Last 7 days breakdown by status</small>
                </div>
                <div class="chart-legend">
                    <span><i class="bi bi-circle-fill text-success"></i> Present</span>
                    <span><i class="bi bi-circle-fill text-warning"></i> Late</span>
                    <span><i class="bi bi-circle-fill text-danger"></i> Absent</span>
                    <span><i class="bi bi-circle-fill text-info"></i> Leave</span>
                </div>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="120"></canvas>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h5><i class="bi bi-diagram-2 me-2 text-primary"></i>Department Distribution</h5>
                    <small class="text-muted">Employee count per department</small>
                </div>
            </div>
            <div class="card-body">
                <canvas id="deptChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Today's Log + Side panels -->
<div class="row g-3 fade-in">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h5><i class="bi bi-list-check me-2 text-primary"></i>Today's Attendance Log <span class="badge bg-success ms-2" style="font-size:10px;font-weight:500;"><i class="bi bi-circle-fill me-1" style="font-size:7px;"></i>LIVE</span></h5>
                    <small id="lastRefreshTime" class="text-muted"></small>
                    <small class="text-muted">Most recent check-ins</small>
                </div>
                <a href="<?= BASE_URL ?>/attendance" class="btn btn-sm btn-light">View All <i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover data-table mb-0">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Department</th>
                                <th>Check In</th>
                                <th>Check Out</th>
                                <th>Method</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody id="liveActivityTbody">
                            <?php foreach ($recentAttendance as $a): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="employee-avatar">
                                            <?php if (!empty($a['photo']) && file_exists(UPLOAD_PATH . '/' . $a['photo'])): ?>
                                                <img src="<?= UPLOAD_URL . '/' . $a['photo'] ?>" alt="" loading="lazy">
                                            <?php else: ?>
                                                <?= strtoupper(substr($a['first_name'], 0, 1) . substr($a['last_name'], 0, 1)) ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fw-600"><?= htmlspecialchars($a['first_name'] . ' ' . $a['last_name']) ?></div>
                                            <div class="text-muted-sm"><?= htmlspecialchars($a['employee_code']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="text-sm"><?= htmlspecialchars($a['department'] ?? '-') ?></td>
                                <td>
                                    <?php if ($a['check_in']): ?>
                                        <span class="fw-600"><?= date('H:i', strtotime($a['check_in'])) ?></span>
                                    <?php else: ?> <span class="text-muted">—</span> <?php endif; ?>
                                </td>
                                <td><?= $a['check_out'] ? '<span class="fw-600">'.date('H:i', strtotime($a['check_out'])).'</span>' : '<span class="text-muted">—</span>' ?></td>
                                <td>
                                    <?php if ($a['check_in_method'] === 'face'): ?>
                                        <span class="method-badge face"><i class="bi bi-camera-video-fill"></i> Face</span>
                                    <?php elseif ($a['check_in_method'] === 'gps'): ?>
                                        <span class="method-badge gps"><i class="bi bi-geo-alt-fill"></i> GPS</span>
                                    <?php elseif ($a['check_in_method'] === 'manual'): ?>
                                        <span class="method-badge manual"><i class="bi bi-pencil-fill"></i> Manual</span>
                                    <?php else: ?> <span class="text-muted">—</span> <?php endif; ?>
                                </td>
                                <td><span class="badge bg-<?= $a['status'] ?>"><?= ucfirst(str_replace('_', ' ', $a['status'])) ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($recentAttendance)): ?>
                            <tr><td colspan="6" class="empty-state">
                                <i class="bi bi-calendar-x"></i>
                                <h6>No attendance records for today yet.</h6>
                            </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-xl-4">
        <!-- At Risk Employees -->
        <div class="card mb-3">
            <div class="card-header">
                <div>
                    <h5><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>At-Risk Employees</h5>
                    <small class="text-muted">AI-flagged for follow-up</small>
                </div>
            </div>
            <div class="card-body">
                <?php if (!empty($ai['at_risk_employees'])): ?>
                    <?php foreach (array_slice($ai['at_risk_employees'], 0, 4) as $r): ?>
                    <div class="d-flex align-items-center gap-2 py-2 border-bottom" style="border-color: var(--border) !important;">
                        <div class="employee-avatar" style="width: 36px; height: 36px;">
                            <?php if (!empty($r['photo']) && file_exists(UPLOAD_PATH . '/' . $r['photo'])): ?>
                                <img src="<?= UPLOAD_URL . '/' . $r['photo'] ?>" alt="">
                            <?php else: ?>
                                <?= strtoupper(substr($r['first_name'], 0, 1) . substr($r['last_name'], 0, 1)) ?>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="fw-600 text-sm text-truncate" style="color: var(--text);"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></div>
                            <div class="text-muted-sm text-truncate"><?= htmlspecialchars($r['department'] ?? '') ?></div>
                        </div>
                        <div class="text-end">
                            <div class="text-xs text-danger"><?= $r['absence_rate'] ?>% absent</div>
                            <div class="text-xs text-warning"><?= $r['late_rate'] ?>% late</div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-check-circle-fill text-success display-6 d-block mb-2"></i>
                        <small>No at-risk employees detected</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Check-in Methods -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h5><i class="bi bi-camera-video me-2 text-primary"></i>Check-In Methods</h5>
                    <small class="text-muted">Today's breakdown</small>
                </div>
            </div>
            <div class="card-body">
                <?php if (empty($methodStats)): ?>
                    <p class="text-muted text-center mb-0 py-3"><i class="bi bi-inbox display-6 d-block mb-2 opacity-50"></i>No data yet</p>
                <?php else: foreach ($methodStats as $m):
                    $methodLabels = ['face' => 'Face Recognition', 'manual' => 'Manual', 'gps' => 'GPS', 'fingerprint' => 'Fingerprint', 'card' => 'ID Card'];
                    $methodIcons = ['face' => 'bi-camera-video-fill', 'manual' => 'bi-pencil-fill', 'gps' => 'bi-geo-alt-fill', 'fingerprint' => 'bi-fingerprint', 'card' => 'bi-credit-card-fill'];
                    $methodColors = ['face' => '#8B5CF6', 'manual' => '#6B7280', 'gps' => '#10B981', 'fingerprint' => '#F59E0B', 'card' => '#3B82F6'];
                ?>
                <div class="d-flex align-items-center gap-2 py-2">
                    <div style="width: 36px; height: 36px; border-radius: 10px; background: <?= $methodColors[$m['check_in_method']] ?? '#6B7280' ?>20; color: <?= $methodColors[$m['check_in_method']] ?? '#6B7280' ?>; display: flex; align-items: center; justify-content: center; font-size: 16px;">
                        <i class="bi <?= $methodIcons[$m['check_in_method']] ?? 'bi-question-circle' ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-600 text-sm" style="color: var(--text);"><?= $methodLabels[$m['check_in_method']] ?? ucfirst($m['check_in_method']) ?></div>
                        <div class="progress" style="height: 5px;">
                            <div class="progress-bar" style="width: <?= min(100, $m['count'] * 20) ?>%; background: <?= $methodColors[$m['check_in_method']] ?? '#6B7280' ?>;"></div>
                        </div>
                    </div>
                    <div class="fw-700" style="font-size: 18px; color: var(--text);"><?= $m['count'] ?></div>
                </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ===== LIVE DATABASE ACTIVITY PANEL ===== -->
<div class="row g-3 mb-3 fade-in">
    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <div>
                    <h5><i class="bi bi-database-check text-success me-2"></i>Live Database Activity
                        <span class="badge bg-success ms-1"><span class="pulse-dot"></span> REAL-TIME</span>
                    </h5>
                    <small class="text-muted">Every change you make is immediately persisted to MySQL</small>
                </div>
                <button onclick="location.reload()" class="btn btn-sm btn-light">
                    <i class="bi bi-arrow-clockwise"></i> Refresh
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 360px; overflow-y: auto;">
                    <table class="table table-sm mb-0">
                        <thead style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th>Time</th>
                                <th>Action</th>
                                <th>Module</th>
                                <th>Description</th>
                                <th>IP</th>
                                <th>Source</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($dbChanges as $change):
                                $actionIcon = [
                                    'create' => 'bi-plus-circle-fill text-success',
                                    'update' => 'bi-pencil-fill text-primary',
                                    'delete' => 'bi-trash-fill text-danger',
                                    'approve' => 'bi-check-circle-fill text-success',
                                    'reject' => 'bi-x-circle-fill text-danger',
                                    'login'  => 'bi-box-arrow-in-right text-info',
                                    'logout' => 'bi-box-arrow-right text-warning',
                                    'check_in'  => 'bi-door-open text-success',
                                    'check_out' => 'bi-door-closed text-primary',
                                    'process'   => 'bi-gear text-info',
                                    'face_enroll' => 'bi-camera-video text-violet',
                                ];
                                $icon = $actionIcon[$change['action'] ?? ''] ?? 'bi-circle-fill text-muted';
                            ?>
                            <tr>
                                <td class="text-muted-sm" style="white-space: nowrap;">
                                    <i class="bi bi-clock"></i>
                                    <?= date('H:i:s', strtotime($change['created_at'])) ?>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        <i class="bi <?= $icon ?>"></i>
                                        <?= htmlspecialchars(ucfirst($change['action'] ?? 'activity')) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($change['module'])): ?>
                                    <span class="badge bg-info"><?= htmlspecialchars($change['module']) ?></span>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-sm"><?= htmlspecialchars($change['description']) ?></td>
                                <td class="text-muted-sm"><?= htmlspecialchars($change['ip_address'] ?: '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $change['source'] === 'audit' ? 'primary' : 'secondary' ?>">
                                        <?= ucfirst($change['source']) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($dbChanges)): ?>
                            <tr><td colspan="6" class="empty-state">
                                <i class="bi bi-database"></i>
                                <h6>No recent activity</h6>
                                <p>Try approving a leave request or marking attendance — you'll see it here instantly!</p>
                            </td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- DB Change Stats -->
    <div class="col-xl-4">
        <div class="card mb-3">
            <div class="card-header">
                <div>
                    <h5><i class="bi bi-activity text-primary me-2"></i>Today's Changes</h5>
                    <small class="text-muted">Database modifications in last 24h</small>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-2 text-center">
                    <div class="col-6 mb-2">
                        <div class="db-stat-tile" style="background: rgba(16,185,129,.12);">
                            <i class="bi bi-plus-circle-fill text-success" style="font-size: 22px;"></i>
                            <div class="db-stat-value"><?= $dbStats['inserts'] ?></div>
                            <div class="db-stat-label">Inserts</div>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="db-stat-tile" style="background: rgba(99,102,241,.12);">
                            <i class="bi bi-pencil-fill text-primary" style="font-size: 22px;"></i>
                            <div class="db-stat-value"><?= $dbStats['updates'] ?></div>
                            <div class="db-stat-label">Updates</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="db-stat-tile" style="background: rgba(239,68,68,.12);">
                            <i class="bi bi-trash-fill text-danger" style="font-size: 22px;"></i>
                            <div class="db-stat-value"><?= $dbStats['deletes'] ?></div>
                            <div class="db-stat-label">Deletes</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="db-stat-tile" style="background: rgba(59,130,247,.12);">
                            <i class="bi bi-box-arrow-in-right text-info" style="font-size: 22px;"></i>
                            <div class="db-stat-value"><?= $dbStats['logins'] ?></div>
                            <div class="db-stat-label">Logins</div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info mt-3 mb-0" style="font-size: 12px;">
                    <i class="bi bi-info-circle"></i> <strong><?= $dbStats['total'] ?></strong> total DB operations today.
                    Every action (approve, reject, edit, delete) is recorded in <code>audit_logs</code> immediately.
                </div>
            </div>
        </div>

        <!-- Table record counts -->
        <div class="card">
            <div class="card-header">
                <div>
                    <h5><i class="bi bi-table text-success me-2"></i>Live Table Counts</h5>
                    <small class="text-muted">Verify your data is stored</small>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr><th>Table</th><th class="text-end">Records</th><th class="text-end">Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tableCounts as $table => $count):
                                $icons = [
                                    'users' => 'bi-person-badge',
                                    'employees' => 'bi-people',
                                    'departments' => 'bi-diagram-3',
                                    'attendance' => 'bi-calendar-check',
                                    'leave_requests' => 'bi-airplane',
                                    'payroll' => 'bi-wallet2',
                                    'notifications' => 'bi-bell',
                                    'audit_logs' => 'bi-shield-check',
                                    'employee_faces' => 'bi-camera-video',
                                    'shifts' => 'bi-clock-history',
                                    'holidays' => 'bi-calendar-event',
                                    'branches' => 'bi-building',
                                    'activity_logs' => 'bi-activity',
                                ];
                                $icon = $icons[$table] ?? 'bi-table';
                            ?>
                            <tr>
                                <td>
                                    <i class="bi <?= $icon ?> text-primary me-2"></i>
                                    <code><?= htmlspecialchars($table) ?></code>
                                </td>
                                <td class="text-end fw-600"><?= number_format($count) ?></td>
                                <td class="text-end">
                                    <?php if ($count > 0): ?>
                                    <span class="badge bg-success">Has data</span>
                                    <?php else: ?>
                                    <span class="badge bg-warning">Empty</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pulse-dot {
    display: inline-block;
    width: 8px;
    height: 8px;
    background: #fff;
    border-radius: 50%;
    margin-right: 4px;
    animation: pulse-blink 1.5s infinite;
}
@keyframes pulse-blink {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.3; transform: scale(1.3); }
}
.db-stat-tile {
    border-radius: 12px;
    padding: 14px 8px;
    transition: transform .2s;
}
.db-stat-tile:hover { transform: translateY(-2px); }
.db-stat-value {
    font-size: 24px;
    font-weight: 700;
    color: var(--text);
    line-height: 1;
    margin-top: 4px;
}
.db-stat-label {
    font-size: 11px;
    color: var(--text-muted);
    text-transform: uppercase;
    letter-spacing: .5px;
    margin-top: 2px;
}
.text-violet { color: #8B5CF6 !important; }
</style>

<?php
// Dashboard charts script — rendered AFTER jQuery + Chart.js are loaded by layout
$trendJson = json_encode($trend);
$deptJson = json_encode($deptStats);
$genderJson = json_encode($genderStats);
$scripts = "
    var palette = ['#6366F1','#10B981','#F59E0B','#EF4444','#3B82F6','#8B5CF6','#EC4899','#14B8A6','#F97316','#06B6D4'];
    var darkMode = document.documentElement.getAttribute('data-theme') === 'dark';
    var gridColor = darkMode ? 'rgba(255,255,255,.05)' : '#F3F4F6';
    var textColor = darkMode ? '#CBD5E1' : '#6B7280';

    Chart.defaults.color = textColor;
    Chart.defaults.borderColor = gridColor;

    // Attendance trend chart
    var trendData = {$trendJson};
    if (trendData.length && document.getElementById('trendChart')) {
        new Chart(document.getElementById('trendChart'), {
            type: 'bar',
            data: {
                labels: trendData.map(function(d) {
                    var dt = new Date(d.attendance_date);
                    return dt.toLocaleDateString('en', {month:'short', day:'numeric'});
                }),
                datasets: [
                    { label: 'Present', data: trendData.map(function(d){return d.present;}), backgroundColor: '#10B981', borderRadius: 6, barPercentage: 0.7 },
                    { label: 'Late', data: trendData.map(function(d){return d.late;}), backgroundColor: '#F59E0B', borderRadius: 6, barPercentage: 0.7 },
                    { label: 'Absent', data: trendData.map(function(d){return d.absent;}), backgroundColor: '#EF4444', borderRadius: 6, barPercentage: 0.7 },
                    { label: 'Leave', data: trendData.map(function(d){return d.on_leave;}), backgroundColor: '#3B82F6', borderRadius: 6, barPercentage: 0.7 }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: 'rgba(15,23,42,.95)', padding: 12, cornerRadius: 8 }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 11 } } },
                    y: { beginAtZero: true, ticks: { stepSize: 1, font: { size: 11 } }, grid: { color: gridColor } }
                }
            }
        });
    }

    // Department chart
    var deptData = {$deptJson};
    if (deptData.length && document.getElementById('deptChart')) {
        new Chart(document.getElementById('deptChart'), {
            type: 'doughnut',
            data: {
                labels: deptData.map(function(d){return d.name;}),
                datasets: [{
                    data: deptData.map(function(d){return d.employee_count;}),
                    backgroundColor: palette,
                    borderWidth: 2,
                    borderColor: darkMode ? '#1E293B' : '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } },
                    tooltip: { backgroundColor: 'rgba(15,23,42,.95)', padding: 10, cornerRadius: 8 }
                }
            }
        });
    }

    // Gender chart
    var genderData = {$genderJson};
    if (genderData.length && document.getElementById('genderChart')) {
        var genderColors = { male: '#3B82F6', female: '#EC4899', other: '#8B5CF6' };
        new Chart(document.getElementById('genderChart'), {
            type: 'pie',
            data: {
                labels: genderData.map(function(g){return g.gender ? g.gender.charAt(0).toUpperCase() + g.gender.slice(1) : 'Unknown';}),
                datasets: [{
                    data: genderData.map(function(g){return g.count;}),
                    backgroundColor: genderData.map(function(g){return genderColors[g.gender] || '#6B7280';}),
                    borderWidth: 2,
                    borderColor: darkMode ? '#1E293B' : '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, padding: 8, font: { size: 10 } } },
                    tooltip: { backgroundColor: 'rgba(15,23,42,.95)', padding: 10, cornerRadius: 8 }
                }
            }
        });
    }

    // Re-render charts on theme change
    window.refreshChartsForTheme = function() {
        var isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        Chart.defaults.color = isDark ? '#CBD5E1' : '#6B7280';
        Chart.defaults.borderColor = isDark ? 'rgba(255,255,255,.05)' : '#F3F4F6';
        Object.values(Chart.instances).forEach(function(c){c.update();});
    };
";
?>
<?php /* ── Real-Time Dashboard Auto-Refresh (separate script block, outside $scripts string) ── */ ?>
<script>
(function() {
    var BASE_URL    = '<?= BASE_URL ?>';
    var INTERVAL_MS = <?= DASHBOARD_REFRESH_INTERVAL ?>;

    function refreshDashboardStats() {
        fetch(BASE_URL + '/api/dashboard/realtime')
            .then(function(r){ return r.json(); })
            .then(function(res) {
                if (!res.success) return;
                var d = res.data;
                document.querySelectorAll('[data-stat]').forEach(function(el) {
                    var key = el.getAttribute('data-stat');
                    if (d[key] !== undefined) {
                        var val = d[key];
                        if (key === 'attendance_pct') val = val + '%';
                        if (key === 'total_overtime') val = val + 'h';
                        el.textContent = val;
                    }
                });
                var ts = document.getElementById('lastRefreshTime');
                if (ts) ts.textContent = 'Updated ' + res.timestamp;
            })
            .catch(function(){});
    }

    function refreshActivityFeed() {
        fetch(BASE_URL + '/api/dashboard/activity')
            .then(function(r){ return r.json(); })
            .then(function(res) {
                if (!res.success || !res.data || !res.data.length) return;
                var tbody = document.getElementById('liveActivityTbody');
                if (!tbody) return;
                tbody.innerHTML = res.data.map(function(a) {
                    var initials = (a.first_name||'?').charAt(0).toUpperCase() + (a.last_name||'?').charAt(0).toUpperCase();
                    var ci = a.check_in  ? new Date(a.check_in).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:false}) : '\u2014';
                    var co = a.check_out ? new Date(a.check_out).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:false}) : '\u2014';
                    var method = '';
                    if      (a.check_in_method === 'face')   method = '<span class="method-badge face"><i class="bi bi-camera-video-fill"></i> Face</span>';
                    else if (a.check_in_method === 'gps')    method = '<span class="method-badge gps"><i class="bi bi-geo-alt-fill"></i> GPS</span>';
                    else if (a.check_in_method === 'qr')     method = '<span class="method-badge face"><i class="bi bi-qr-code"></i> QR</span>';
                    else if (a.check_in_method === 'manual') method = '<span class="method-badge manual"><i class="bi bi-pencil-fill"></i> Manual</span>';
                    var statusBg = {present:'success',late:'warning',absent:'danger',half_day:'secondary',leave:'info',remote:'primary'}[a.status] || 'secondary';
                    return '<tr>' +
                        '<td><div class="d-flex align-items-center gap-2">' +
                        '<div class="employee-avatar" style="width:32px;height:32px;font-size:12px;">' + initials + '</div>' +
                        '<div><div class="fw-600 text-sm">' + (a.first_name||'') + ' ' + (a.last_name||'') + '</div>' +
                        '<div class="text-muted" style="font-size:11px;">' + (a.employee_code||'') + '</div></div></div></td>' +
                        '<td class="text-sm">' + (a.department||'\u2014') + '</td>' +
                        '<td><span class="fw-600">' + ci + '</span></td>' +
                        '<td><span class="fw-600">' + co + '</span></td>' +
                        '<td>' + (method||'\u2014') + '</td>' +
                        '<td><span class="badge bg-' + statusBg + '">' + (a.status||'').replace('_',' ') + '</span></td>' +
                        '</tr>';
                }).join('');
            })
            .catch(function(){});
    }

    // Initial load + recurring interval
    refreshDashboardStats();
    refreshActivityFeed();
    setInterval(refreshDashboardStats, INTERVAL_MS);
    setInterval(refreshActivityFeed,   INTERVAL_MS);
})();
</script>
