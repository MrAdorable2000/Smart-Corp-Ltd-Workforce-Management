<?php /** Admin Registration Approval Center */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-person-check me-2 text-success"></i>Registration Approval Center</h4>
        <p class="text-muted mb-0">Review and process employee self-registration requests</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/register" class="btn btn-outline-success btn-sm" target="_blank">
            <i class="bi bi-box-arrow-up-right me-1"></i>Registration Page
        </a>
    </div>
</div>

<!-- KPI cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2half">
        <a href="?status=pending" class="text-decoration-none">
            <div class="stat-card stat-amber <?= $status==='pending'?'ring-active':'' ?>">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= (int)($counts['pending']??0) ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-2half">
        <a href="?status=under_review" class="text-decoration-none">
            <div class="stat-card stat-sky">
                <div class="stat-icon"><i class="bi bi-eye"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= (int)($counts['under_review']??0) ?></div>
                    <div class="stat-label">Under Review</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-2half">
        <a href="?status=approved" class="text-decoration-none">
            <div class="stat-card stat-emerald">
                <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= (int)($counts['approved']??0) ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-2half">
        <a href="?status=rejected" class="text-decoration-none">
            <div class="stat-card stat-rose">
                <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= (int)($counts['rejected']??0) ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-6 col-xl-2half">
        <a href="?status=changes_requested" class="text-decoration-none">
            <div class="stat-card stat-violet">
                <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
                <div class="stat-content">
                    <div class="stat-value"><?= (int)($counts['changes_requested']??0) ?></div>
                    <div class="stat-label">Changes Requested</div>
                </div>
            </div>
        </a>
    </div>
</div>

<!-- Status filter tabs -->
<div class="card mb-0" style="border-radius:16px 16px 0 0;border-bottom:none;">
    <div class="card-body py-2 px-3">
        <div class="d-flex gap-1 flex-wrap">
            <?php foreach(['all'=>'All','pending'=>'Pending','under_review'=>'Under Review','approved'=>'Approved','rejected'=>'Rejected','changes_requested'=>'Changes Requested'] as $s=>$label): ?>
            <a href="?status=<?= $s ?>" class="btn btn-sm <?= $status===$s ? 'btn-success' : 'btn-light' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card" style="border-radius:0 0 16px 16px;">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>Position</th>
                        <th>Department</th>
                        <th>Branch</th>
                        <th>Face</th>
                        <th>Submitted</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <?php if ($r['profile_photo'] && file_exists(UPLOAD_PATH.'/'.$r['profile_photo'])): ?>
                                    <img src="<?= UPLOAD_URL.'/'.$r['profile_photo'] ?>" style="width:36px;height:36px;border-radius:50%;object-fit:cover;">
                                <?php else: ?>
                                    <div class="employee-avatar" style="width:36px;height:36px;font-size:13px;">
                                        <?= strtoupper(substr($r['first_name'],0,1).substr($r['last_name'],0,1)) ?>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <div class="fw-600"><?= htmlspecialchars($r['first_name'].' '.$r['last_name']) ?></div>
                                    <div class="text-muted text-xs"><?= htmlspecialchars($r['email']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm"><?= htmlspecialchars($r['position'] ?? '—') ?></td>
                        <td class="text-sm"><?= htmlspecialchars($r['dept_name'] ?? '—') ?></td>
                        <td class="text-sm"><?= htmlspecialchars($r['branch_name'] ?? '—') ?></td>
                        <td>
                            <?php if ($r['face_descriptors']): ?>
                                <span class="badge bg-success" title="Enrolled: <?= htmlspecialchars($r['face_angles_captured']??'') ?>">
                                    <i class="bi bi-camera-video-fill me-1"></i>Enrolled
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning text-dark">Not enrolled</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-sm text-muted">
                            <?= date('M d, Y', strtotime($r['submitted_at'])) ?>
                            <?php if ($r['resubmit_count'] > 0): ?>
                                <br><span class="badge bg-light text-muted" style="font-size:10px;">Resubmitted ×<?= $r['resubmit_count'] ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $badgeMap = [
                                'pending'           => 'bg-warning text-dark',
                                'under_review'      => 'bg-primary',
                                'approved'          => 'bg-success',
                                'rejected'          => 'bg-danger',
                                'changes_requested' => 'bg-purple',
                            ];
                            $bg = $badgeMap[$r['status']] ?? 'bg-secondary';
                            ?>
                            <span class="badge <?= $bg ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="<?= BASE_URL ?>/registration/admin/review/<?= $r['id'] ?>"
                                   class="btn btn-sm btn-primary" title="Review">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <?php if (in_array($r['status'],['pending','under_review','changes_requested'])): ?>
                                <button class="btn btn-sm btn-success" onclick="quickApprove(<?= $r['id'] ?>)" title="Quick Approve">
                                    <i class="bi bi-check2"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" onclick="quickReject(<?= $r['id'] ?>)" title="Reject">
                                    <i class="bi bi-x"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-4 d-block opacity-50 mb-2"></i>
                            No registration requests with status: <?= htmlspecialchars($status) ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Quick Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Reject Registration</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="rejectId">
                <label class="form-label fw-600">Reason for rejection <span class="text-danger">*</span></label>
                <textarea id="rejectReason" class="form-control" rows="3" placeholder="Explain why this registration is being rejected…"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger" id="confirmRejectBtn"><i class="bi bi-x-circle me-1"></i>Reject</button>
            </div>
        </div>
    </div>
</div>

<style>
.col-xl-2half { flex: 0 0 auto; width: 20%; }
@media(max-width:1200px){ .col-xl-2half { width: 33.333%; } }
@media(max-width:768px) { .col-xl-2half { width: 50%; } }
.ring-active { box-shadow: 0 0 0 3px rgba(22,163,74,.4) !important; }
.bg-purple { background: #8B5CF6 !important; }
</style>

<script>
const CSRF = '<?= Session::getInstance()->csrfToken() ?>';
const BASE = '<?= BASE_URL ?>';
const CSRF_KEY = '<?= CSRF_TOKEN_NAME ?>';

async function quickApprove(id) {
    if (!confirm('Approve this registration? Employee account will be created immediately.')) return;
    const fd = new FormData();
    fd.append(CSRF_KEY, CSRF);
    fd.append('notes', 'Approved via quick action');
    try {
        const resp = await fetch(BASE+'/registration/admin/approve/'+id, {method:'POST',body:fd});
        const data = await resp.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch(e) { showToast('Error: '+e.message,'error'); }
}

function quickReject(id) {
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectReason').value = '';
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

document.getElementById('confirmRejectBtn').addEventListener('click', async function() {
    const id     = document.getElementById('rejectId').value;
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) { alert('Please provide a rejection reason.'); return; }
    this.disabled = true;
    const fd = new FormData();
    fd.append(CSRF_KEY, CSRF);
    fd.append('reason', reason);
    try {
        const resp = await fetch(BASE+'/registration/admin/reject/'+id, {method:'POST',body:fd});
        const data = await resp.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else {
            showToast(data.message || 'Failed','error');
        }
    } catch(e) { showToast('Error','error'); }
    this.disabled = false;
});
</script>
