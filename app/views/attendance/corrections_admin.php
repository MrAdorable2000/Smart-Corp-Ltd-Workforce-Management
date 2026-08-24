<?php /** @var array $data Admin corrections view */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-pencil-square me-2"></i>Attendance Corrections</h4>
        <p class="text-muted mb-0">Review and process employee correction requests</p>
    </div>
    <div class="btn-group">
        <?php foreach (['all','pending','approved','rejected'] as $s): ?>
        <a href="?status=<?= $s ?>" class="btn btn-sm btn-<?= $status === $s ? 'primary' : 'light' ?>">
            <?= ucfirst($s) ?>
        </a>
        <?php endforeach; ?>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-list-ul me-2"></i>Correction Requests (<?= count($corrections) ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Current</th>
                        <th>Requested</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Submitted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($corrections as $c): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar" style="width:32px;height:32px;font-size:13px;">
                                    <?= strtoupper(substr($c['first_name'],0,1).substr($c['last_name'],0,1)) ?>
                                </div>
                                <div>
                                    <div class="fw-600 text-sm"><?= htmlspecialchars($c['first_name'].' '.$c['last_name']) ?></div>
                                    <div class="text-muted text-xs"><?= htmlspecialchars($c['employee_code']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="fw-600"><?= date('M d, Y', strtotime($c['attendance_date'])) ?></td>
                        <td class="text-sm">
                            <div>In: <?= $c['current_check_in'] ? date('H:i', strtotime($c['current_check_in'])) : '—' ?></div>
                            <div>Out: <?= $c['current_check_out'] ? date('H:i', strtotime($c['current_check_out'])) : '—' ?></div>
                        </td>
                        <td class="text-sm text-primary fw-600">
                            <div>In: <?= $c['requested_check_in'] ? date('H:i', strtotime($c['requested_check_in'])) : '—' ?></div>
                            <div>Out: <?= $c['requested_check_out'] ? date('H:i', strtotime($c['requested_check_out'])) : '—' ?></div>
                        </td>
                        <td class="text-sm" style="max-width:180px;"><?= htmlspecialchars(substr($c['reason'],0,100)) ?><?= strlen($c['reason'])>100?'…':'' ?></td>
                        <td>
                            <?php $bg=['pending'=>'warning','approved'=>'success','rejected'=>'danger'][$c['status']]??'secondary'; ?>
                            <span class="badge bg-<?= $bg ?>"><?= ucfirst($c['status']) ?></span>
                        </td>
                        <td class="text-sm text-muted"><?= date('M d, H:i', strtotime($c['created_at'])) ?></td>
                        <td>
                            <?php if ($c['status'] === 'pending'): ?>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-success" onclick="reviewCorrection(<?= $c['id'] ?>,'approve')">
                                    <i class="bi bi-check2"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="reviewCorrection(<?= $c['id'] ?>,'reject')">
                                    <i class="bi bi-x"></i>
                                </button>
                            </div>
                            <?php else: ?>
                            <span class="text-muted text-sm"><?= htmlspecialchars($c['reviewer_name'] ?? '—') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($corrections)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted"><i class="bi bi-inbox display-4 d-block opacity-50 mb-2"></i>No correction requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reviewModalTitle">Review Request</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reviewId">
                <input type="hidden" id="reviewAction">
                <div class="mb-3">
                    <label class="form-label">Notes (optional)</label>
                    <textarea id="reviewNotes" class="form-control" rows="3" placeholder="Add a note for the employee..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="confirmReviewBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= Session::getInstance()->csrfToken() ?>';
const BASE = '<?= BASE_URL ?>';

function reviewCorrection(id, action) {
    document.getElementById('reviewId').value     = id;
    document.getElementById('reviewAction').value = action;
    document.getElementById('reviewModalTitle').textContent = action === 'approve' ? '✅ Approve Request' : '❌ Reject Request';
    document.getElementById('confirmReviewBtn').className   = 'btn btn-' + (action === 'approve' ? 'success' : 'danger');
    new bootstrap.Modal(document.getElementById('reviewModal')).show();
}

document.getElementById('confirmReviewBtn').addEventListener('click', async function() {
    const id     = document.getElementById('reviewId').value;
    const action = document.getElementById('reviewAction').value;
    const notes  = document.getElementById('reviewNotes').value;

    this.disabled = true;
    const fd = new FormData();
    fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF);
    fd.append('notes', notes);

    try {
        const resp = await fetch(`${BASE}/corrections/${id}/${action}`, { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('reviewModal')).hide();
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch(e) { showToast('Network error', 'error'); }
    this.disabled = false;
});
</script>
