<?php /** @var array $data */ ?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars(Session::getInstance()->csrfToken()) ?>">
    <title>QR Attendance — <?= APP_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
    <script>(function(){ const t=localStorage.getItem('theme')||'light'; document.documentElement.setAttribute('data-theme',t); })();</script>
    <style>
        :root { --bg:#0F172A; --card:#1E293B; --border:#334155; --text:#F1F5F9; --muted:#94A3B8; --primary:#6366F1; --success:#10B981; --danger:#EF4444; }
        body { font-family:'Inter',sans-serif; background:var(--bg); color:var(--text); min-height:100vh; display:flex; flex-direction:column; }
        [data-theme="light"] { --bg:#F8FAFC; --card:#fff; --border:#E2E8F0; --text:#1E293B; --muted:#64748B; }
        .page-header { background:var(--card); border-bottom:1px solid var(--border); padding:16px 24px; display:flex; align-items:center; justify-content:space-between; }
        .brand { font-weight:800; font-size:20px; display:flex; align-items:center; gap:8px; color:var(--primary); }
        .main { flex:1; display:flex; align-items:center; justify-content:center; padding:24px; }
        .scan-card { background:var(--card); border:1px solid var(--border); border-radius:20px; padding:32px; max-width:420px; width:100%; text-align:center; }
        .scan-icon { font-size:64px; color:var(--primary); margin-bottom:16px; }
        .scan-title { font-size:22px; font-weight:800; margin-bottom:8px; }
        .scan-sub { color:var(--muted); font-size:14px; margin-bottom:24px; }
        .camera-wrap { background:#000; border-radius:16px; overflow:hidden; aspect-ratio:1; position:relative; margin-bottom:20px; }
        #scanVideo { width:100%; height:100%; object-fit:cover; display:block; }
        .scan-line { position:absolute; left:10%; width:80%; height:3px; background:linear-gradient(90deg,transparent,var(--primary),transparent); animation:scan 2s linear infinite; top:0; }
        @keyframes scan { 0%{top:10%} 100%{top:90%} }
        .qr-frame { position:absolute; inset:15%; border:3px solid var(--primary); border-radius:12px; opacity:.6; }
        .btn-main { width:100%; padding:14px; border-radius:12px; font-size:16px; font-weight:700; border:none; cursor:pointer; transition:.2s; }
        .btn-scan  { background:var(--primary); color:#fff; }
        .btn-scan:hover { opacity:.9; transform:translateY(-1px); }
        .btn-manual { background:var(--border); color:var(--text); margin-top:10px; }
        .result-panel { border-radius:16px; padding:24px; animation:popIn .4s; }
        .result-panel.success { background:rgba(16,185,129,.15); border:1px solid var(--success); }
        .result-panel.error   { background:rgba(239,68,68,.15);  border:1px solid var(--danger); }
        .result-emoji { font-size:56px; margin-bottom:12px; display:block; }
        .result-name  { font-size:20px; font-weight:800; }
        .result-time  { font-size:32px; font-weight:800; color:var(--primary); margin-top:6px; }
        .status-chip  { display:inline-block; padding:4px 12px; border-radius:100px; font-size:12px; font-weight:700; margin-top:6px; }
        .chip-present { background:rgba(16,185,129,.2); color:#6EE7B7; }
        .chip-late    { background:rgba(245,158,11,.2);  color:#FCD34D; }
        .chip-out     { background:rgba(99,102,241,.2);  color:#A5B4FC; }
        .manual-input { text-align:left; }
        .manual-input input { background:var(--border); border:1px solid var(--border); color:var(--text); border-radius:8px; padding:10px 14px; width:100%; font-size:14px; }
        .clock { font-size:28px; font-weight:800; color:var(--primary); letter-spacing:2px; }
        @keyframes popIn { from{opacity:0;transform:scale(.9)} to{opacity:1;transform:scale(1)} }
    </style>
</head>
<body>
<div class="page-header">
    <div class="brand"><i class="bi bi-shield-check"></i><?= APP_NAME ?></div>
    <div class="clock" id="clock">--:--:--</div>
</div>

<div class="main">
    <div class="scan-card">
        <!-- Mode: URL token provided (deep link scan) -->
        <?php if (!empty($token)): ?>
            <div id="autoScanPanel">
                <div class="scan-icon"><i class="bi bi-qr-code-scan"></i></div>
                <div class="scan-title">Processing QR Code</div>
                <div class="scan-sub">Please wait while we verify your identity...</div>
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        <?php else: ?>
            <!-- Mode: Camera scanner -->
            <div id="scanPanel">
                <div class="scan-icon"><i class="bi bi-camera"></i></div>
                <div class="scan-title">QR Code Check-In</div>
                <div class="scan-sub">Point your QR code at the camera, or enter it manually below</div>

                <div class="camera-wrap" id="cameraWrap" style="display:none;">
                    <video id="scanVideo" autoplay muted playsinline></video>
                    <div class="qr-frame"></div>
                    <div class="scan-line"></div>
                </div>

                <button class="btn-main btn-scan mb-2" id="startCameraBtn">
                    <i class="bi bi-camera me-2"></i>Start Camera Scanner
                </button>

                <hr style="border-color:var(--border); margin:16px 0;">

                <div class="manual-input">
                    <label style="font-size:13px;color:var(--muted);margin-bottom:6px;display:block;">Enter QR Token Manually</label>
                    <input type="text" id="manualToken" placeholder="Paste your QR token here..." autocomplete="off">
                    <button class="btn-main btn-manual mt-2" id="manualSubmitBtn">
                        <i class="bi bi-arrow-right-circle me-2"></i>Submit
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <!-- Result panel (hidden initially) -->
        <div id="resultPanel" style="display:none;"></div>

        <!-- Try again button -->
        <button class="btn-main btn-manual mt-3" id="tryAgainBtn" style="display:none;" onclick="resetScan()">
            <i class="bi bi-arrow-clockwise me-2"></i>Scan Again
        </button>
    </div>
</div>

<!-- jsQR for camera scanning -->
<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
<script>
const CSRF   = document.querySelector('meta[name="csrf-token"]').content;
const BASE   = '<?= BASE_URL ?>';
const TOKEN  = '<?= addslashes($token ?? '') ?>';
let videoStream = null;
let scanning    = false;
let scanCanvas  = document.createElement('canvas');
let scanCtx     = scanCanvas.getContext('2d');

// Clock
setInterval(() => {
    const n = new Date();
    document.getElementById('clock').textContent = n.toLocaleTimeString('en-US', {hour12:false});
}, 1000);

// Auto-process if token in URL
if (TOKEN) {
    setTimeout(() => processToken(TOKEN), 500);
}

// Camera scanner
document.getElementById('startCameraBtn')?.addEventListener('click', async () => {
    const btn = document.getElementById('startCameraBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Accessing camera...';

    try {
        videoStream = await navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' } });
        const video = document.getElementById('scanVideo');
        video.srcObject = videoStream;
        await video.play();

        document.getElementById('cameraWrap').style.display = '';
        btn.style.display = 'none';
        scanning = true;
        requestAnimationFrame(scanFrame);
    } catch(err) {
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-camera me-2"></i>Start Camera Scanner';
        alert('Camera error: ' + err.message + '\n\nPlease use the manual token input below.');
    }
});

function scanFrame() {
    if (!scanning) return;
    const video = document.getElementById('scanVideo');
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
        scanCanvas.width  = video.videoWidth;
        scanCanvas.height = video.videoHeight;
        scanCtx.drawImage(video, 0, 0);
        const imgData = scanCtx.getImageData(0, 0, scanCanvas.width, scanCanvas.height);
        const code    = jsQR(imgData.data, imgData.width, imgData.height, { inversionAttempts: 'dontInvert' });
        if (code && code.data) {
            scanning = false;
            stopCamera();
            // Extract token from URL if it's a full URL
            let token = code.data;
            if (token.includes('?token=')) token = new URL(token).searchParams.get('token');
            processToken(token);
            return;
        }
    }
    requestAnimationFrame(scanFrame);
}

// Manual submit
document.getElementById('manualSubmitBtn')?.addEventListener('click', () => {
    const t = document.getElementById('manualToken').value.trim();
    if (!t) { alert('Please enter a QR token'); return; }
    processToken(t);
});

document.getElementById('manualToken')?.addEventListener('keypress', e => {
    if (e.key === 'Enter') document.getElementById('manualSubmitBtn').click();
});

async function processToken(token) {
    // Get GPS location
    let lat = null, lng = null;
    try {
        const pos = await new Promise((res, rej) => navigator.geolocation.getCurrentPosition(res, rej, {timeout:5000}));
        lat = pos.coords.latitude;
        lng = pos.coords.longitude;
    } catch(e) {}

    const fd = new FormData();
    fd.append('<?= CSRF_TOKEN_NAME ?>', CSRF);
    fd.append('token', token);
    if (lat) fd.append('latitude', lat);
    if (lng) fd.append('longitude', lng);

    try {
        const resp = await fetch(BASE + '/attendance/qr-scan', { method: 'POST', body: fd });
        const data = await resp.json();
        showResult(data);
    } catch(err) {
        showResult({ success: false, message: 'Network error. Please try again.' });
    }
}

function showResult(data) {
    document.getElementById('scanPanel')?.style.setProperty('display','none');
    document.getElementById('autoScanPanel')?.style.setProperty('display','none');

    const panel  = document.getElementById('resultPanel');
    const time   = new Date().toLocaleTimeString('en-US', {hour12:false});
    const emp    = data.employee;

    if (data.already_done) {
        panel.innerHTML = `
            <div class="result-panel success">
                <span class="result-emoji">ℹ️</span>
                <div class="result-name">${emp ? emp.full_name : 'Employee'}</div>
                <div style="color:var(--muted);margin-top:6px;">${data.message}</div>
            </div>`;
    } else if (data.success) {
        const isIn    = data.action === 'check_in';
        const emoji   = isIn ? '✅' : '👋';
        const chipCls = isIn ? (data.status === 'late' ? 'chip-late' : 'chip-present') : 'chip-out';
        const chipLbl = isIn ? (data.status === 'late' ? 'LATE' : 'ON TIME') : 'CHECKED OUT';
        panel.innerHTML = `
            <div class="result-panel success">
                <span class="result-emoji">${emoji}</span>
                ${emp ? `<div class="result-name">${emp.full_name}</div><div style="font-size:13px;color:var(--muted);">${emp.employee_code||''}</div>` : ''}
                <div style="font-size:15px;font-weight:600;margin-top:10px;">${data.message}</div>
                <div class="result-time">${time}</div>
                <div><span class="status-chip ${chipCls}">${chipLbl}</span></div>
                ${isIn && data.late_minutes > 0 ? `<div style="font-size:12px;color:#FCD34D;margin-top:4px;">${data.late_minutes} minutes late</div>` : ''}
                ${!isIn && data.working_hours ? `<div style="font-size:13px;color:var(--muted);margin-top:4px;">Worked: ${data.working_hours}h</div>` : ''}
            </div>`;
        // Auto-reset after 6s
        setTimeout(resetScan, 6000);
    } else {
        panel.innerHTML = `
            <div class="result-panel error">
                <span class="result-emoji">❌</span>
                <div class="result-name">Not Recognized</div>
                <div style="color:var(--muted);margin-top:8px;font-size:14px;">${data.message}</div>
            </div>`;
    }

    panel.style.display = '';
    document.getElementById('tryAgainBtn').style.display = '';
}

function resetScan() {
    document.getElementById('resultPanel').style.display = 'none';
    document.getElementById('tryAgainBtn').style.display = 'none';
    const sp = document.getElementById('scanPanel');
    if (sp) { sp.style.display = ''; if (document.getElementById('manualToken')) document.getElementById('manualToken').value = ''; }
    scanning = false;
}

function stopCamera() {
    if (videoStream) { videoStream.getTracks().forEach(t => t.stop()); videoStream = null; }
    document.getElementById('cameraWrap')?.style.setProperty('display','none');
    const btn = document.getElementById('startCameraBtn');
    if (btn) { btn.style.display = ''; btn.disabled = false; btn.innerHTML = '<i class="bi bi-camera me-2"></i>Start Camera Scanner'; }
}
</script>
</body>
</html>
