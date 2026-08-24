<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Leave Report</h4>
    <form method="GET" class="d-flex gap-2">
        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= $startDate ?>" style="width:160px">
        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= $endDate ?>" style="width:160px">
        <button class="btn btn-sm btn-light">Filter</button>
    </form>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Employee</th><th>Type</th><th>Start</th><th>End</th><th>Days</th><th>Reason</th><th>Status</th><th>Approved By</th></tr></thead>
                <tbody>
                    <?php foreach ($leaves as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['first_name'] . ' ' . $l['last_name']) ?> <small class="text-muted">(<?= $l['employee_code'] ?>)</small></td>
                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($l['leave_type']) ?></span></td>
                        <td><?= date('M d, Y', strtotime($l['start_date'])) ?></td>
                        <td><?= date('M d, Y', strtotime($l['end_date'])) ?></td>
                        <td><?= $l['total_days'] ?></td>
                        <td class="text-sm"><?= htmlspecialchars(substr($l['reason'], 0, 50)) ?><?= strlen($l['reason']) > 50 ? '...' : '' ?></td>
                        <td><span class="badge bg-<?= $l['status'] === 'approved' ? 'success' : ($l['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($l['status']) ?></span></td>
                        <td class="text-sm"><?= htmlspecialchars($l['approved_by_name'] ?? '-') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($leaves)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No leave records</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
