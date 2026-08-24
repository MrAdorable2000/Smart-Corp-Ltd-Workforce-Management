<?php /** Admin Registration Detail Review */ ?>
<?php
$faceData = $request['face_descriptors'] ? json_decode($request['face_descriptors'], true) : [];
$statusColors = ['pending'=>'warning','under_review'=>'primary','approved'=>'success','rejected'=>'danger','changes_requested'=>'purple'];
$statusBg = $statusColors[$request['status']] ?? 'secondary';
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="<?= BASE_URL ?>/registration/admin">Approval Center</a></li>
                <li class="breadcrumb-item active">Review Request #<?= $request['id'] ?></li>
            </ol>
        </nav>
        <h4 class="mb-0"><?= htmlspecialchars($request['first_name'].' '.$request['last_name']) ?></h4>
    </div>
    <span class="badge bg-<?= $statusBg ?>" style="font-size:13px;padding:8px 14px;">
        <?= ucfirst(str_replace('_',' ',$request['status'])) ?>
    </span>
</div>

<div class="row g-4">
    <!-- Left: Details -->
    <div class="col-lg-8">

        <!-- Personal Info -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person me-2"></i>Personal Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">Full Name</div>
                        <div class="fw-600"><?= htmlspecialchars($request['first_name'].' '.$request['last_name']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">National ID / Passport</div>
                        <div class="fw-600"><?= htmlspecialchars($request['national_id'] ?? $request['passport_number'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">Email</div>
                        <div class="fw-600"><?= htmlspecialchars($request['email']) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">Phone</div>
                        <div class="fw-600"><?= htmlspecialchars($request['phone'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted text-sm mb-1">Date of Birth</div>
                        <div class="fw-600"><?= $request['date_of_birth'] ? date('M d, Y', strtotime($request['date_of_birth'])) : '—' ?></div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-muted text-sm mb-1">Gender</div>
                        <div class="fw-600"><?= ucfirst($request['gender'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">Address</div>
                        <div class="fw-600"><?= nl2br(htmlspecialchars($request['address'] ?? '—')) ?></div>
                    </div>
                    <?php if ($request['emergency_contact_name']): ?>
                    <div class="col-12">
                        <div class="text-muted text-sm mb-1">Emergency Contact</div>
                        <div class="fw-600">
                            <?= htmlspecialchars($request['emergency_contact_name']) ?>
                            <?= $request['emergency_contact_rel'] ? '(' . htmlspecialchars($request['emergency_contact_rel']) . ')' : '' ?>
                            · <?= htmlspecialchars($request['emergency_contact_phone'] ?? '') ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Employment -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-briefcase me-2"></i>Employment Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">Position</div>
                        <div class="fw-600"><?= htmlspecialchars($request['position'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">Employee Code</div>
                        <div class="fw-600"><?= htmlspecialchars($request['employee_code'] ?? 'Auto-generate') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">Department</div>
                        <div class="fw-600"><?= htmlspecialchars($request['dept_name'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">Branch</div>
                        <div class="fw-600"><?= htmlspecialchars($request['branch_name'] ?? '—') ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">Employment Type</div>
                        <div class="fw-600"><?= ucfirst(str_replace('_',' ',$request['employment_type'] ?? '')) ?></div>
                    </div>
                    <div class="col-md-6">
                        <div class="text-muted text-sm mb-1">Submitted</div>
                        <div class="fw-600"><?= date('M d, Y H:i', strtotime($request['submitted_at'])) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Face Enrollment -->
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-camera-video me-2"></i>Face Enrollment</span>
                <?php if ($faceData): ?>
                    <span class="badge bg-success"><?= count($faceData) ?> angles captured</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark">Not enrolled</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (!empty($faceData)): ?>
                <div class="row g-2 mb-3">
                    <?php foreach ($faceData as $face): ?>
                    <div class="col-auto">
                        <?php if (!empty($face['image_path']) && file_exists(UPLOAD_PATH.'/'.$face['image_path'])): ?>
                            <div class="text-center">
                                <img src="<?= UPLOAD_URL.'/'.$face['image_path'] ?>"
                                     style="width:80px;height:80px;object-fit:cover;border-radius:10px;border:2px solid #D1FAE5;">
                                <div style="font-size:11px;color:#6B7280;margin-top:4px;"><?= ucfirst($face['label'] ?? '') ?></div>
                            </div>
                        <?php else: ?>
                            <div style="width:80px;height:80px;border-radius:10px;background:#F0FDF4;border:2px solid #D1FAE5;display:flex;align-items:center;justify-content:center;flex-direction:column;">
                                <i class="bi bi-person-bounding-box" style="font-size:24px;color:#16A34A;"></i>
                                <div style="font-size:10px;color:#6B7280;"><?= ucfirst($face['label'] ?? '') ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($request['face_quality_score']): ?>
                <div class="text-sm text-muted">
                    Quality score: <strong><?= number_format($request['face_quality_score'], 1) ?>%</strong>
                    <?= $request['face_quality_score'] >= 80 ? ' <span class="text-success">✓ Good</span>' : ' <span class="text-warning">⚠ Fair</span>' ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="text-muted text-sm">
                    <i class="bi bi-exclamation-circle me-1 text-warning"></i>
                    Face not enrolled. Employee can enroll at HR office after approval.
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Timeline -->
        <?php if (!empty($history)): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Review History</div>
            <div class="card-body">
                <div style="border-left:2px solid #D1FAE5;padding-left:20px;">
                    <?php foreach ($history as $h): ?>
                    <div style="position:relative;margin-bottom:16px;">
                        <div style="position:absolute;left:-26px;top:4px;width:10px;height:10px;border-radius:50%;background:#16A34A;border:2px solid #fff;box-shadow:0 0 0 2px #D1FAE5;"></div>
                        <div style="font-size:13px;font-weight:700;"><?= htmlspecialchars(ucfirst(str_replace('_',' ',$h['action']))) ?></div>
                        <?php if ($h['notes']): ?><div style="font-size:12px;color:#6B7280;"><?= htmlspecialchars($h['notes']) ?></div><?php endif; ?>
                        <div style="font-size:11px;color:#9CA3AF;">
                            <?= date('M d, Y H:i', strtotime($h['created_at'])) ?>
                            <?= $h['reviewer_name'] ? ' · ' . htmlspecialchars($h['reviewer_name']) : '' ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: Actions -->
    <div class="col-lg-4">

        <!-- Photo -->
        <div class="card mb-3 text-center">
            <div class="card-body py-4">
                <?php if ($request['profile_photo'] && file_exists(UPLOAD_PATH.'/'.$request['profile_photo'])): ?>
                    <img src="<?= UPLOAD_URL.'/'.$request['profile_photo'] ?>"
                         style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid #D1FAE5;">
                <?php else: ?>
                    <div style="width:100px;height:100px;border-radius:50%;background:#D1FAE5;color:#16A34A;font-size:36px;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                        <?= strtoupper(substr($request['first_name'],0,1).substr($request['last_name'],0,1)) ?>
                    </div>
                <?php endif; ?>
                <div class="fw-700 mt-2"><?= htmlspecialchars($request['first_name'].' '.$request['last_name']) ?></div>
                <div class="text-muted text-sm"><?= htmlspecialchars($request['email']) ?></div>
                <div class="mt-2">
                    <span class="badge bg-<?= $statusBg ?>"><?= ucfirst(str_replace('_',' ',$request['status'])) ?></span>
                </div>
            </div>
        </div>

        <?php if (in_array($request['status'], ['pending','under_review','changes_requested'])): ?>
        <!-- Approval Panel -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-gear me-2"></i>Approval Settings</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label text-sm fw-600">Employee Code</label>
                    <input type="text" id="empCodeInput" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($request['employee_code'] ?? '') ?>"
                           placeholder="Leave blank to auto-generate">
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm fw-600">Department</label>
                    <select id="deptInput" class="form-select form-select-sm">
                        <option value="">Keep: <?= htmlspecialchars($request['dept_name'] ?? 'Not set') ?></option>
                        <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $d['id'] == ($request['department_id']??'') ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm fw-600">Branch</label>
                    <select id="branchInput" class="form-select form-select-sm">
                        <option value="">Keep: <?= htmlspecialchars($request['branch_name'] ?? 'Not set') ?></option>
                        <?php foreach ($branches as $b): ?>
                        <option value="<?= $b['id'] ?>" <?= $b['id'] == ($request['branch_id']??'') ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['name']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm fw-600">Shift</label>
                    <select id="shiftInput" class="form-select form-select-sm">
                        <option value="">Default shift</option>
                        <?php foreach ($shifts as $s): ?>
                        <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= $s['start_time'] ?>-<?= $s['end_time'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm fw-600">Starting Salary (optional)</label>
                    <input type="number" id="salaryInput" class="form-control form-control-sm" placeholder="0.00" min="0" step="0.01">
                </div>
                <div class="mb-3">
                    <label class="form-label text-sm fw-600">Approval Notes</label>
                    <textarea id="approvalNotes" class="form-control form-control-sm" rows="2" placeholder="Optional notes…"></textarea>
                </div>

                <button class="btn btn-success w-100 mb-2 fw-700" onclick="approveRequest(<?= $request['id'] ?>)">
                    <i class="bi bi-check2-circle me-1"></i>Approve & Activate Employee
                </button>
                <button class="btn btn-outline-warning w-100 mb-2" onclick="showChangesModal()">
                    <i class="bi bi-pencil me-1"></i>Request Changes
                </button>
                <button class="btn btn-outline-danger w-100" onclick="showRejectModal()">
                    <i class="bi bi-x-circle me-1"></i>Reject
                </button>
            </div>
        </div>
        <?php elseif ($request['status'] === 'approved'): ?>
        <div class="card mb-3">
            <div class="card-body text-center">
                <i class="bi bi-check-circle-fill text-success" style="font-size:40px;"></i>
                <div class="fw-700 mt-2">Approved</div>
                <div class="text-muted text-sm mt-1">Employee ID: <?= htmlspecialchars($request['employee_code'] ?? '—') ?></div>
                <?php if ($request['employee_id']): ?>
                <a href="<?= BASE_URL ?>/employees/view/<?= $request['employee_id'] ?>" class="btn btn-sm btn-success mt-2">
                    <i class="bi bi-person me-1"></i>View Employee
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Meta info -->
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Submission Info</div>
            <div class="card-body">
                <div class="text-sm mb-2"><span class="text-muted">IP Address:</span> <strong><?= htmlspecialchars($request['ip_address'] ?? '—') ?></strong></div>
                <div class="text-sm mb-2"><span class="text-muted">Submitted:</span> <strong><?= date('M d, Y H:i', strtotime($request['submitted_at'])) ?></strong></div>
                <?php if ($request['resubmit_count'] > 0): ?>
                <div class="text-sm"><span class="text-muted">Resubmissions:</span> <strong><?= $request['resubmit_count'] ?></strong></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Request Changes Modal -->
<div class="modal fade" id="changesModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil me-2 text-warning"></i>Request Changes</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-600">What needs to be corrected? <span class="text-danger">*</span></label>
                <textarea id="changesNotes" class="form-control" rows="4"
                    placeholder="Describe what the applicant needs to fix or provide…"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-warning fw-600" id="confirmChangesBtn">
                    <i class="bi bi-send me-1"></i>Send Request
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="bi bi-x-circle me-2"></i>Reject Registration</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-600">Reason for rejection <span class="text-danger">*</span></label>
                <textarea id="rejectReason" class="form-control" rows="3"
                    placeholder="Explain why this registration cannot be approved…"></textarea>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-danger fw-600" id="confirmRejectBtn">
                    <i class="bi bi-x-circle me-1"></i>Confirm Rejection
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= Session::getInstance()->csrfToken() ?>';
const BASE = '<?= BASE_URL ?>';
const CSRF_KEY = '<?= CSRF_TOKEN_NAME ?>';
const REQUEST_ID = <?= (int)$request['id'] ?>;

async function approveRequest(id) {
    if (!confirm('Approve this registration and create employee account?')) return;
    const fd = new FormData();
    fd.append(CSRF_KEY,        CSRF);
    fd.append('employee_code', document.getElementById('empCodeInput').value);
    fd.append('department_id', document.getElementById('deptInput').value);
    fd.append('branch_id',     document.getElementById('branchInput').value);
    fd.append('shift_id',      document.getElementById('shiftInput').value);
    fd.append('salary',        document.getElementById('salaryInput').value);
    fd.append('notes',         document.getElementById('approvalNotes').value);

    try {
        const resp = await fetch(BASE+'/registration/admin/approve/'+id, {method:'POST',body:fd});
        const data = await resp.json();
        if (data.success) {
            showToast(data.message, 'success');
            setTimeout(() => window.location.href = BASE+'/registration/admin', 2000);
        } else {
            showToast(data.message || 'Approval failed', 'error');
        }
    } catch(e) { showToast('Error: '+e.message,'error'); }
}

function showChangesModal() {
    new bootstrap.Modal(document.getElementById('changesModal')).show();
}
function showRejectModal() {
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}

document.getElementById('confirmChangesBtn').addEventListener('click', async function() {
    const notes = document.getElementById('changesNotes').value.trim();
    if (!notes) { alert('Please describe what needs to be changed.'); return; }
    this.disabled = true;
    const fd = new FormData();
    fd.append(CSRF_KEY, CSRF);
    fd.append('notes', notes);
    try {
        const resp = await fetch(BASE+'/registration/admin/changes/'+REQUEST_ID, {method:'POST',body:fd});
        const data = await resp.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('changesModal')).hide();
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 1200);
        } else { showToast(data.message||'Failed','error'); }
    } catch(e) { showToast('Error','error'); }
    this.disabled = false;
});

document.getElementById('confirmRejectBtn').addEventListener('click', async function() {
    const reason = document.getElementById('rejectReason').value.trim();
    if (!reason) { alert('Please provide a rejection reason.'); return; }
    this.disabled = true;
    const fd = new FormData();
    fd.append(CSRF_KEY, CSRF);
    fd.append('reason', reason);
    try {
        const resp = await fetch(BASE+'/registration/admin/reject/'+REQUEST_ID, {method:'POST',body:fd});
        const data = await resp.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('rejectModal')).hide();
            showToast(data.message, 'success');
            setTimeout(() => window.location.href = BASE+'/registration/admin', 1500);
        } else { showToast(data.message||'Failed','error'); }
    } catch(e) { showToast('Error','error'); }
    this.disabled = false;
});
</script>
