<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-phone me-2"></i>Mobile Attendance</h4>
        <p class="text-muted mb-0">Check in or out using your GPS location and a selfie</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/attendance/qr" class="btn btn-light"><i class="bi bi-qr-code"></i> My QR</a>
        <a href="<?= BASE_URL ?>/attendance/my" class="btn btn-light"><i class="bi bi-calendar-check"></i> History</a>
    </div>
</div>

<!-- Today status -->
<?php
$hasIn  = $todayRecord && $todayRecord['check_in'];
$hasOut = $todayRecord && $todayRecord['check_out'];
?>
<div class="card mb-4">
    <div class="card-body">
        <div class="row text-center g-3">
            <div class="col-md-3">
                <div class="text-muted text-sm">Status</div>
                <?php if ($hasOut): ?>
                    <span class="badge bg-secondary fs-6">Completed</span>
                <?php elseif ($hasIn): ?>
                    <span class="badge bg-success fs-6">Working</span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark fs-6">Not Yet</span>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <div class="text-muted text-sm">Check In</div>
                <div class="h5 mb-0 text-success"><?= $hasIn ? date('H:i', strtotime($todayRecord['check_in'])) : '—' ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted text-sm">Check Out</div>
                <div class="h5 mb-0 text-primary"><?= $hasOut ? date('H:i', strtotime($todayRecord['check_out'])) : '—' ?></div>
            </div>
            <div class="col-md-3">
                <div class="text-muted text-sm">Hours</div>
                <div class="h5 mb-0"><?= $todayRecord['working_hours'] ?? '0.00' ?>h</div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Camera + GPS Panel -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-camera me-2"></i>Selfie & GPS Verification</div>
            <div class="card-body">
                <!-- Camera preview -->
                <div id="cameraContainer" style="display:none;">
                    <div class="position-relative mb-3" style="background:#000;border-radius:12px;overflow:hidden;aspect-ratio:4/3;">
                        <video id="selfieVideo" autoplay muted playsinline style="width:100%;height:100%;object-fit:cover;"></video>
                        <canvas id="selfieCanvas" style="display:none;"></canvas>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary flex-fill" id="captureBtn">
                            <i class="bi bi-camera-fill"></i> Capture Selfie
                        </button>
                        <button class="btn btn-light" id="retakeBtn" style="display:none;">
                            <i class="bi bi-arrow-clockwise"></i> Retake
                        </button>
                    </div>
                </div>

                <!-- Selfie preview -->
                <div id="selfiePreview" style="display:none;" class="mb-3 text-center">
                    <img id="selfieImg" src="" alt="Selfie" style="width:160px;height:160px;object-fit:cover;border-radius:50%;border:3px solid var(--success,#10B981);">
                    <div class="mt-1 text-success text-sm"><i class="bi bi-check-circle me-1"></i>Selfie captured</div>
                </div>

                <!-- Start camera -->
                <button class="btn btn-outline-primary w-100 mb-3" id="startCameraBtn">
                    <i class="bi bi-camera me-2"></i>Open Camera
                </button>

                <!-- GPS Status -->
                <div class="border rounded-3 p-3 mb-3" id="gpsPanel">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="bi bi-geo-alt fs-5" id="gpsIcon"></i>
                        <strong id="gpsTitle">Acquiring GPS...</strong>
                    </div>
                    <div id="gpsDetails" class="text-sm text-muted"></div>
                    <div id="gpsDistance" class="mt-2 text-sm fw-600" style="display:none;"></div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex gap-2">
                    <?php if (!$hasIn): ?>
                    <button class="btn btn-success flex-fill btn-lg" id="checkInBtn" disabled>
                        <i class="bi bi-box-arrow-in-right me-2"></i>Check In
                    </button>
                    <?php elseif (!$hasOut): ?>
                    <button class="btn btn-primary flex-fill btn-lg" id="checkOutBtn" disabled>
                        <i class="bi bi-box-arrow-right me-2"></i>Check Out
                    </button>
                    <?php else: ?>
                    <div class="alert alert-success w-100 text-center mb-0">
                        <i class="bi bi-check-circle me-2"></i>You have completed attendance for today.
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Error / Result -->
                <div id="mobileError" class="alert alert-danger mt-3" style="display:none;"></div>
                <div id="mobileResult" class="alert mt-3" style="display:none;"></div>
            </div>
        </div>
    </div>

    <!-- Info Panel -->
    <div class="col-lg-6">
        <!-- Office location -->
        <?php if (!empty($employee['branch_name'])): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-building me-2"></i>Your Office</div>
            <div class="card-body">
                <div class="fw-600"><?= htmlspecialchars($employee['branch_name']) ?></div>
                <?php if ($employee['blat']): ?>
                    <div class="text-muted text-sm mt-1">
                        <i class="bi bi-geo me-1"></i><?= $employee['blat'] ?>, <?= $employee['blng'] ?>
                    </div>
                    <div class="text-sm mt-1">
                        <i class="bi bi-circle me-1 text-primary"></i>
                        Attendance radius: <strong><?= number_format($employee['geofence_radius'] ?? 200) ?>m</strong>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Rules -->
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Mobile Attendance Rules</div>
            <div class="card-body text-sm">
                <div class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success mt-1"></i><span>You must be within the office geofence radius</span></div>
                <div class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success mt-1"></i><span>GPS accuracy must be better than 100m</span></div>
                <div class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success mt-1"></i><span>A selfie photo is captured for verification</span></div>
                <div class="d-flex gap-2 mb-2"><i class="bi bi-check2-circle text-success mt-1"></i><span>Attendance records are time-stamped and logged</span></div>
                <div class="d-flex gap-2"><i class="bi bi-check2-circle text-success mt-1"></i><span>Wrong records can be corrected via <a href="<?= BASE_URL ?>/corrections">Correction Requests</a></span></div>
            </div>
        </div>

        <!-- Quick links -->
        <div class="card">
            <div class="card-header"><i class="bi bi-lightning me-2"></i>Quick Access</div>
            <div class="list-group list-group-flush">
                <a href="<?= BASE_URL ?>/attendance/kiosk" class="list-group-item list-group-item-action" target="_blank">
                    <i class="bi bi-display me-2"></i>Face Recognition Kiosk
                </a>
                <a href="<?= BASE_URL ?>/attendance/qr" class="list-group-item list-group-item-action">
                    <i class="bi bi-qr-code me-2"></i>My QR Code
                </a>
                <a href="<?= BASE_URL ?>/corrections" class="list-group-item list-group-item-action">
                    <i class="bi bi-pencil-square me-2"></i>Request Correction
                </a>
                <a href="<?= BASE_URL ?>/attendance/my" class="list-group-item list-group-item-action">
                    <i class="bi bi-calendar3 me-2"></i>View Attendance History
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const CSRF   = '<?= Session::getInstance()->csrfToken() ?>';
const BASE   = '<?= BASE_URL ?>';
let gpsLat = null, gpsLng = null, gpsAcc = null;
let selfieData = null;
let videoStream = null;
let selfieCanvas = document.getElementById('selfieCanvas');
let selfieCtx    = selfieCanvas.getContext('2d');

// ─── GPS ─────────────────────────────────────────────────────
if (navigator.geolocation) {
    navigator.geolocation.watchPosition(pos => {
        gpsLat = pos.coords.latitude;
        gpsLng = pos.coords.longitude;
        gpsAcc = pos.coords.accuracy;
        const ok = gpsAcc < 100;
        document.getElementById('gpsIcon').className  = 'bi bi-geo-alt-fill fs-5 text-' + (ok ? 'success' : 'warning');
        document.getElementById('gpsTitle').textContent = ok ? 'GPS Location Acquired' : 'GPS Weak (move outside)';
        document.getElementById('gpsDetails').textContent = `${gpsLat.toFixed(5)}, ${gpsLng.toFixed(5)} · Accuracy: ±${Math.round(gpsAcc)}m`;
        enableActionBtns();
    }, err => {
        document.getElementById('gpsIcon').className  = 'bi bi-geo-alt fs-5 text-danger';
        document.getElementById('gpsTitle').textContent = 'GPS unavailable';
        document.getElementById('gpsDetails').textContent = err.message;
    }, { enableHighAccuracy: true, timeout: 15000 });
} else {
    document.getElementById('gpsTitle').textContent = 'GPS not supported by your browser';
}

function enableActionBtns() {
    const ok = gpsLat && gpsAcc < 100;
    ['checkInBtn','checkOutBtn'].forEach(id => {
        const b = document.getElementById(id);
        if (b) b.disabled = !ok;
    });
}

// ─── Camera ──────────────────────────────────────────────────
document.getElementById('startCameraBtn')?.addEventListener('click', async () => {
    try {
        videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'user', width:640, height:480 } });
        document.getElementById('selfieVideo').srcObject = videoStream;
        document.getElementById('cameraContainer').style.display = '';
        document.getElementById('startCameraBtn').style.display = 'none';
    } catch(err) {
        showError('Camera error: ' + err.message);
    }
});

document.getElementById('captureBtn')?.addEventListener('click', () => {
    const video = document.getElementById('selfieVideo');
    selfieCanvas.width  = video.videoWidth;
    selfieCanvas.height = video.videoHeight;
    selfieCtx.drawImage(video, 0, 0);
    selfieData = selfieCanvas.toDataURL('image/jpeg', 0.8);

    document.getElementById('selfieImg').src = selfieData;
    document.getElementById('selfiePreview').style.display = '';
    document.getElementById('cameraContainer').style.display = 'none';
    document.getElementById('retakeBtn').style.display = '';
    stopCamera();
});

document.getElementById('retakeBtn')?.addEventListener('click', async () => {
    selfieData = null;
    document.getElementById('selfiePreview').style.display = 'none';
    document.getElementById('startCameraBtn').style.display = '';
    document.getElementById('retakeBtn').style.display = 'none';
});

function stopCamera() {
    if (videoStream) { videoStream.getTracks().forEach(t => t.stop()); videoStream = null; }
}

// ─── Submit ───────────────────────────────────────────────────
async function submitAttendance(action) {
    if (!gpsLat) { showError('GPS location required'); return; }

    const btn = document.getElementById(action === 'check_in' ? 'checkInBtn' : 'checkOutBtn');
    if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...'; }

    clearMessages();
    const fd = new FormData();
    fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF);
    fd.append('action',    action);
    fd.append('latitude',  gpsLat);
    fd.append('longitude', gpsLng);
    fd.append('accuracy',  gpsAcc || 999);
    if (selfieData) fd.append('selfie', selfieData);

    try {
        const resp = await fetch(BASE + '/attendance/mobile', { method:'POST', body:fd });
        const data = await resp.json();

        if (data.success) {
            showSuccess(action, data);
            if (btn) btn.style.display = 'none';
        } else if (data.geofence_failed) {
            showError(`⚠️ You are ${data.distance}m from the office. Must be within ${data.radius}m. ${data.message}`);
            if (btn) { btn.disabled = false; btn.innerHTML = action === 'check_in' ? '<i class="bi bi-box-arrow-in-right me-2"></i>Check In' : '<i class="bi bi-box-arrow-right me-2"></i>Check Out'; }
        } else {
            showError(data.message || 'Failed');
            if (btn) { btn.disabled = false; btn.innerHTML = action === 'check_in' ? '<i class="bi bi-box-arrow-in-right me-2"></i>Check In' : '<i class="bi bi-box-arrow-right me-2"></i>Check Out'; }
        }
    } catch(e) {
        showError('Network error: ' + e.message);
        if (btn) { btn.disabled = false; }
    }
}

document.getElementById('checkInBtn')?.addEventListener('click',  () => submitAttendance('check_in'));
document.getElementById('checkOutBtn')?.addEventListener('click', () => submitAttendance('check_out'));

function showSuccess(action, data) {
    const el = document.getElementById('mobileResult');
    const time = new Date().toLocaleTimeString('en-US',{hour12:false});
    el.className = 'alert alert-success mt-3';
    el.innerHTML = `
        <div class="d-flex gap-3 align-items-start">
            <i class="bi bi-check-circle-fill fs-4 text-success"></i>
            <div>
                <strong>${data.message}</strong><br>
                <span class="text-muted text-sm">${time}</span>
                ${data.status === 'late' && data.late_minutes ? `<br><span class="text-warning">⚠️ ${data.late_minutes} minutes late</span>` : ''}
                ${action === 'check_out' && data.working_hours ? `<br>Total hours: <strong>${data.working_hours}h</strong>` : ''}
                ${data.distance ? `<br><i class="bi bi-geo-alt me-1"></i>Distance from office: ${data.distance}m` : ''}
            </div>
        </div>`;
    el.style.display = '';
}
function showError(msg) {
    const el = document.getElementById('mobileError');
    el.textContent = msg; el.style.display = '';
}
function clearMessages() {
    document.getElementById('mobileError').style.display  = 'none';
    document.getElementById('mobileResult').style.display = 'none';
}
window.addEventListener('beforeunload', stopCamera);
</script>
