<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Employee Directory Report</h4>
    <form method="POST" action="<?= BASE_URL ?>/reports/export" class="d-flex gap-2">
        <?= $csrf ?>
        <input type="hidden" name="type" value="employees">
        <button name="format" value="csv" class="btn btn-sm btn-light"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button name="format" value="excel" class="btn btn-sm btn-light"><i class="bi bi-filetype-xls"></i> Excel</button>
        <button name="format" value="pdf" class="btn btn-sm btn-light"><i class="bi bi-filetype-pdf"></i> PDF</button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Department</th><th>Position</th><th>Email</th><th>Phone</th><th>Status</th><th>Salary</th><th>Present</th><th>Absent</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $e): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['employee_code']) ?></td>
                        <td><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></td>
                        <td><?= htmlspecialchars($e['department'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['position'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['email'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['phone'] ?? '-') ?></td>
                        <td><span class="badge bg-info"><?= ucfirst($e['employment_status']) ?></span></td>
                        <td>$<?= number_format($e['salary'], 2) ?></td>
                        <td><?= $e['present_days'] ?></td>
                        <td><?= $e['absent_days'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
