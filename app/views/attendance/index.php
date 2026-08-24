<?php /** @var array $data */ ?>
<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Attendance Dashboard</h4>
        <p class="text-muted mb-0">Monitor, manage, and track employee attendance</p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/attendance/kiosk" class="btn btn-outline-secondary" target="_blank">
            <i class="bi bi-display"></i> Kiosk Mode
        </a>
        <a href="<?= BASE_URL ?>/attendance/face-scan" class="btn btn-primary">
            <i class="bi bi-camera-video"></i> Face Check-In
        </a>
        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#manualModal">
            <i class="bi bi-plus-circle"></i> Manual Entry
        </button>
        <a href="<?= BASE_URL ?>/attendance/report" class="btn btn-light">
            <i class="bi bi-file-bar-graph"></i> Report
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-violet">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $totalEmployees ?></div>
                <div class="stat-label">Total Employees</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-emerald">
            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= ($summary['present'] ?? 0) + ($summary['late'] ?? 0) + ($summary['half_day'] ?? 0) ?></div>
                <div class="stat-label">Present Today</div>
                <div class="stat-trend up"><i class="bi bi-arrow-up-short"></i> <?= $attPct ?>% rate</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-amber">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $summary['late'] ?? 0 ?></div>
                <div class="stat-label">Late Arrivals</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-rose">
            <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $summary['absent'] ?? 0 ?></div>
                <div class="stat-label">Absent</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-sky">
            <div class="stat-icon"><i class="bi bi-box-arrow-in-right"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $checkedIn ?></div>
                <div class="stat-label">Checked In</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-teal">
            <div class="stat-icon"><i class="bi bi-clock-fill"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $summary['total_overtime'] ?? '0' ?>h</div>
                <div class="stat-label">Total Overtime</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts row -->
<div class="row g-3 mb-4">
    <!-- Weekly Trend -->
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-bar-chart me-2"></i>Weekly Attendance Trend</span>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-light active" onclick="showChart('weekly', this)">Weekly</button>
                    <button class="btn btn-light" onclick="showChart('monthly', this)">Monthly</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="trendChart" height="200"></canvas>
            </div>
        </div>
    </div>
    <!-- Department Stats -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-building me-2"></i>Department Today</div>
            <div class="card-body">
                <canvas id="deptChart" height="200"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Filters + Table -->
<div class="card mb-3">
    <div class="card-header">
        <i class="bi bi-funnel me-2"></i>Filter Records
    </div>
    <div class="card-body pb-0">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Date</label>
                <input type="date" name="date" class="form-control" value="<?= htmlspecialchars($date) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                    <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <?php foreach (['present','late','absent','half_day','leave','remote'] as $s): ?>
                    <option value="<?= $s ?>" <?= ($statusFilter ?? '') === $s ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
            <div class="col-md-2">
                <form method="POST" action="<?= BASE_URL ?>/attendance/export" class="d-flex gap-1">
                    <?= $csrf ?>
                    <input type="hidden" name="start_date" value="<?= $date ?>">
                    <input type="hidden" name="end_date"   value="<?= $date ?>">
                    <button name="format" value="csv" class="btn btn-light flex-fill" title="Export CSV">
                        <i class="bi bi-filetype-csv"></i>
                    </button>
                    <button name="format" value="excel" class="btn btn-light flex-fill" title="Export Excel">
                        <i class="bi bi-filetype-xls"></i>
                    </button>
                </form>
            </div>
        </form>
    </div>
</div>

<!-- Attendance Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i>Attendance for <?= date('M d, Y', strtotime($date)) ?> (<?= count($records) ?> records)</span>
        <small class="text-muted">
            Attendance: <strong class="text-success"><?= $attPct ?>%</strong>
        </small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover data-table mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Shift</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours</th>
                        <th>OT</th>
                        <th>Late</th>
                        <th>Method</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($records as $r): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar">
                                    <?php if (!empty($r['photo']) && file_exists(UPLOAD_PATH . '/' . $r['photo'])): ?>
                                        <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($r['photo']) ?>" alt="">
                                    <?php else: ?>
                                        <?= strtoupper(substr($r['first_name'],0,1) . substr($r['last_name'],0,1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div class="fw-600"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($r['employee_code']) ?></span></td>
                        <td class="text-sm"><?= htmlspecialchars($r['department_name'] ?? '-') ?></td>
                        <td class="text-sm"><?= htmlspecialchars($r['shift_name'] ?? '-') ?></td>
                        <td>
                            <?php if ($r['check_in']): ?>
                                <?= date('H:i:s', strtotime($r['check_in'])) ?>
                                <?php if ($r['verified_by_face']): ?>
                                    <i class="bi bi-camera-video-fill text-primary ms-1" title="Face verified"></i>
                                <?php endif; ?>
                                <?php if ($r['verified_by_gps']): ?>
                                    <i class="bi bi-geo-alt-fill text-success ms-1" title="GPS verified"></i>
                                <?php endif; ?>
                            <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                        </td>
                        <td><?= $r['check_out'] ? date('H:i:s', strtotime($r['check_out'])) : '<span class="text-muted">—</span>' ?></td>
                        <td><?= $r['working_hours'] ? '<strong>' . $r['working_hours'] . '</strong>h' : '—' ?></td>
                        <td>
                            <?php if (($r['overtime_hours'] ?? 0) > 0): ?>
                                <span class="text-info"><?= $r['overtime_hours'] ?>h</span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php if (($r['late_minutes'] ?? 0) > 0): ?>
                                <span class="text-warning"><?= $r['late_minutes'] ?>m</span>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $methodMap = ['face' => ['bi-camera-video','primary'], 'manual' => ['bi-pencil','secondary'], 'gps' => ['bi-geo-alt','success'], 'fingerprint' => ['bi-fingerprint','info'], 'card' => ['bi-credit-card','warning']];
                            $m = $r['check_in_method'] ?? null;
                            if ($m && isset($methodMap[$m])) {
                                [$icon, $color] = $methodMap[$m];
                                echo "<i class=\"bi {$icon} text-{$color}\" title=\"" . ucfirst($m) . "\"></i>";
                            } else { echo '—'; }
                            ?>
                        </td>
                        <td>
                            <?php
                            $sBg = ['present' => 'success', 'late' => 'warning', 'absent' => 'danger', 'leave' => 'info', 'half_day' => 'secondary', 'holiday' => 'light', 'remote' => 'primary'];
                            $bg = $sBg[$r['status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $bg ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-light" onclick="deleteAttendance(<?= $r['id'] ?>)" title="Delete">
                                <i class="bi bi-trash text-danger"></i>
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($records)): ?>
                    <tr>
                        <td colspan="12" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x display-4 d-block mb-2 opacity-50"></i>
                            No attendance records for this date.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Manual Entry Modal -->
<div class="modal fade" id="manualModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Manual Attendance Entry</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="manualForm">
                    <?= $csrf ?>
                    <div class="mb-3">
                        <label class="form-label">Employee</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">Select Employee</option>
                            <?php
                            $empList = Database::getInstance()->fetchAll("SELECT id, CONCAT(employee_code,' - ',first_name,' ',last_name) AS name FROM employees WHERE is_active=1 ORDER BY first_name");
                            foreach ($empList as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Date</label>
                        <input type="date" name="attendance_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Check In Time</label>
                            <input type="time" name="check_in" class="form-control" value="08:00">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Check Out Time</label>
                            <input type="time" name="check_out" class="form-control" value="17:00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select" required>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="absent">Absent</option>
                            <option value="half_day">Half Day</option>
                            <option value="leave">On Leave</option>
                            <option value="remote">Remote</option>
                            <option value="holiday">Holiday</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="saveManualBtn"><i class="bi bi-check2"></i> Save</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ─── Weekly trend data ──────────────────────────────────
const weeklyData = <?= json_encode($weekTrend) ?>;
const monthlyData = <?= json_encode($monthlyTrend) ?>;
const deptData = <?= json_encode($deptAttendance) ?>;

let trendChart;
const darkMode = document.documentElement.getAttribute('data-theme') === 'dark';
const gridColor = darkMode ? 'rgba(255,255,255,.08)' : 'rgba(0,0,0,.05)';
const textColor = darkMode ? '#94A3B8' : '#64748B';

function buildWeeklyChart(data) {
    return {
        labels: data.map(r => {
            const d = new Date(r.attendance_date);
            return d.toLocaleDateString('en-US', {weekday:'short', month:'short', day:'numeric'});
        }),
        datasets: [
            { label:'Present', data: data.map(r=>r.present||0), backgroundColor:'rgba(16,185,129,.7)', borderRadius:4 },
            { label:'Late',    data: data.map(r=>r.late||0),    backgroundColor:'rgba(245,158,11,.7)', borderRadius:4 },
            { label:'Absent',  data: data.map(r=>r.absent||0),  backgroundColor:'rgba(239,68,68,.5)',  borderRadius:4 },
        ]
    };
}

function buildMonthlyChart(data) {
    return {
        labels: data.map(r => 'Week ' + r.week_num),
        datasets: [
            { label:'Present', data: data.map(r=>r.present||0), backgroundColor:'rgba(16,185,129,.7)', borderRadius:4 },
            { label:'Absent',  data: data.map(r=>r.absent||0),  backgroundColor:'rgba(239,68,68,.5)',  borderRadius:4 },
        ]
    };
}

const chartOpts = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { labels: { color: textColor } } },
    scales: {
        x: { stacked: true, ticks: { color: textColor }, grid: { color: gridColor } },
        y: { stacked: true, ticks: { color: textColor }, grid: { color: gridColor }, beginAtZero: true }
    }
};

const trendCtx = document.getElementById('trendChart').getContext('2d');
trendChart = new Chart(trendCtx, { type:'bar', data: buildWeeklyChart(weeklyData), options: {...chartOpts, plugins: { legend: { labels: { color: textColor } } }} });

function showChart(type, btn) {
    document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    const newData = type === 'weekly' ? buildWeeklyChart(weeklyData) : buildMonthlyChart(monthlyData);
    trendChart.data = newData;
    trendChart.update();
}

// ─── Department Doughnut ───────────────────────────────
const deptCtx = document.getElementById('deptChart').getContext('2d');
const deptColors = ['#6366F1','#10B981','#F59E0B','#3B82F6','#EF4444','#8B5CF6','#EC4899','#14B8A6'];
new Chart(deptCtx, {
    type: 'doughnut',
    data: {
        labels: deptData.map(d => d.dept || 'Unknown'),
        datasets: [{
            data: deptData.map(d => d.present || 0),
            backgroundColor: deptColors,
            borderWidth: 2,
            borderColor: darkMode ? '#1E293B' : '#fff'
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: {
            legend: { position:'bottom', labels: { color: textColor, font:{size:11} } }
        }
    }
});

// ─── Manual Entry ─────────────────────────────────────
document.getElementById('saveManualBtn').addEventListener('click', async function() {
    const form = document.getElementById('manualForm');
    const formData = new FormData(form);
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Saving...';

    try {
        const resp = await fetch('<?= BASE_URL ?>/attendance/manual', {method:'POST', body:formData});
        const data = await resp.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('manualModal')).hide();
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(data.message || 'Failed to save', 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    }
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-check2"></i> Save';
});

// ─── Delete ───────────────────────────────────────────
async function deleteAttendance(id) {
    if (!confirm('Delete this attendance record?')) return;
    const fd = new FormData();
    fd.append('_csrf_token', document.querySelector('meta[name="csrf-token"]').content);
    const resp = await fetch('<?= BASE_URL ?>/attendance/' + id + '/delete', {method:'POST', body:fd});
    const data = await resp.json();
    if (data.success) { showToast('Record deleted', 'success'); setTimeout(() => location.reload(), 800); }
    else showToast(data.message || 'Delete failed', 'error');
}

function showToast(msg, type) {
    if (typeof window.showToast === 'function') { window.showToast(msg, type); return; }
    alert(msg);
}
</script>
