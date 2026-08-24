<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Apply for Leave</h4>
    <a href="<?= BASE_URL ?>/leaves" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/leaves/apply">
                    <?= $csrf ?>
                    <?php if ($employee): ?>
                    <input type="hidden" name="employee_id" value="<?= $employee['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Leave Type</label>
                            <select name="leave_type_id" class="form-select" required>
                                <option value="">-- Select --</option>
                                <?php foreach ($leaveTypes as $lt): ?>
                                    <option value="<?= $lt['id'] ?>" data-days="<?= $lt['default_days_per_year'] ?>" data-paid="<?= $lt['is_paid'] ?>">
                                        <?= htmlspecialchars($lt['name']) ?> (<?= $lt['default_days_per_year'] ?> days/yr, <?= $lt['is_paid'] ? 'Paid' : 'Unpaid' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">Start Date</label>
                            <input type="date" name="start_date" id="startDate" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label required">End Date</label>
                            <input type="date" name="end_date" id="endDate" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Total Days</label>
                            <input type="text" id="totalDays" class="form-control" readonly value="0">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Emergency Contact</label>
                            <input type="text" name="emergency_contact" class="form-control" placeholder="Name and phone">
                        </div>
                        <div class="col-12">
                            <label class="form-label required">Reason</label>
                            <textarea name="reason" class="form-control" rows="4" required placeholder="Provide a detailed reason for your leave request..."></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-send"></i> Submit Request</button>
                            <a href="<?= BASE_URL ?>/leaves" class="btn btn-light">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">Leave Types</div>
            <div class="card-body">
                <ul class="list-unstyled mb-0 small">
                    <?php foreach ($leaveTypes as $lt): ?>
                    <li class="mb-2 d-flex justify-content-between">
                        <span><i class="bi bi-<?= $lt['is_paid'] ? 'cash-coin text-success' : 'x-circle text-danger' ?>"></i> <?= htmlspecialchars($lt['name']) ?></span>
                        <span class="badge bg-light text-dark"><?= $lt['default_days_per_year'] ?>d</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Notes</div>
            <div class="card-body small text-muted">
                <ul class="ps-3">
                    <li>Leave requests require manager approval.</li>
                    <li>Submit at least 3 days in advance (except emergencies).</li>
                    <li>Sick leave &gt; 2 days requires medical certificate.</li>
                    <li>Unused annual leave may be carried forward.</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
$('#startDate, #endDate').on('change', function() {
    const s = $('#startDate').val();
    const e = $('#endDate').val();
    if (s && e) {
        const d1 = new Date(s); const d2 = new Date(e);
        const days = Math.floor((d2 - d1) / 86400000) + 1;
        $('#totalDays').val(days + ' day' + (days !== 1 ? 's' : ''));
    }
});
</script>
