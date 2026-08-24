<?php
/**
 * Face Recognition Attendance — Authenticated Employee Page
 * Uses /face/match (now public) → /attendance/check-in or /attendance/check-out
 */
$hasCheckedIn  = !empty($todayRecord['check_in']);
$hasCheckedOut = !empty($todayRecord['check_out']);
$statusMap     = ['present'=>'success','late'=>'warning','absent'=>'danger','leave'=>'info','half_day'=>'secondary','remote'=>'primary'];
$statusBg      = $statusMap[$todayRecord['status'] ?? ''] ?? 'secondary';
?>

<!-- Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-camera-video-fill me-2 text-primary"></i>Face Recognition Attendance</h4>
        <p class="text-muted mb-0">Look at the camera and blink twice — attendance records automatically</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/attendance/qr" class="btn btn-light"><i class="bi bi-qr-code me-1"></i>QR Code</a>
        <a href="<?= BASE_URL ?>/attendance/mobile" class="btn btn-light"><i class="bi bi-phone me-1"></i>Mobile</a>
        <a href="<?= BASE_URL ?>/my-attendance" class="btn btn-light"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
    </div>
</div>

<!-- Today status bar -->
<div class="card mb-4">
    <div class="card-body py-3">
        <div class="row text-center g-3">
            <div class="col-md-3">
                <div class="text-muted text-sm">Check-In</div>
                <div class="h4 mb-0 text-success fw-800">
                    <?= $hasCheckedIn ? date('H:i:s', strtotime($todayRecord['check_in'])) : '—' ?>
                </div>
                <?php if ($hasCheckedIn): ?>
                <span class="badge bg-<?= $statusBg ?>"><?= ucfirst(str_replace('_',' ',$todayRecord['status'])) ?></span>
                <?php endif; ?>
            </div>
            <div class="col-md-3 border-start border-end">
                <div class="text-muted text-sm">Check-Out</div>
                <div class="h4 mb-0 text-primary fw-800">
                    <?= $hasCheckedOut ? date('H:i:s', strtotime($todayRecord['check_out'])) : '—' ?>
                </div>
            </div>
            <div class="col-md-3 border-end">
                <div class="text-muted text-sm">Hours Worked</div>
                <div class="h4 mb-0">
                    <?= isset($todayRecord['working_hours']) && $todayRecord['working_hours'] > 0
                        ? number_format($todayRecord['working_hours'], 2) . 'h'
                        : '—' ?>
                </div>
            </div>
            <div class="col-md-3">
                <div class="text-muted text-sm">Today</div>
                <div class="fw-600"><?= date('l') ?></div>
                <div class="text-muted text-sm"><?= date('M j, Y') ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Main Layout -->
<div class="row g-4">
    <!-- Camera -->
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-camera-video me-2"></i>Face Recognition Camera</span>
                <span id="scanStatusBadge" class="badge bg-secondary">Ready</span>
            </div>
            <div class="card-body p-0">
                <!-- Camera container -->
                <div style="position:relative;background:#000;min-height:320px;" id="cameraBox">
                    <video id="faceVideo" autoplay muted playsinline
                           style="width:100%;display:block;max-height:400px;object-fit:cover;"></video>
                    <canvas id="faceCanvas" style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;"></canvas>

                    <!-- Face oval guide -->
                    <div id="faceOval" style="
                        position:absolute;top:50%;left:50%;
                        transform:translate(-50%,-55%);
                        width:180px;height:220px;
                        border:3px solid rgba(99,102,241,.5);
                        border-radius:50%;pointer-events:none;
                        transition:border-color .4s,box-shadow .4s;
                    "></div>

                    <!-- Status overlay -->
                    <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(0,0,0,.75);backdrop-filter:blur(6px);padding:10px 16px;">
                        <div id="camStatusText" style="font-size:14px;font-weight:600;color:#fff;">
                            <i class="bi bi-camera-video-off me-2"></i>Click "Start Camera" to begin
                        </div>
                    </div>

                    <!-- Start overlay -->
                    <div id="startOverlay" style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;background:rgba(0,0,0,.6);">
                        <button id="startCamBtn" class="btn btn-primary btn-lg px-4" style="font-size:16px;font-weight:700;">
                            <i class="bi bi-camera-video-fill me-2"></i>Start Camera
                        </button>
                    </div>
                </div>

                <!-- Liveness checklist -->
                <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:12px;">
                    <div class="lv-check" id="lv_blink">
                        <i class="bi bi-eye"></i>
                        <div class="lv-val" id="lv_blink_val">0/2</div>
                        <div class="lv-lbl">Blinks</div>
                    </div>
                    <div class="lv-check" id="lv_head">
                        <i class="bi bi-arrow-left-right"></i>
                        <div class="lv-val" id="lv_head_val">No</div>
                        <div class="lv-lbl">Head Turn</div>
                    </div>
                    <div class="lv-check" id="lv_size">
                        <i class="bi bi-person-bounding-box"></i>
                        <div class="lv-val" id="lv_size_val">—</div>
                        <div class="lv-lbl">Face Size</div>
                    </div>
                    <div class="lv-check" id="lv_live">
                        <i class="bi bi-shield-check"></i>
                        <div class="lv-val" id="lv_live_val">Wait</div>
                        <div class="lv-lbl">Liveness</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Right panel -->
    <div class="col-lg-5">
        <!-- Result -->
        <div class="card mb-3" id="resultCard">
            <div class="card-body text-center py-4" id="resultArea">
                <i class="bi bi-person-bounding-box" style="font-size:48px;color:var(--border-color,#e2e8f0);display:block;margin-bottom:10px;"></i>
                <p class="text-muted">Complete liveness check and face will be matched automatically</p>
            </div>
        </div>

        <!-- Error display -->
        <div id="errorAlert" class="alert alert-danger" style="display:none;"></div>

        <!-- Instructions -->
        <div class="card">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>Instructions</div>
            <div class="card-body">
                <ol class="mb-0" style="font-size:13px;padding-left:18px;">
                    <li class="mb-2">Click <strong>Start Camera</strong> to activate face recognition</li>
                    <li class="mb-2">Position your face inside the oval guide</li>
                    <li class="mb-2"><strong>Blink twice</strong> naturally to confirm liveness</li>
                    <li class="mb-2">Turn your head slightly left or right</li>
                    <li class="mb-2">Stay still — attendance records in under 5 seconds</li>
                </ol>
                <hr>
                <div class="text-sm text-muted">
                    <i class="bi bi-shield-lock me-1"></i>90%+ confidence required · Anti-spoofing active
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.lv-check{background:rgba(0,0,0,.04);border:1px solid var(--border-color,#e2e8f0);border-radius:10px;padding:8px;text-align:center;transition:all .3s;}
.lv-check i{font-size:18px;display:block;margin-bottom:3px;color:#94A3B8;}
.lv-val{font-size:13px;font-weight:700;color:#64748B;}
.lv-lbl{font-size:10px;color:#94A3B8;margin-top:2px;}
.lv-check.pass{background:rgba(16,185,129,.1);border-color:rgba(16,185,129,.4);}
.lv-check.pass i,.lv-check.pass .lv-val{color:#10B981;}
[data-theme="dark"] .lv-check{background:rgba(255,255,255,.04);border-color:#1E3054;}
[data-theme="dark"] .lv-check.pass{background:rgba(16,185,129,.15);}
</style>

<?php
// These vars are injected into JS via json_encode — safe and correct
$jsConfig = json_encode([
    'BASE_URL'   => BASE_URL,
    'CSRF'       => Session::getInstance()->csrfToken(),
    'CSRF_NAME'  => CSRF_TOKEN_NAME,
    'EMP_ID'     => Auth::employeeId(),
    'HAS_IN'     => $hasCheckedIn,
    'HAS_OUT'    => $hasCheckedOut,
]);
?>

<!-- Load face-api BEFORE the inline script block -->
<script>window.ASSET_URL = '<?= ASSET_URL ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="<?= ASSET_URL ?>/js/face-recognition.js"></script>
<script>
(function() {
'use strict';

const CFG       = <?= $jsConfig ?>;
const BASE      = CFG.BASE_URL;
const CSRF      = CFG.CSRF;
const CSRF_NAME = CFG.CSRF_NAME;
const EMP_ID    = CFG.EMP_ID;

let cameraStarted = false;
let lastScanTime  = 0;
const COOLDOWN_MS = 8000;

// ── Liveness UI ──────────────────────────────────────────────
window.updateLivenessUI = function(state, faceCount) {
    const b1 = state.blinkCount >= 2;
    const b2 = state.headMovements >= 1;
    const b3 = state.faceSizeOk;
    const b4 = FaceRecognition.livenessReady();

    document.getElementById('lv_blink_val').textContent = Math.min(state.blinkCount,2)+'/2';
    document.getElementById('lv_head_val').textContent  = b2 ? 'Yes ✓' : 'No';
    document.getElementById('lv_size_val').textContent  = b3 ? 'OK ✓' : '—';
    document.getElementById('lv_live_val').textContent  = b4 ? 'PASS ✓' : 'Wait';

    ['lv_blink','lv_head','lv_size','lv_live'].forEach((id,i) => {
        document.getElementById(id).className = 'lv-check' + ([b1,b2,b3,b4][i] ? ' pass' : '');
    });

    // Oval color
    const oval = document.getElementById('faceOval');
    if (faceCount === 0) {
        oval.style.borderColor = 'rgba(99,102,241,.4)'; oval.style.boxShadow = 'none';
    } else if (faceCount > 1) {
        oval.style.borderColor = '#EF4444'; oval.style.boxShadow = '0 0 20px rgba(239,68,68,.4)';
    } else if (b4) {
        oval.style.borderColor = '#F59E0B'; oval.style.boxShadow = '0 0 20px rgba(245,158,11,.4)';
    } else {
        oval.style.borderColor = '#6366F1'; oval.style.boxShadow = '0 0 16px rgba(99,102,241,.35)';
    }
};

// ── Status helpers ────────────────────────────────────────────
function setStatus(msg, type) {
    const el  = document.getElementById('camStatusText');
    const bdg = document.getElementById('scanStatusBadge');
    el.innerHTML = msg;
    const map = {success:'bg-success',error:'bg-danger',warning:'bg-warning',info:'bg-primary'};
    bdg.className = 'badge ' + (map[type] || 'bg-secondary');
}
FaceRecognition.onStatus(setStatus);

function showError(msg) {
    const el = document.getElementById('errorAlert');
    el.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>' + msg;
    el.style.display = '';
    setTimeout(() => el.style.display = 'none', 6000);
}
function clearError() { document.getElementById('errorAlert').style.display = 'none'; }

// ── Start camera ──────────────────────────────────────────────
document.getElementById('startCamBtn').addEventListener('click', async function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading AI…';
    clearError();

    try {
        await FaceRecognition.loadModels();
        await FaceRecognition.startCamera(document.getElementById('faceVideo'));
        FaceRecognition.setCanvas(document.getElementById('faceCanvas'));
        document.getElementById('startOverlay').style.display = 'none';
        cameraStarted = true;

        FaceRecognition.startDetectionLoop(onFaceDetected, { requireAntiSpoof: true });
    } catch(err) {
        showError(err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-camera-video-fill me-2"></i>Start Camera';
    }
});

// ── Face detected callback ────────────────────────────────────
async function onFaceDetected(descriptor, spoofData) {
    const now = Date.now();
    if (now - lastScanTime < COOLDOWN_MS) return;
    lastScanTime = now;
    FaceRecognition.markScanned();

    setStatus('<i class="bi bi-cpu me-2"></i>Identifying…', 'info');

    const snapshot = FaceRecognition.captureImage(0.7);

    const fd = new FormData();
    fd.append(CSRF_NAME,   CSRF);
    fd.append('descriptor', JSON.stringify(descriptor));
    fd.append('anti_spoof', JSON.stringify(spoofData));
    if (snapshot) fd.append('snapshot', snapshot);

    try {
        // Step 1: Match face
        const matchResp = await fetch(BASE + '/face/match', { method:'POST', body:fd });
        const matchData = await matchResp.json();

        if (!matchData.success) {
            showResultError(matchData.message || 'Not recognized', matchData.confidence || 0);
            setStatus('<i class="bi bi-x-circle me-2"></i>' + (matchData.message || 'Not recognized'), 'error');
            setTimeout(() => { FaceRecognition.resetLiveness(); lastScanTime = 0; }, 4000);
            return;
        }

        const emp = matchData.employee;
        setStatus('<i class="bi bi-check2-circle me-2"></i>Matched: ' + emp.full_name + ' · Recording attendance…', 'success');

        // Step 2: Determine action
        const action = await determineAction(emp.id);
        if (!action) {
            showResultInfo('Attendance already completed for today', emp, matchData.confidence);
            setTimeout(() => { FaceRecognition.resetLiveness(); lastScanTime = 0; }, 5000);
            return;
        }

        // Step 3: Record attendance
        const attFd = new FormData();
        attFd.append(CSRF_NAME,    CSRF);
        attFd.append('employee_id', emp.id);
        attFd.append('match_score', matchData.confidence || matchData.score || 0);

        // Get GPS if available
        if (window.userLat) attFd.append('latitude',  window.userLat);
        if (window.userLng) attFd.append('longitude', window.userLng);

        const url = action === 'check_in'
            ? BASE + '/attendance/check-in'
            : BASE + '/attendance/check-out';

        const attResp = await fetch(url, { method:'POST', body:attFd });
        const attData = await attResp.json();

        if (attData.success) {
            showResultSuccess(action, attData, emp, matchData.confidence);
            setTimeout(() => { resetResult(); FaceRecognition.resetLiveness(); lastScanTime = 0; }, 7000);
        } else {
            showError(attData.message || 'Attendance recording failed');
            setTimeout(() => { FaceRecognition.resetLiveness(); lastScanTime = 0; }, 4000);
        }

    } catch(err) {
        showError('Network error: ' + err.message);
        setTimeout(() => { FaceRecognition.resetLiveness(); lastScanTime = 0; }, 3000);
    }
}

async function determineAction(empId) {
    try {
        const resp = await fetch(BASE + '/api/attendance/today', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        const data = await resp.json();
        const rec  = (data.data || []).find(r => parseInt(r.employee_id) === parseInt(empId));
        if (!rec || !rec.check_in)  return 'check_in';
        if (rec.check_in && !rec.check_out) return 'check_out';
        return null; // already done both
    } catch(e) {
        return 'check_in';
    }
}

// ── Result displays ───────────────────────────────────────────
function showResultSuccess(action, attData, emp, confidence) {
    const isIn   = action === 'check_in';
    const time   = new Date(isIn ? attData.check_in_time : attData.check_out_time)
                       .toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',second:'2-digit',hour12:false});
    const isLate = attData.status === 'late';
    const conf   = Math.round(confidence || 0);
    const photo  = emp.photo
        ? `<img src="${BASE}/uploads/${emp.photo}" style="width:72px;height:72px;border-radius:50%;object-fit:cover;border:3px solid #10B981;margin-bottom:10px;" onerror="this.remove()">`
        : `<div style="width:72px;height:72px;border-radius:50%;background:linear-gradient(135deg,#6366F1,#8B5CF6);color:#fff;font-size:24px;font-weight:800;display:flex;align-items:center;justify-content:center;margin:0 auto 10px;">${(emp.full_name||'??').split(' ').map(w=>w[0]).join('').substring(0,2).toUpperCase()}</div>`;

    document.getElementById('resultArea').innerHTML = `
        <div style="animation:popIn .4s;">
            ${photo}
            <div style="font-size:18px;font-weight:800;margin-bottom:2px;">${emp.full_name}</div>
            <div style="font-size:12px;color:#94A3B8;margin-bottom:10px;">${emp.employee_code||''}</div>
            <div style="font-size:22px;font-weight:700;color:${isIn?'#10B981':'#6366F1'}">
                ${isIn ? '✅ Check-In Successful' : '👋 Check-Out Successful'}
            </div>
            <div style="font-size:36px;font-weight:900;color:#6366F1;margin:6px 0;font-variant-numeric:tabular-nums;">${time}</div>
            <div style="display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin-top:8px;">
                <span style="background:rgba(16,185,129,.15);color:#10B981;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;">
                    <i class="bi bi-shield-check"></i> ${conf}% match
                </span>
                ${isIn && isLate ? `<span style="background:rgba(245,158,11,.15);color:#F59E0B;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;">⚠️ ${attData.late_minutes}m late</span>` : ''}
                ${isIn && !isLate ? `<span style="background:rgba(16,185,129,.15);color:#10B981;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;">✓ On Time</span>` : ''}
                ${!isIn && attData.working_hours ? `<span style="background:rgba(59,130,246,.15);color:#3B82F6;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;">⏱ ${attData.working_hours}h worked</span>` : ''}
            </div>
        </div>`;
    document.getElementById('faceOval').style.borderColor = '#10B981';
    document.getElementById('faceOval').style.boxShadow  = '0 0 30px rgba(16,185,129,.5)';
}

function showResultError(msg, confidence) {
    document.getElementById('resultArea').innerHTML = `
        <div style="animation:popIn .4s;">
            <div style="font-size:48px;">🚫</div>
            <div style="font-size:16px;font-weight:700;color:#EF4444;margin:8px 0;">${msg}</div>
            ${confidence > 0 ? `<div style="font-size:12px;color:#94A3B8;">Confidence: ${Math.round(confidence)}% (minimum 90%)</div>` : ''}
        </div>`;
    document.getElementById('faceOval').style.borderColor = '#EF4444';
    document.getElementById('faceOval').style.boxShadow  = '0 0 20px rgba(239,68,68,.4)';
}

function showResultInfo(msg, emp, confidence) {
    document.getElementById('resultArea').innerHTML = `
        <div style="animation:popIn .4s;">
            <div style="font-size:48px;">ℹ️</div>
            <div style="font-size:16px;font-weight:700;margin:8px 0;">${emp.full_name||''}</div>
            <div style="font-size:14px;color:#94A3B8;">${msg}</div>
        </div>`;
}

function resetResult() {
    document.getElementById('resultArea').innerHTML = `
        <i class="bi bi-person-bounding-box" style="font-size:48px;color:var(--border-color,#e2e8f0);display:block;margin-bottom:10px;"></i>
        <p class="text-muted">Complete liveness check and face will be matched automatically</p>`;
    document.getElementById('faceOval').style.borderColor = 'rgba(99,102,241,.4)';
    document.getElementById('faceOval').style.boxShadow  = 'none';
}

// ── GPS ───────────────────────────────────────────────────────
if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(p => {
        window.userLat = p.coords.latitude;
        window.userLng = p.coords.longitude;
    }, () => {}, { timeout: 8000 });
}

window.addEventListener('beforeunload', () => FaceRecognition.stopCamera());
})();
</script>
<style>
@keyframes popIn{from{opacity:0;transform:scale(.9)}to{opacity:1;transform:scale(1)}}
</style>
