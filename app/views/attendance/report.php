<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Attendance Report</h4>
        <p class="text-muted mb-0"><?= date('M d, Y', strtotime($startDate)) ?> — <?= date('M d, Y', strtotime($endDate)) ?></p>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="<?= BASE_URL ?>/attendance/export" class="d-flex gap-1">
            <?= $csrf ?>
            <input type="hidden" name="start_date" value="<?= $startDate ?>">
            <input type="hidden" name="end_date"   value="<?= $endDate ?>">
            <input type="hidden" name="department_id" value="<?= $deptId ?>">
            <button name="format" value="csv"   class="btn btn-light btn-sm"><i class="bi bi-filetype-csv"></i> CSV</button>
            <button name="format" value="excel" class="btn btn-light btn-sm"><i class="bi bi-filetype-xls"></i> Excel</button>
            <button name="format" value="pdf"   class="btn btn-light btn-sm"><i class="bi bi-file-earmark-pdf"></i> HTML</button>
        </form>
    </div>
</div>

<!-- Summary KPIs -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-emerald">
            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= array_sum(array_column($report, 'present')) ?></div>
                <div class="stat-label">Total Present Days</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-amber">
            <div class="stat-icon"><i class="bi bi-clock"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= array_sum(array_column($report, 'late')) ?></div>
                <div class="stat-label">Late Days</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-rose">
            <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= array_sum(array_column($report, 'absent')) ?></div>
                <div class="stat-label">Absent Days</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-sky">
            <div class="stat-icon"><i class="bi bi-graph-up"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= round(array_sum(array_column($report, 'total_hours')), 1) ?>h</div>
                <div class="stat-label">Total Hours Worked</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
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
            <div class="col-md-3">
                <label class="form-label">Employee</label>
                <select name="employee_id" class="form-select">
                    <option value="">All Employees</option>
                    <?php foreach ($employees as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= ($empId ?? '') == $e['id'] ? 'selected' : '' ?>><?= htmlspecialchars($e['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Report Table -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table me-2"></i>Employee Attendance Summary (<?= count($report) ?> employees)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th class="text-center">Days</th>
                        <th class="text-center">Present</th>
                        <th class="text-center">Late</th>
                        <th class="text-center">Absent</th>
                        <th class="text-center">Half Day</th>
                        <th class="text-center">Leave</th>
                        <th class="text-center">Total Hrs</th>
                        <th class="text-center">Overtime</th>
                        <th class="text-center">Late Min</th>
                        <th class="text-center">Attendance%</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report as $r): ?>
                    <?php
                    $presentDays = ($r['present'] ?? 0) + ($r['late'] ?? 0) + ($r['half_day'] ?? 0);
                    $totalDays   = max(1, $r['total_days'] ?? 1);
                    $pct = round(($presentDays / $totalDays) * 100);
                    $pctClass = $pct >= 90 ? 'success' : ($pct >= 75 ? 'warning' : 'danger');
                    ?>
                    <tr>
                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($r['employee_code']) ?></span></td>
                        <td class="fw-600"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                        <td class="text-sm"><?= htmlspecialchars($r['department'] ?? '—') ?></td>
                        <td class="text-center"><?= $r['total_days'] ?? 0 ?></td>
                        <td class="text-center"><span class="badge bg-success"><?= $r['present'] ?? 0 ?></span></td>
                        <td class="text-center"><span class="badge bg-warning"><?= $r['late'] ?? 0 ?></span></td>
                        <td class="text-center"><span class="badge bg-danger"><?= $r['absent'] ?? 0 ?></span></td>
                        <td class="text-center"><span class="badge bg-secondary"><?= $r['half_day'] ?? 0 ?></span></td>
                        <td class="text-center"><span class="badge bg-info"><?= $r['on_leave'] ?? 0 ?></span></td>
                        <td class="text-center fw-600"><?= number_format($r['total_hours'] ?? 0, 1) ?>h</td>
                        <td class="text-center text-info"><?= number_format($r['overtime'] ?? 0, 1) ?>h</td>
                        <td class="text-center text-warning"><?= (int)($r['total_late_min'] ?? 0) ?>m</td>
                        <td class="text-center">
                            <span class="badge bg-<?= $pctClass ?>"><?= $pct ?>%</span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($report)): ?>
                    <tr>
                        <td colspan="13" class="text-center py-5 text-muted">
                            <i class="bi bi-calendar-x display-4 d-block mb-2 opacity-50"></i>
                            No attendance data for the selected period.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
