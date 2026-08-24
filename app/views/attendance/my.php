<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">My Attendance</h4>
        <p class="text-muted mb-0">Your attendance history for <?= date('F Y', strtotime($month . '-01')) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/attendance/face-scan" class="btn btn-primary">
            <i class="bi bi-camera-video"></i> Face Check-In/Out
        </a>
        <form method="POST" action="<?= BASE_URL ?>/attendance/export" class="d-flex gap-1">
            <input type="hidden" name="_csrf_token" value="<?= Session::getInstance()->csrfToken() ?>">
            <input type="hidden" name="start_date" value="<?= date('Y-m-01', strtotime($month)) ?>">
            <input type="hidden" name="end_date"   value="<?= date('Y-m-t',  strtotime($month)) ?>">
            <input type="hidden" name="employee_id" value="<?= Auth::employeeId() ?>">
            <button name="format" value="csv" class="btn btn-light btn-sm" title="Export CSV">
                <i class="bi bi-download"></i> CSV
            </button>
        </form>
    </div>
</div>

<!-- Today's Status Banner -->
<?php if ($todayRecord): ?>
<div class="card mb-4 border-<?= $todayRecord['check_out'] ? 'secondary' : ($todayRecord['status'] === 'late' ? 'warning' : 'success') ?>">
    <div class="card-body">
        <h6 class="card-title mb-3"><i class="bi bi-calendar-day me-2"></i>Today — <?= date('l, F j, Y') ?></h6>
        <div class="row text-center g-3">
            <div class="col-md-3">
                <div class="text-muted-sm">Check In</div>
                <div class="h4 mb-0 text-success"><?= $todayRecord['check_in'] ? date('H:i:s', strtotime($todayRecord['check_in'])) : '—' ?></div>
                <?php if ($todayRecord['status']): ?>
                <span class="badge bg-<?= ['present'=>'success','late'=>'warning','absent'=>'danger'][$todayRecord['status']] ?? 'secondary' ?>">
                    <?= ucfirst($todayRecord['status']) ?>
                </span>
                <?php endif; ?>
            </div>
            <div class="col-md-3 border-start border-end">
                <div class="text-muted-sm">Check Out</div>
                <div class="h4 mb-0 text-primary"><?= $todayRecord['check_out'] ? date('H:i:s', strtotime($todayRecord['check_out'])) : '—' ?></div>
                <?php if (!$todayRecord['check_out'] && $todayRecord['check_in']): ?>
                <small class="text-muted">Currently working</small>
                <?php endif; ?>
            </div>
            <div class="col-md-3 border-end">
                <div class="text-muted-sm">Working Hours</div>
                <div class="h4 mb-0"><?= $todayRecord['working_hours'] ?? '—' ?>h</div>
                <?php if (($todayRecord['late_minutes'] ?? 0) > 0): ?>
                <small class="text-warning"><?= $todayRecord['late_minutes'] ?>m late</small>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <div class="text-muted-sm">Overtime</div>
                <div class="h4 mb-0 text-info"><?= ($todayRecord['overtime_hours'] ?? 0) > 0 ? $todayRecord['overtime_hours'] . 'h' : '—' ?></div>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
<div class="alert alert-warning mb-4">
    <i class="bi bi-exclamation-triangle me-2"></i>
    No attendance recorded today yet.
    <a href="<?= BASE_URL ?>/attendance/face-scan" class="alert-link">Check in now</a>
</div>
<?php endif; ?>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-emerald">
            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-content"><div class="stat-value"><?= ($summary['present'] ?? 0) + ($summary['late'] ?? 0) ?></div><div class="stat-label">Present</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-amber">
            <div class="stat-icon"><i class="bi bi-clock"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $summary['late'] ?? 0 ?></div><div class="stat-label">Late Days</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-sky">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $summary['total_hours'] ?? '0' ?>h</div><div class="stat-label">Total Hours</div></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-violet">
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-content"><div class="stat-value"><?= $summary['total_overtime'] ?? '0' ?>h</div><div class="stat-label">Overtime</div></div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <!-- Monthly chart -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>6-Month Trend</div>
            <div class="card-body"><canvas id="myTrendChart" height="200"></canvas></div>
        </div>
    </div>
    <!-- Attendance table -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar3 me-2"></i>Attendance History</span>
                <form method="GET" class="d-flex gap-1">
                    <input type="month" name="month" class="form-control form-control-sm" value="<?= $month ?>" style="width:160px;">
                    <button class="btn btn-sm btn-light">Go</button>
                </form>
            </div>
            <div class="card-body p-0" style="max-height:420px; overflow-y:auto;">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours</th>
                            <th>OT</th>
                            <th>Late</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($records as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-600"><?= date('d', strtotime($r['attendance_date'])) ?></div>
                                <small class="text-muted"><?= date('D', strtotime($r['attendance_date'])) ?></small>
                            </td>
                            <td><?= $r['check_in'] ? date('H:i', strtotime($r['check_in'])) : '—' ?></td>
                            <td><?= $r['check_out'] ? date('H:i', strtotime($r['check_out'])) : '—' ?></td>
                            <td class="fw-600"><?= $r['working_hours'] ?: '—' ?></td>
                            <td><?= ($r['overtime_hours'] ?? 0) > 0 ? '<span class="text-info">' . $r['overtime_hours'] . 'h</span>' : '—' ?></td>
                            <td><?= ($r['late_minutes'] ?? 0) > 0 ? '<span class="text-warning">' . $r['late_minutes'] . 'm</span>' : '—' ?></td>
                            <td>
                                <?php
                                $sBg = ['present'=>'success','late'=>'warning','absent'=>'danger','leave'=>'info','half_day'=>'secondary','remote'=>'primary'];
                                $bg = $sBg[$r['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-<?= $bg ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($records)): ?>
                        <tr><td colspan="7" class="text-center text-muted py-4">No records for this month.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const monthStats = <?= json_encode($monthlyStats) ?>;
const dark = document.documentElement.getAttribute('data-theme') === 'dark';
const tc = dark ? '#94A3B8' : '#64748B';
const gc = dark ? 'rgba(255,255,255,.06)' : 'rgba(0,0,0,.04)';

new Chart(document.getElementById('myTrendChart'), {
    type: 'bar',
    data: {
        labels: monthStats.map(r => {
            const [y,m] = r.month.split('-');
            return new Date(y,m-1).toLocaleDateString('en-US',{month:'short',year:'2-digit'});
        }),
        datasets: [
            { label:'Present', data: monthStats.map(r=>r.present||0), backgroundColor:'rgba(16,185,129,.7)', borderRadius:4 },
            { label:'Late',    data: monthStats.map(r=>r.late||0),    backgroundColor:'rgba(245,158,11,.7)', borderRadius:4 },
        ]
    },
    options: {
        responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ labels:{ color:tc } } },
        scales:{
            x:{ stacked:true, ticks:{color:tc}, grid:{color:gc} },
            y:{ stacked:true, ticks:{color:tc}, grid:{color:gc}, beginAtZero:true }
        }
    }
});
</script>
