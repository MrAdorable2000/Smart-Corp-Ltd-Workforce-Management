<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-qr-code me-2"></i>Employee QR Codes</h4>
        <p class="text-muted mb-0">Manage and view all employee attendance QR codes</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary" id="bulkGenBtn">
            <i class="bi bi-qr-code-scan"></i> Generate Missing QRs
        </button>
        <a href="<?= BASE_URL ?>/attendance/qr-scan" class="btn btn-light" target="_blank">
            <i class="bi bi-camera"></i> Open Scanner
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card stat-violet">
            <div class="stat-icon"><i class="bi bi-people"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= count($qrs) ?></div>
                <div class="stat-label">Active QR Codes</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-emerald">
            <div class="stat-icon"><i class="bi bi-qr-code-scan"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= array_sum(array_column($qrs, 'scan_count')) ?></div>
                <div class="stat-label">Total Scans</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-sky">
            <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= count(array_filter($qrs, fn($q) => $q['last_scanned'] && date('Y-m-d', strtotime($q['last_scanned'])) === date('Y-m-d'))) ?></div>
                <div class="stat-label">Scanned Today</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card stat-amber">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= count(array_filter($qrs, fn($q) => !$q['last_scanned'])) ?></div>
                <div class="stat-label">Never Scanned</div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>QR Code Registry (<?= count($qrs) ?> codes)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="qrTable">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Department</th>
                        <th>QR Preview</th>
                        <th class="text-center">Scans</th>
                        <th>Last Scanned</th>
                        <th>Expires</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($qrs as $q): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar" style="width:34px;height:34px;font-size:12px;">
                                    <?= strtoupper(substr($q['first_name'],0,1).substr($q['last_name'],0,1)) ?>
                                </div>
                                <div>
                                    <div class="fw-600"><?= htmlspecialchars($q['first_name'].' '.$q['last_name']) ?></div>
                                    <div class="text-muted text-sm"><?= htmlspecialchars($q['employee_code']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm"><?= htmlspecialchars($q['dept'] ?? '—') ?></td>
                        <td>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=60x60&data=<?= urlencode($q['qr_token']) ?>&format=png"
                                 alt="QR" width="50" height="50" style="border-radius:6px;border:1px solid #e2e8f0;"
                                 loading="lazy">
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $q['scan_count'] > 0 ? 'primary' : 'light text-muted' ?>">
                                <?= number_format($q['scan_count']) ?>
                            </span>
                        </td>
                        <td class="text-sm text-muted">
                            <?= $q['last_scanned'] ? date('M d, H:i', strtotime($q['last_scanned'])) : 'Never' ?>
                        </td>
                        <td class="text-sm">
                            <?php if (!$q['expires_at']): ?>
                                <span class="text-muted">Permanent</span>
                            <?php elseif (strtotime($q['expires_at']) < time()): ?>
                                <span class="text-danger">Expired</span>
                            <?php else: ?>
                                <span class="text-success"><?= date('M d, Y', strtotime($q['expires_at'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-<?= $q['is_active'] ? 'success' : 'danger' ?>">
                                <?= $q['is_active'] ? 'Active' : 'Inactive' ?>
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=<?= urlencode($q['qr_token']) ?>&format=png"
                                   download="qr-<?= $q['employee_code'] ?>.png"
                                   class="btn btn-sm btn-light" title="Download QR">
                                    <i class="bi bi-download"></i>
                                </a>
                                <button class="btn btn-sm btn-warning" onclick="regenQr(<?= $q['employee_id'] ?>)" title="Regenerate">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($qrs)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-qr-code display-4 d-block opacity-50 mb-2"></i>
                            No QR codes generated yet. Click "Generate Missing QRs" to create them.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
const CSRF = '<?= Session::getInstance()->csrfToken() ?>';
const BASE = '<?= BASE_URL ?>';

document.getElementById('bulkGenBtn').addEventListener('click', async function() {
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Generating…';
    const fd = new FormData();
    fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF);
    try {
        const resp = await fetch(BASE + '/attendance/qr-bulk', { method:'POST', body:fd });
        const data = await resp.json();
        showToast(data.message, data.success ? 'success' : 'error');
        if (data.success) setTimeout(() => location.reload(), 1500);
    } catch(e) { showToast('Error: ' + e.message, 'error'); }
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-qr-code-scan"></i> Generate Missing QRs';
});

async function regenQr(empId) {
    if (!confirm('Regenerate QR? The old one will stop working immediately.')) return;
    const fd = new FormData();
    fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF);
    // Temporarily set employee session for regen
    const resp = await fetch(BASE + '/attendance/qr/regen?eid=' + empId, { method:'POST', body:fd });
    const data = await resp.json();
    showToast(data.message || 'Done', data.success ? 'success' : 'error');
    if (data.success) setTimeout(() => location.reload(), 1200);
}
</script>
