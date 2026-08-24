<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Attendance Report</h4>
    <form method="POST" action="<?= BASE_URL ?>/reports/export" class="d-flex gap-2">
        <?= $csrf ?>
        <input type="hidden" name="type" value="attendance">
        <button name="format" value="csv" class="btn btn-sm btn-light"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button name="format" value="excel" class="btn btn-sm btn-light"><i class="bi bi-filetype-xls"></i> Excel</button>
        <button name="format" value="pdf" class="btn btn-sm btn-light"><i class="bi bi-filetype-pdf"></i> PDF</button>
    </form>
</div>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label">Start Date</label>
                <input type="date" name="start_date" class="form-control" value="<?= $startDate ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">End Date</label>
                <input type="date" name="end_date" class="form-control" value="<?= $endDate ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">Department</label>
                <select name="department_id" class="form-select">
                    <option value="">All</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $deptId == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Apply Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Present</th>
                        <th>Late</th>
                        <th>Absent</th>
                        <th>Leave</th>
                        <th>Half Day</th>
                        <th>Hours</th>
                        <th>OT</th>
                        <th>Late Min</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($report as $r): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></td>
                        <td><?= htmlspecialchars($r['employee_code']) ?></td>
                        <td><?= htmlspecialchars($r['department'] ?? '-') ?></td>
                        <td><span class="badge bg-success"><?= $r['present'] ?? 0 ?></span></td>
                        <td><span class="badge bg-warning"><?= $r['late'] ?? 0 ?></span></td>
                        <td><span class="badge bg-danger"><?= $r['absent'] ?? 0 ?></span></td>
                        <td><span class="badge bg-info"><?= $r['on_leave'] ?? 0 ?></span></td>
                        <td><span class="badge bg-secondary"><?= $r['half_day'] ?? 0 ?></span></td>
                        <td><?= $r['total_hours'] ?? 0 ?>h</td>
                        <td><?= $r['overtime'] ?? 0 ?>h</td>
                        <td><?= $r['total_late'] ?? 0 ?>m</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($report)): ?>
                    <tr><td colspan="11" class="text-center py-4 text-muted">No data</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
