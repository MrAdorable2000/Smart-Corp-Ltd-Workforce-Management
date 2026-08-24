<?php /** @var array $data Employee correction requests */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-pencil-square me-2"></i>Correction Requests</h4>
        <p class="text-muted mb-0">Request a correction to your attendance record</p>
    </div>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#correctionModal">
        <i class="bi bi-plus-circle me-2"></i>New Request
    </button>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <?php
    $pending  = count(array_filter($corrections, fn($c) => $c['status'] === 'pending'));
    $approved = count(array_filter($corrections, fn($c) => $c['status'] === 'approved'));
    $rejected = count(array_filter($corrections, fn($c) => $c['status'] === 'rejected'));
    ?>
    <div class="col-4"><div class="stat-card stat-amber"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div><div class="stat-content"><div class="stat-value"><?= $pending ?></div><div class="stat-label">Pending</div></div></div></div>
    <div class="col-4"><div class="stat-card stat-emerald"><div class="stat-icon"><i class="bi bi-check2-circle"></i></div><div class="stat-content"><div class="stat-value"><?= $approved ?></div><div class="stat-label">Approved</div></div></div></div>
    <div class="col-4"><div class="stat-card stat-rose"><div class="stat-icon"><i class="bi bi-x-circle"></i></div><div class="stat-content"><div class="stat-value"><?= $rejected ?></div><div class="stat-label">Rejected</div></div></div></div>
</div>

<!-- Requests table -->
<div class="card">
    <div class="card-header"><i class="bi bi-list-ul me-2"></i>My Requests (<?= count($corrections) ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table mb-0">
                <thead><tr><th>Date</th><th>Requested In</th><th>Requested Out</th><th>Reason</th><th>Status</th><th>Reviewer Note</th><th>Submitted</th></tr></thead>
                <tbody>
                    <?php foreach ($corrections as $c): ?>
                    <tr>
                        <td class="fw-600"><?= date('M d, Y', strtotime($c['attendance_date'])) ?></td>
                        <td><?= $c['requested_check_in'] ? date('H:i', strtotime($c['requested_check_in'])) : '—' ?></td>
                        <td><?= $c['requested_check_out'] ? date('H:i', strtotime($c['requested_check_out'])) : '—' ?></td>
                        <td class="text-sm" style="max-width:200px;"><?= htmlspecialchars(substr($c['reason'],0,80)) ?><?= strlen($c['reason'])>80?'…':'' ?></td>
                        <td>
                            <?php $bg=['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$c['status']]??'secondary'; ?>
                            <span class="badge bg-<?= $bg ?>"><?= ucfirst($c['status']) ?></span>
                        </td>
                        <td class="text-sm text-muted"><?= htmlspecialchars($c['review_notes'] ?? '—') ?></td>
                        <td class="text-sm text-muted"><?= date('M d, H:i', strtotime($c['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($corrections)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted"><i class="bi bi-inbox display-4 d-block opacity-50 mb-2"></i>No correction requests yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New Correction Modal -->
<div class="modal fade" id="correctionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Request Attendance Correction</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="correctionForm">
                    <?= $csrf ?>
                    <div class="mb-3">
                        <label class="form-label">Date to Correct <span class="text-danger">*</span></label>
                        <input type="date" name="attendance_date" class="form-control" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Correct Check-In Time</label>
                            <input type="time" name="requested_check_in" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Correct Check-Out Time</label>
                            <input type="time" name="requested_check_out" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Explain why you need this correction..."></textarea>
                    </div>
                    <div class="alert alert-info text-sm">
                        <i class="bi bi-info-circle me-1"></i>
                        A manager will review your request. You will be notified once it's processed.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="submitCorrectionBtn"><i class="bi bi-send me-1"></i>Submit Request</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('submitCorrectionBtn').addEventListener('click', async function() {
    const form = document.getElementById('correctionForm');
    const fd   = new FormData(form);
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting...';

    try {
        const resp = await fetch('<?= BASE_URL ?>/corrections/create', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('correctionModal')).hide();
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch(e) { showToast('Network error', 'error'); }

    this.disabled = false;
    this.innerHTML = '<i class="bi bi-send me-1"></i>Submit Request';
});
</script>
