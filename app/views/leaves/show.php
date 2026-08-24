<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Leave Request #<?= $leave['id'] ?></h4>
    <a href="<?= BASE_URL ?>/leaves" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Request Details</div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr><th style="width:200px">Leave Type</th><td><?= htmlspecialchars($leave['leave_type']) ?> (<?= htmlspecialchars($leave['leave_code']) ?>)</td></tr>
                    <tr><th>Start Date</th><td><?= date('l, F d, Y', strtotime($leave['start_date'])) ?></td></tr>
                    <tr><th>End Date</th><td><?= date('l, F d, Y', strtotime($leave['end_date'])) ?></td></tr>
                    <tr><th>Duration</th><td><strong><?= $leave['total_days'] ?> day<?= $leave['total_days'] != 1 ? 's' : '' ?></strong></td></tr>
                    <tr><th>Emergency Contact</th><td><?= htmlspecialchars($leave['emergency_contact'] ?: '-') ?></td></tr>
                    <tr><th>Reason</th><td><?= nl2br(htmlspecialchars($leave['reason'])) ?></td></tr>
                    <tr><th>Applied By</th><td><?= htmlspecialchars($leave['applied_by_name'] ?? '-') ?></td></tr>
                    <tr><th>Applied On</th><td><?= date('M d, Y H:i', strtotime($leave['created_at'])) ?></td></tr>
                    <?php if ($leave['approved_at']): ?>
                    <tr><th>Approved/Rejected</th><td><?= date('M d, Y H:i', strtotime($leave['approved_at'])) ?></td></tr>
                    <tr><th>Approval Notes</th><td><?= nl2br(htmlspecialchars($leave['approval_notes'] ?: '-')) ?></td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <?php if ($leave['status'] === 'pending' && Auth::can('manage_leaves')): ?>
        <div class="card">
            <div class="card-header"><i class="bi bi-check2-square me-2"></i>Take Action</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Notes</label>
                    <textarea id="approvalNotes" class="form-control" rows="2" placeholder="Add approval/rejection notes (optional)"></textarea>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="<?= BASE_URL ?>/leaves/<?= $leave['id'] ?>/approve" id="approveForm">
                        <?= $csrf ?>
                        <input type="hidden" name="notes" id="approveNotesInput">
                        <button type="submit" class="btn btn-success"><i class="bi bi-check-lg"></i> Approve</button>
                    </form>
                    <form method="POST" action="<?= BASE_URL ?>/leaves/<?= $leave['id'] ?>/reject" id="rejectForm">
                        <?= $csrf ?>
                        <input type="hidden" name="notes" id="rejectNotesInput">
                        <button type="submit" class="btn btn-danger"><i class="bi bi-x-lg"></i> Reject</button>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">Status</div>
            <div class="card-body text-center">
                <?php
                $statusClass = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'];
                $statusIcon = ['pending' => 'hourglass-split', 'approved' => 'check-circle', 'rejected' => 'x-circle', 'cancelled' => 'dash-circle'];
                ?>
                <i class="bi bi-<?= $statusIcon[$leave['status']] ?> display-3 text-<?= $statusClass[$leave['status']] ?>"></i>
                <h5 class="mt-2 mb-0 text-<?= $statusClass[$leave['status']] ?>"><?= ucfirst($leave['status']) ?></h5>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Employee</div>
            <div class="card-body text-center">
                <div class="employee-avatar mb-2" style="width:64px;height:64px;margin:0 auto;font-size:20px;">
                    <?php if (!empty($leave['photo']) && file_exists(UPLOAD_PATH . '/' . $leave['photo'])): ?>
                        <img src="<?= UPLOAD_URL . '/' . $leave['photo'] ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($leave['first_name'], 0, 1) . substr($leave['last_name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h6 class="mb-0"><?= htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']) ?></h6>
                <small class="text-muted"><?= htmlspecialchars($leave['employee_code']) ?></small>
                <hr>
                <div class="text-sm">
                    <div class="mb-1"><i class="bi bi-building me-2"></i><?= htmlspecialchars($leave['department'] ?? '-') ?></div>
                    <div class="mb-1"><i class="bi bi-envelope me-2"></i><?= htmlspecialchars($leave['email'] ?: '-') ?></div>
                    <div><i class="bi bi-telephone me-2"></i><?= htmlspecialchars($leave['phone'] ?: '-') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$('#approveForm, #rejectForm').on('submit', function(e) {
    const notes = $('#approvalNotes').val();
    $('#approveNotesInput').val(notes);
    $('#rejectNotesInput').val(notes);
});
</script>
