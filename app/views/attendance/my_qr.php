<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-qr-code me-2"></i>My Attendance QR Code</h4>
        <p class="text-muted mb-0">Scan this QR code at any attendance station to check in or check out</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/attendance/mobile" class="btn btn-light">
            <i class="bi bi-phone"></i> Mobile Check-In
        </a>
        <a href="<?= BASE_URL ?>/attendance/my" class="btn btn-light">
            <i class="bi bi-calendar-check"></i> My Attendance
        </a>
    </div>
</div>

<div class="row g-4 justify-content-center">
    <!-- QR Card -->
    <div class="col-lg-5">
        <div class="card text-center">
            <div class="card-header">
                <i class="bi bi-qr-code me-2"></i>Personal Attendance QR
            </div>
            <div class="card-body py-4">
                <!-- Employee avatar -->
                <div class="mb-3">
                    <div class="employee-avatar mx-auto" style="width:64px;height:64px;font-size:24px;">
                        <?php if (!empty($employee['photo']) && file_exists(UPLOAD_PATH . '/' . $employee['photo'])): ?>
                            <img src="<?= UPLOAD_URL . '/' . htmlspecialchars($employee['photo']) ?>" alt="">
                        <?php else: ?>
                            <?= strtoupper(substr($employee['first_name'],0,1).substr($employee['last_name'],0,1)) ?>
                        <?php endif; ?>
                    </div>
                    <div class="fw-700 mt-2"><?= htmlspecialchars($employee['first_name'].' '.$employee['last_name']) ?></div>
                    <div class="text-muted text-sm"><?= htmlspecialchars($employee['employee_code'] ?? '') ?> · <?= htmlspecialchars($employee['dept'] ?? '') ?></div>
                </div>

                <!-- QR Image -->
                <div class="qr-wrap mx-auto mb-3" id="qrWrap" style="width:240px;height:240px;background:#fff;padding:12px;border-radius:12px;border:2px solid var(--border-color, #e2e8f0);">
                    <img id="qrImg" src="<?= htmlspecialchars($qrImgUrl) ?>"
                         alt="QR Code"
                         style="width:100%;height:100%;object-fit:contain;"
                         onerror="this.src='<?= ASSET_URL ?>/img/qr-placeholder.png'">
                </div>

                <!-- Token info -->
                <div class="text-muted text-sm mb-3">
                    <i class="bi bi-shield-check text-success me-1"></i>
                    Encrypted & Secure
                    <?php if ($qr['expires_at']): ?>
                        · Expires <?= date('M d, Y', strtotime($qr['expires_at'])) ?>
                    <?php else: ?>
                        · Permanent
                    <?php endif; ?>
                </div>

                <div class="d-flex gap-2 justify-content-center">
                    <button class="btn btn-primary" id="regenerateBtn">
                        <i class="bi bi-arrow-clockwise"></i> Regenerate
                    </button>
                    <a href="<?= htmlspecialchars($qrImgUrl) ?>" download="my-qr-<?= $employee['employee_code'] ?>.png" class="btn btn-light">
                        <i class="bi bi-download"></i> Download
                    </a>
                    <button class="btn btn-light" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print
                    </button>
                </div>

                <div class="alert alert-warning mt-3 text-start text-sm" role="alert">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <strong>Keep this QR private.</strong> Do not share it with others. Regenerate immediately if you think it was compromised.
                </div>
            </div>
        </div>

        <!-- Scan stats -->
        <div class="card mt-3">
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-4">
                        <div class="text-muted text-sm">Total Scans</div>
                        <div class="h4 mb-0"><?= number_format($qr['scan_count']) ?></div>
                    </div>
                    <div class="col-4 border-start border-end">
                        <div class="text-muted text-sm">Last Scan</div>
                        <div class="fw-600 text-sm"><?= $qr['last_scanned'] ? date('M d, H:i', strtotime($qr['last_scanned'])) : 'Never' ?></div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted text-sm">Status</div>
                        <span class="badge bg-<?= $qr['is_active'] ? 'success' : 'danger' ?>">
                            <?= $qr['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Instructions -->
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>How to Use</div>
            <div class="card-body">
                <ol class="list-unstyled">
                    <li class="d-flex gap-3 mb-4">
                        <div class="step-num">1</div>
                        <div>
                            <div class="fw-600">Open QR Scanner</div>
                            <div class="text-muted text-sm">Go to the attendance kiosk station or open the QR scan page on your phone</div>
                        </div>
                    </li>
                    <li class="d-flex gap-3 mb-4">
                        <div class="step-num">2</div>
                        <div>
                            <div class="fw-600">Show Your QR Code</div>
                            <div class="text-muted text-sm">Display this QR code on your phone screen or printed badge</div>
                        </div>
                    </li>
                    <li class="d-flex gap-3 mb-4">
                        <div class="step-num">3</div>
                        <div>
                            <div class="fw-600">Auto-Detection</div>
                            <div class="text-muted text-sm">The system automatically detects if you're checking in or out</div>
                        </div>
                    </li>
                    <li class="d-flex gap-3">
                        <div class="step-num">4</div>
                        <div>
                            <div class="fw-600">Confirmation</div>
                            <div class="text-muted text-sm">You'll see your name and a success message on screen</div>
                        </div>
                    </li>
                </ol>

                <hr>
                <div class="text-sm text-muted">
                    <div class="mb-2"><i class="bi bi-camera me-2"></i>Or use the <a href="<?= BASE_URL ?>/attendance/kiosk">Face Kiosk</a></div>
                    <div class="mb-2"><i class="bi bi-phone me-2"></i>Or use <a href="<?= BASE_URL ?>/attendance/mobile">Mobile GPS</a></div>
                    <div><i class="bi bi-question-circle me-2"></i>Wrong attendance? <a href="<?= BASE_URL ?>/corrections">Request correction</a></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.step-num {
    width: 32px; height: 32px; min-width: 32px;
    background: var(--primary, #6366F1); color: #fff;
    border-radius: 50%; display: flex; align-items: center;
    justify-content: center; font-weight: 700; font-size: 14px;
}
@media print {
    .btn, .card-header, nav, .topbar, .sidebar, .alert-warning { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>

<?php $csrf = CSRF::field(); ?>
<script>
document.getElementById('regenerateBtn').addEventListener('click', async function() {
    if (!confirm('Are you sure? Your current QR code will be invalidated immediately.')) return;
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Generating...';

    const fd = new FormData();
    fd.append('<?= CSRF_TOKEN_NAME ?>', '<?= Session::getInstance()->csrfToken() ?>');

    try {
        const resp = await fetch('<?= BASE_URL ?>/attendance/qr/regen', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) {
            document.getElementById('qrImg').src = data.qr_img;
            showToast('New QR code generated successfully', 'success');
        } else {
            showToast(data.message || 'Failed', 'error');
        }
    } catch(e) {
        showToast('Network error', 'error');
    }
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Regenerate';
});
</script>
