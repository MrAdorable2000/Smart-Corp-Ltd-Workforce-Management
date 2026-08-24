<?php
/**
 * Registration Status Page — token-based, public access
 */
$statusConfig = [
    'pending'            => ['icon'=>'bi-clock-history',       'color'=>'#F59E0B','label'=>'Pending Review',         'bg'=>'#FFFBEB'],
    'under_review'       => ['icon'=>'bi-eye',                 'color'=>'#3B82F6','label'=>'Under Review',           'bg'=>'#EFF6FF'],
    'approved'           => ['icon'=>'bi-check-circle-fill',   'color'=>'#16A34A','label'=>'Approved — Active!',     'bg'=>'#F0FDF4'],
    'rejected'           => ['icon'=>'bi-x-circle-fill',       'color'=>'#EF4444','label'=>'Rejected',               'bg'=>'#FEF2F2'],
    'changes_requested'  => ['icon'=>'bi-pencil-square',       'color'=>'#8B5CF6','label'=>'Changes Requested',      'bg'=>'#F5F3FF'],
];
$status = $request['status'];
$cfg    = $statusConfig[$status] ?? $statusConfig['pending'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Registration Status — <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
body{font-family:'Inter',sans-serif;background:#F0FDF4;color:#111827;min-height:100vh;}
.topbar{background:#fff;border-bottom:1px solid #D1FAE5;padding:14px 24px;display:flex;align-items:center;justify-content:space-between;margin-bottom:32px;}
.brand{display:flex;align-items:center;gap:8px;color:#16A34A;text-decoration:none;font-weight:800;font-size:17px;}
.brand i{font-size:22px;}
.main{max-width:700px;margin:0 auto;padding:0 16px 48px;}
.status-hero{border-radius:20px;padding:32px;text-align:center;margin-bottom:24px;}
.status-icon{font-size:56px;margin-bottom:12px;}
.status-label{font-size:22px;font-weight:800;margin-bottom:6px;}
.card{border-radius:16px;border:1px solid #D1FAE5;margin-bottom:16px;}
.card-header-custom{background:#F0FDF4;border-bottom:1px solid #D1FAE5;padding:14px 20px;border-radius:16px 16px 0 0;font-size:13px;font-weight:700;color:#16A34A;text-transform:uppercase;letter-spacing:.5px;}
.info-row{display:flex;padding:10px 20px;border-bottom:1px solid #F3F4F6;font-size:13px;}
.info-row:last-child{border-bottom:none;}
.info-label{color:#6B7280;min-width:160px;flex-shrink:0;font-weight:500;}
.info-value{font-weight:600;}
.timeline{border-left:2px solid #D1FAE5;padding-left:20px;margin:0 20px 16px;}
.tl-item{position:relative;margin-bottom:14px;}
.tl-item::before{content:'';position:absolute;left:-26px;top:5px;width:10px;height:10px;border-radius:50%;background:#16A34A;border:2px solid #fff;box-shadow:0 0 0 2px #D1FAE5;}
.tl-action{font-size:13px;font-weight:700;text-transform:capitalize;}
.tl-notes{font-size:12px;color:#6B7280;margin-top:2px;}
.tl-time{font-size:11px;color:#9CA3AF;margin-top:2px;}
.btn-green{background:linear-gradient(135deg,#16A34A,#15803D);color:#fff;border:none;border-radius:10px;padding:10px 24px;font-weight:700;font-size:14px;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:6px;}
.btn-green:hover{opacity:.9;color:#fff;}
.alert-box{border-radius:12px;padding:16px 20px;font-size:13px;margin-bottom:16px;}
</style>
</head>
<body>
<div class="topbar">
  <a href="<?= BASE_URL ?>/" class="brand"><i class="bi bi-shield-check-fill"></i><?= APP_NAME ?></a>
  <a href="<?= BASE_URL ?>/login" class="btn btn-sm btn-success">Sign In</a>
</div>

<div class="main">
  <!-- Status Hero -->
  <div class="status-hero" style="background:<?= $cfg['bg'] ?>;border:2px solid <?= $cfg['color'] ?>20;">
    <div class="status-icon" style="color:<?= $cfg['color'] ?>">
      <i class="bi <?= $cfg['icon'] ?>"></i>
    </div>
    <div class="status-label" style="color:<?= $cfg['color'] ?>"><?= $cfg['label'] ?></div>
    <div style="font-size:14px;color:#6B7280;margin-top:4px;">
      Submitted <?= date('M d, Y', strtotime($request['submitted_at'])) ?>
    </div>
  </div>

  <!-- Status-specific messages -->
  <?php if ($status === 'approved'): ?>
  <div class="alert-box" style="background:#F0FDF4;border:1.5px solid #16A34A;">
    <div style="font-size:15px;font-weight:700;color:#16A34A;margin-bottom:8px;">
      <i class="bi bi-check-circle-fill me-2"></i>Your account is now active!
    </div>
    <p style="margin:0;color:#374151;font-size:13px;">
      Your Employee ID is <strong><?= htmlspecialchars($request['employee_code'] ?? 'Being assigned') ?></strong>.
      You can now log in to the system and start recording attendance.
    </p>
    <div class="mt-3">
      <a href="<?= BASE_URL ?>/login" class="btn-green">
        <i class="bi bi-box-arrow-in-right"></i>Login Now
      </a>
    </div>
  </div>

  <?php elseif ($status === 'rejected'): ?>
  <div class="alert-box" style="background:#FEF2F2;border:1.5px solid #EF4444;">
    <div style="font-size:14px;font-weight:700;color:#EF4444;margin-bottom:6px;">
      <i class="bi bi-x-circle-fill me-2"></i>Your registration was not approved
    </div>
    <?php if ($request['rejection_reason']): ?>
    <div style="font-size:13px;color:#374151;"><strong>Reason:</strong> <?= htmlspecialchars($request['rejection_reason']) ?></div>
    <?php endif; ?>
    <div class="mt-2" style="font-size:13px;color:#6B7280;">
      You may register again with corrected information.
    </div>
    <div class="mt-3"><a href="<?= BASE_URL ?>/register" class="btn-green">Register Again</a></div>
  </div>

  <?php elseif ($status === 'changes_requested'): ?>
  <div class="alert-box" style="background:#F5F3FF;border:1.5px solid #8B5CF6;">
    <div style="font-size:14px;font-weight:700;color:#8B5CF6;margin-bottom:6px;">
      <i class="bi bi-pencil-square me-2"></i>Changes Required
    </div>
    <?php if ($request['change_request_notes']): ?>
    <div style="font-size:13px;color:#374151;"><strong>HR Notes:</strong> <?= nl2br(htmlspecialchars($request['change_request_notes'])) ?></div>
    <?php endif; ?>
    <div class="mt-3">
      <button class="btn-green" data-bs-toggle="modal" data-bs-target="#resubmitModal">
        <i class="bi bi-arrow-clockwise"></i>Update & Resubmit
      </button>
    </div>
  </div>

  <?php else: ?>
  <div class="alert-box" style="background:#FFFBEB;border:1.5px solid #F59E0B;">
    <div style="font-size:14px;font-weight:600;color:#92400E;margin-bottom:6px;">
      <i class="bi bi-clock me-2"></i>Your registration is being reviewed
    </div>
    <div style="font-size:13px;color:#6B7280;">
      Typically processed within 1-2 business days. You'll receive a notification once a decision is made.
    </div>
  </div>
  <?php endif; ?>

  <!-- Personal Info -->
  <div class="card">
    <div class="card-header-custom"><i class="bi bi-person me-2"></i>Registration Details</div>
    <div class="info-row"><span class="info-label">Full Name</span><span class="info-value"><?= htmlspecialchars($request['first_name'].' '.$request['last_name']) ?></span></div>
    <div class="info-row"><span class="info-label">Email</span><span class="info-value"><?= htmlspecialchars($request['email']) ?></span></div>
    <?php if ($request['phone']): ?>
    <div class="info-row"><span class="info-label">Phone</span><span class="info-value"><?= htmlspecialchars($request['phone']) ?></span></div>
    <?php endif; ?>
    <div class="info-row"><span class="info-label">Position</span><span class="info-value"><?= htmlspecialchars($request['position'] ?? '—') ?></span></div>
    <?php if ($request['dept_name']): ?>
    <div class="info-row"><span class="info-label">Department</span><span class="info-value"><?= htmlspecialchars($request['dept_name']) ?></span></div>
    <?php endif; ?>
    <?php if ($request['branch_name']): ?>
    <div class="info-row"><span class="info-label">Branch</span><span class="info-value"><?= htmlspecialchars($request['branch_name']) ?></span></div>
    <?php endif; ?>
    <div class="info-row">
      <span class="info-label">Face Enrollment</span>
      <span class="info-value">
        <?php if ($request['face_descriptors']): ?>
          <span style="color:#16A34A;"><i class="bi bi-check-circle-fill me-1"></i>Enrolled (<?= htmlspecialchars($request['face_angles_captured'] ?? '') ?>)</span>
        <?php else: ?>
          <span style="color:#F59E0B;"><i class="bi bi-exclamation-circle me-1"></i>Not enrolled</span>
        <?php endif; ?>
      </span>
    </div>
    <div class="info-row">
      <span class="info-label">Submitted</span>
      <span class="info-value"><?= date('M d, Y H:i', strtotime($request['submitted_at'])) ?></span>
    </div>
    <?php if ($request['resubmit_count'] > 0): ?>
    <div class="info-row">
      <span class="info-label">Resubmissions</span>
      <span class="info-value"><?= $request['resubmit_count'] ?></span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Timeline -->
  <?php if (!empty($history)): ?>
  <div class="card">
    <div class="card-header-custom"><i class="bi bi-list-ul me-2"></i>Review History</div>
    <div class="pt-3 pb-1">
      <div class="timeline">
        <?php foreach ($history as $h): ?>
        <div class="tl-item">
          <div class="tl-action"><?= htmlspecialchars(str_replace('_',' ',$h['action'])) ?></div>
          <?php if ($h['notes']): ?>
          <div class="tl-notes"><?= htmlspecialchars($h['notes']) ?></div>
          <?php endif; ?>
          <div class="tl-time">
            <?= date('M d, Y H:i', strtotime($h['created_at'])) ?>
            <?= $h['reviewer_name'] ? ' · by ' . htmlspecialchars($h['reviewer_name']) : '' ?>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>

<?php if ($status === 'changes_requested'): ?>
<!-- Resubmit Modal -->
<div class="modal fade" id="resubmitModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content" style="border-radius:16px;">
      <div class="modal-header" style="border-bottom:1px solid #D1FAE5;">
        <h5 class="modal-title fw-700"><i class="bi bi-arrow-clockwise me-2 text-success"></i>Update Registration</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-warning text-sm mb-3">
          <strong>Required changes:</strong> <?= htmlspecialchars($request['change_request_notes'] ?? '') ?>
        </div>
        <form id="resubmitForm">
          <?= $csrf ?>
          <div class="mb-3">
            <label class="form-label fw-600">First Name</label>
            <input type="text" name="first_name" class="form-control" value="<?= htmlspecialchars($request['first_name']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Last Name</label>
            <input type="text" name="last_name" class="form-control" value="<?= htmlspecialchars($request['last_name']) ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Phone</label>
            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($request['phone'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Position</label>
            <input type="text" name="position" class="form-control" value="<?= htmlspecialchars($request['position'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label fw-600">Address</label>
            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($request['address'] ?? '') ?></textarea>
          </div>
        </form>
      </div>
      <div class="modal-footer" style="border-top:1px solid #D1FAE5;">
        <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button class="btn-green" id="resubmitBtn" onclick="resubmit()">
          <i class="bi bi-send me-1"></i>Resubmit
        </button>
      </div>
    </div>
  </div>
</div>
<script>
async function resubmit() {
  const btn = document.getElementById('resubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting…';
  const fd = new FormData(document.getElementById('resubmitForm'));
  try {
    const resp = await fetch('<?= BASE_URL ?>/registration/resubmit/<?= htmlspecialchars($request['token']) ?>', {method:'POST',body:fd});
    const data = await resp.json();
    if (data.success) {
      location.reload();
    } else {
      alert(data.message || 'Failed');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-send me-1"></i>Resubmit';
    }
  } catch(e) { alert('Error: '+e.message); btn.disabled=false; }
}
</script>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
