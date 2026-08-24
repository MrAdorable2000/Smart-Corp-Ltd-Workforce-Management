<?php
$companyName = $companyName ?? APP_NAME;
$csrfToken   = Session::getInstance()->csrfToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
<title>Attendance Kiosk — <?= htmlspecialchars($companyName) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
/* ── Reset & base ───────────────────────────────────── */
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --bg:#060B18;--surface:#0D1526;--card:#111E35;
  --border:#1E3054;--primary:#6366F1;--success:#10B981;
  --warn:#F59E0B;--danger:#EF4444;--text:#F1F5F9;
  --muted:#64748B;--dim:#94A3B8;
}
html,body{height:100%;overflow:hidden;font-family:'Inter',sans-serif;background:var(--bg);color:var(--text)}

/* ── Layout ─────────────────────────────────────────── */
.kiosk{display:grid;grid-template-rows:64px 1fr;height:100vh}

/* ── Header ─────────────────────────────────────────── */
.kiosk-header{
  background:var(--surface);
  border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  padding:0 28px;z-index:10;
}
.kiosk-brand{display:flex;align-items:center;gap:10px}
.kiosk-brand i{font-size:26px;color:var(--primary)}
.kiosk-brand .name{font-size:18px;font-weight:800;letter-spacing:-.3px}
.kiosk-brand .tag{font-size:11px;color:var(--muted);margin-left:4px;font-weight:500}
.kiosk-live{display:flex;align-items:center;gap:8px}
.live-pill{display:flex;align-items:center;gap:6px;background:rgba(16,185,129,.1);border:1px solid rgba(16,185,129,.3);color:#34D399;padding:4px 12px;border-radius:100px;font-size:12px;font-weight:600}
.live-dot{width:7px;height:7px;background:#10B981;border-radius:50%;animation:liveBlink 1.2s ease-in-out infinite}
@keyframes liveBlink{0%,100%{opacity:1;transform:scale(1)}50%{opacity:.4;transform:scale(.8)}}
.kiosk-clock-small{text-align:right}
.clock-t{font-size:22px;font-weight:800;color:var(--primary);letter-spacing:2px;font-variant-numeric:tabular-nums}
.clock-d{font-size:11px;color:var(--muted)}

/* ── Body ───────────────────────────────────────────── */
.kiosk-body{display:grid;grid-template-columns:1fr 380px;overflow:hidden}

/* ── Camera pane ────────────────────────────────────── */
.cam-pane{
  background:var(--surface);
  display:flex;flex-direction:column;
  border-right:1px solid var(--border);
  position:relative;overflow:hidden;
}
.cam-wrap{flex:1;position:relative;background:#000}
#kioskVideo{width:100%;height:100%;object-fit:cover;display:block}
#kioskCanvas{position:absolute;inset:0;pointer-events:none;width:100%;height:100%}

/* oval guide */
.face-oval{
  position:absolute;top:50%;left:50%;
  transform:translate(-50%,-52%);
  width:min(240px,35vw);height:min(300px,44vw);
  border:3px solid rgba(99,102,241,.5);
  border-radius:50%;pointer-events:none;
  transition:border-color .4s,box-shadow .4s;
}
.face-oval.detected  {border-color:#6366F1;box-shadow:0 0 30px rgba(99,102,241,.35)}
.face-oval.liveness  {border-color:#F59E0B;box-shadow:0 0 30px rgba(245,158,11,.35)}
.face-oval.matched   {border-color:#10B981;box-shadow:0 0 40px rgba(16,185,129,.5)}
.face-oval.rejected  {border-color:#EF4444;box-shadow:0 0 30px rgba(239,68,68,.4)}

/* scan line animation */
.scan-line{
  position:absolute;left:10%;width:80%;height:2px;
  background:linear-gradient(90deg,transparent,var(--primary),transparent);
  animation:scanAnim 2.5s ease-in-out infinite;top:20%;opacity:0;
  transition:opacity .3s;
}
.scan-line.active{opacity:1}
@keyframes scanAnim{0%{top:20%}100%{top:80%}}

/* status bar */
.cam-status{
  background:rgba(0,0,0,.75);
  backdrop-filter:blur(8px);
  padding:10px 20px;
  display:flex;align-items:center;justify-content:space-between;
  border-top:1px solid var(--border);
  min-height:52px;
}
.cam-status-text{font-size:15px;font-weight:600;transition:color .3s}
.cam-status-text.info   {color:var(--dim)}
.cam-status-text.success{color:#34D399}
.cam-status-text.warning{color:#FCD34D}
.cam-status-text.error  {color:#FCA5A5}

/* Start button */
.btn-start-cam{
  position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);
  background:linear-gradient(135deg,var(--primary),#8B5CF6);
  color:#fff;border:none;border-radius:16px;padding:18px 36px;
  font-size:18px;font-weight:700;cursor:pointer;
  display:flex;align-items:center;gap:10px;
  box-shadow:0 8px 32px rgba(99,102,241,.5);
  transition:all .2s;z-index:5;
}
.btn-start-cam:hover{transform:translate(-50%,-50%) scale(1.04)}
.btn-start-cam:disabled{opacity:.6;cursor:not-allowed;transform:translate(-50%,-50%)}

/* ── Liveness checklist ─────────────────────────────── */
.liveness-bar{
  display:grid;grid-template-columns:repeat(4,1fr);
  gap:8px;padding:12px 16px;
  background:rgba(0,0,0,.5);border-top:1px solid var(--border);
}
.lv-item{
  background:rgba(30,48,84,.6);border:1px solid var(--border);
  border-radius:10px;padding:8px 6px;text-align:center;
  transition:all .3s;
}
.lv-item i{font-size:18px;display:block;margin-bottom:3px;color:var(--muted)}
.lv-item .lv-label{font-size:10px;color:var(--muted);display:block}
.lv-item .lv-val{font-size:13px;font-weight:700;color:var(--dim)}
.lv-item.pass{background:rgba(16,185,129,.15);border-color:rgba(16,185,129,.4)}
.lv-item.pass i,.lv-item.pass .lv-val{color:#34D399}
.lv-item.fail{background:rgba(239,68,68,.1);border-color:rgba(239,68,68,.3)}

/* ── Right panel ────────────────────────────────────── */
.side-panel{display:flex;flex-direction:column;overflow-y:auto;background:var(--card)}

/* Result card */
.result-zone{
  flex:0 0 auto;padding:20px;
  border-bottom:1px solid var(--border);
  min-height:200px;
  display:flex;align-items:center;justify-content:center;
}
.result-idle{text-align:center;color:var(--muted)}
.result-idle i{font-size:48px;display:block;margin-bottom:8px;opacity:.4}
.result-idle p{font-size:13px;line-height:1.6}

.result-card{
  width:100%;border-radius:16px;padding:22px;
  text-align:center;animation:popIn .4s cubic-bezier(.34,1.56,.64,1);
}
.result-card.success{background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(16,185,129,.05));border:1px solid rgba(16,185,129,.3)}
.result-card.error  {background:linear-gradient(135deg,rgba(239,68,68,.15),rgba(239,68,68,.05));border:1px solid rgba(239,68,68,.3)}
.result-card.warning{background:linear-gradient(135deg,rgba(245,158,11,.15),rgba(245,158,11,.05));border:1px solid rgba(245,158,11,.3)}
@keyframes popIn{from{opacity:0;transform:scale(.85)}to{opacity:1;transform:scale(1)}}

.result-emoji{font-size:52px;display:block;margin-bottom:10px}
.result-emp-photo{
  width:72px;height:72px;border-radius:50%;
  object-fit:cover;border:3px solid #10B981;
  margin:0 auto 10px;display:block;
}
.result-emp-initials{
  width:72px;height:72px;border-radius:50%;
  background:linear-gradient(135deg,var(--primary),#8B5CF6);
  color:#fff;font-size:26px;font-weight:800;
  display:flex;align-items:center;justify-content:center;
  margin:0 auto 10px;
}
.result-name{font-size:20px;font-weight:800;margin-bottom:2px}
.result-code{font-size:12px;color:var(--muted);margin-bottom:8px}
.result-msg {font-size:14px;font-weight:600;margin-bottom:8px}
.result-time{font-size:34px;font-weight:900;color:var(--primary);letter-spacing:1px;font-variant-numeric:tabular-nums}
.result-chips{display:flex;gap:6px;justify-content:center;flex-wrap:wrap;margin-top:8px}
.chip{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700}
.chip-ok  {background:rgba(16,185,129,.2);color:#34D399}
.chip-late{background:rgba(245,158,11,.2);color:#FCD34D}
.chip-out {background:rgba(99,102,241,.2);color:#A5B4FC}
.chip-conf{background:rgba(99,102,241,.15);color:#C4B5FD}
.result-extra{font-size:12px;color:var(--muted);margin-top:6px}
.progress-bar-wrap{background:var(--border);border-radius:100px;height:4px;margin-top:10px;overflow:hidden}
.progress-bar-fill{height:100%;background:#10B981;border-radius:100px;transition:width 1s linear}

/* Fraud alert */
.fraud-alert{
  background:rgba(239,68,68,.1);border:1px solid rgba(239,68,68,.3);
  border-radius:12px;padding:12px 16px;margin-top:8px;
  font-size:12px;color:#FCA5A5;text-align:left;
}

/* Activity feed */
.activity-section{flex:1;padding:16px;overflow-y:auto}
.activity-section h3{font-size:12px;font-weight:700;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;margin-bottom:12px;display:flex;align-items:center;gap:6px}
.act-item{display:flex;align-items:center;gap:10px;padding:9px 0;border-bottom:1px solid rgba(30,48,84,.5)}
.act-item:last-child{border-bottom:none}
.act-avatar{width:34px;height:34px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:700;flex-shrink:0}
.act-name{font-size:13px;font-weight:600;line-height:1.2}
.act-time{font-size:11px;color:var(--muted)}
.act-type{margin-left:auto;font-size:10px;font-weight:700;padding:2px 7px;border-radius:100px}
.act-in  {background:rgba(16,185,129,.2);color:#34D399}
.act-out {background:rgba(99,102,241,.2);color:#A5B4FC}

/* GPS badge */
.gps-row{
  padding:10px 16px;
  background:rgba(0,0,0,.3);
  border-top:1px solid var(--border);
  display:flex;align-items:center;gap:8px;
  font-size:12px;color:var(--muted);
}
.gps-ok  {color:#34D399}
.gps-err {color:#FCA5A5}

/* ── Offline banner ─────────────────────────────────── */
.offline-banner{
  display:none;
  background:rgba(245,158,11,.15);
  border-bottom:1px solid rgba(245,158,11,.3);
  color:#FCD34D;
  padding:6px 20px;
  font-size:12px;font-weight:600;
  text-align:center;
}

/* Countdown ring */
.countdown-wrap{display:flex;align-items:center;gap:8px;font-size:12px;color:var(--muted)}
svg.ring{transform:rotate(-90deg)}
.ring-bg{fill:none;stroke:var(--border);stroke-width:3}
.ring-fg{fill:none;stroke:var(--primary);stroke-width:3;stroke-linecap:round;stroke-dasharray:75.4;stroke-dashoffset:0;transition:stroke-dashoffset .1s linear}

@media(max-width:900px){.kiosk-body{grid-template-columns:1fr}.side-panel{display:none}}
</style>
</head>
<body>
<div class="kiosk">

<!-- ── Header ─────────────────────────────────────────── -->
<header class="kiosk-header">
  <div class="kiosk-brand">
    <i class="bi bi-shield-check-fill"></i>
    <div>
      <span class="name"><?= htmlspecialchars($companyName) ?></span>
      <span class="tag">Attendance Kiosk</span>
    </div>
  </div>
  <div class="kiosk-live">
    <div class="live-pill"><span class="live-dot"></span>LIVE</div>
    <div id="offlineIndicator" style="display:none;">
      <span style="background:rgba(245,158,11,.15);border:1px solid rgba(245,158,11,.3);color:#FCD34D;padding:4px 10px;border-radius:100px;font-size:11px;font-weight:600;">
        <i class="bi bi-wifi-off me-1"></i>OFFLINE
      </span>
    </div>
  </div>
  <div class="kiosk-clock-small">
    <div class="clock-t" id="kClock">--:--:--</div>
    <div class="clock-d" id="kDate"></div>
  </div>
</header>

<!-- ── Offline syncing banner ─────────────────────────── -->
<div class="offline-banner" id="offlineBanner">
  <i class="bi bi-wifi-off me-2"></i>Offline mode — attendance will sync automatically when internet is restored
</div>

<!-- ── Body ───────────────────────────────────────────── -->
<div class="kiosk-body">

  <!-- Camera pane -->
  <div class="cam-pane">
    <div class="cam-wrap">
      <video id="kioskVideo" autoplay muted playsinline></video>
      <canvas id="kioskCanvas"></canvas>
      <div class="face-oval" id="faceOval"></div>
      <div class="scan-line" id="scanLine"></div>

      <!-- Start button (shown before camera starts) -->
      <button class="btn-start-cam" id="startBtn">
        <i class="bi bi-camera-video-fill"></i>
        Start Face Recognition
      </button>
    </div>

    <!-- Liveness progress bar -->
    <div class="liveness-bar" id="livenessBar">
      <div class="lv-item" id="lv1">
        <i class="bi bi-eye"></i>
        <span class="lv-val" id="lv1v">0/2</span>
        <span class="lv-label">Blinks</span>
      </div>
      <div class="lv-item" id="lv2">
        <i class="bi bi-arrow-left-right"></i>
        <span class="lv-val" id="lv2v">No</span>
        <span class="lv-label">Head Turn</span>
      </div>
      <div class="lv-item" id="lv3">
        <i class="bi bi-person-bounding-box"></i>
        <span class="lv-val" id="lv3v">—</span>
        <span class="lv-label">Face Size</span>
      </div>
      <div class="lv-item" id="lv4">
        <i class="bi bi-shield-check"></i>
        <span class="lv-val" id="lv4v">Wait</span>
        <span class="lv-label">Liveness</span>
      </div>
    </div>

    <!-- Status bar -->
    <div class="cam-status">
      <span class="cam-status-text info" id="statusText">
        <i class="bi bi-camera-video me-2"></i>Click "Start Face Recognition" to begin
      </span>
      <div class="countdown-wrap" id="cdWrap" style="display:none">
        <svg class="ring" width="28" height="28" viewBox="0 0 28 28">
          <circle class="ring-bg" cx="14" cy="14" r="12"/>
          <circle class="ring-fg" id="cdRing" cx="14" cy="14" r="12"/>
        </svg>
        <span id="cdNum"></span>
      </div>
    </div>
  </div>

  <!-- Side panel -->
  <div class="side-panel">

    <!-- Result zone -->
    <div class="result-zone" id="resultZone">
      <div class="result-idle">
        <i class="bi bi-person-bounding-box"></i>
        <p>Face recognition ready.<br>Stand in front of the camera<br>and blink twice to begin.</p>
      </div>
    </div>

    <!-- GPS badge -->
    <div class="gps-row" id="gpsRow">
      <i class="bi bi-geo-alt" id="gpsIco"></i>
      <span id="gpsTxt">Acquiring GPS location…</span>
    </div>

    <!-- Activity feed -->
    <div class="activity-section">
      <h3><i class="bi bi-activity"></i>Recent Activity</h3>
      <div id="actFeed"><div style="color:var(--muted);font-size:13px;text-align:center;padding:20px">Loading…</div></div>
    </div>

  </div>
</div>
</div>

<script>window.ASSET_URL = '<?= ASSET_URL ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="<?= ASSET_URL ?>/js/face-recognition.js"></script>
<script>
'use strict';
const CSRF    = <?= json_encode($csrfToken) ?>;
const BASE    = <?= json_encode(BASE_URL) ?>;
const COMPANY = <?= json_encode($companyName) ?>;

let cameraStarted    = false;
let lastResultTime   = 0;
let autoResetTimer   = null;
let isOnline         = navigator.onLine;
let syncInterval     = null;

// ── Clock ─────────────────────────────────────────────
function tick() {
  const n = new Date();
  document.getElementById('kClock').textContent = n.toLocaleTimeString('en-US', {hour12:false});
  document.getElementById('kDate').textContent  = n.toLocaleDateString('en-US', {weekday:'long', month:'long', day:'numeric', year:'numeric'});
}
tick(); setInterval(tick, 1000);

// ── Online/Offline ────────────────────────────────────
function updateOnlineStatus() {
  isOnline = navigator.onLine;
  document.getElementById('offlineBanner').style.display  = isOnline ? 'none' : '';
  document.getElementById('offlineIndicator').style.display = isOnline ? 'none' : '';
  if (isOnline) trySyncOffline();
}
window.addEventListener('online',  updateOnlineStatus);
window.addEventListener('offline', updateOnlineStatus);
updateOnlineStatus();

async function trySyncOffline() {
  const pending = await FaceRecognition.getPendingScans();
  if (!pending.length) return;
  setStatus(`Syncing ${pending.length} offline record(s)…`, 'info');
  const result = await FaceRecognition.syncOfflineScans(BASE + '/attendance/kiosk/scan', CSRF);
  if (result.synced > 0) showToastMsg(`✅ ${result.synced} offline attendance records synced`, 'success');
}
setInterval(trySyncOffline, 30000);

// ── GPS ───────────────────────────────────────────────
let geoLat = null, geoLng = null;
if (navigator.geolocation) {
  navigator.geolocation.watchPosition(
    p => {
      geoLat = p.coords.latitude; geoLng = p.coords.longitude;
      const ico = document.getElementById('gpsIco');
      ico.className = 'bi bi-geo-alt-fill gps-ok';
      document.getElementById('gpsTxt').innerHTML =
        `<span class="gps-ok">GPS: ${geoLat.toFixed(5)}, ${geoLng.toFixed(5)}</span>`;
    },
    () => {
      document.getElementById('gpsIco').className = 'bi bi-geo-alt gps-err';
      document.getElementById('gpsTxt').innerHTML = '<span class="gps-err">GPS unavailable</span>';
    },
    { enableHighAccuracy:true, timeout:10000 }
  );
}

// ── Liveness UI ───────────────────────────────────────
window.updateLivenessUI = function(state, faceCount) {
  const b1 = state.blinkCount >= 2;
  const b2 = state.headMovements >= 1;
  const b3 = state.faceSizeOk;
  const b4 = FaceRecognition.livenessReady();

  document.getElementById('lv1v').textContent = `${Math.min(state.blinkCount,2)}/2`;
  document.getElementById('lv2v').textContent = b2 ? 'Yes ✓' : 'No';
  document.getElementById('lv3v').textContent = b3 ? 'OK ✓' : '—';
  document.getElementById('lv4v').textContent = b4 ? 'PASS ✓' : 'Wait';

  ['lv1','lv2','lv3','lv4'].forEach((id,i) =>
    document.getElementById(id).className = 'lv-item' + ([b1,b2,b3,b4][i] ? ' pass' : ''));

  // Face oval state
  const oval = document.getElementById('faceOval');
  if (faceCount === 0)      oval.className = 'face-oval';
  else if (faceCount > 1)   oval.className = 'face-oval rejected';
  else if (b4)              oval.className = 'face-oval liveness';
  else                      oval.className = 'face-oval detected';

  document.getElementById('scanLine').className = 'scan-line' + (faceCount === 1 ? ' active' : '');
};

// ── Status text ───────────────────────────────────────
function setStatus(msg, type) {
  const el = document.getElementById('statusText');
  el.className = 'cam-status-text ' + (type || 'info');
  el.innerHTML = msg;
}
FaceRecognition.onStatus(setStatus);

// ── Start camera ──────────────────────────────────────
document.getElementById('startBtn').addEventListener('click', async function() {
  const btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Loading AI…';

  // Inline spinner style
  if (!document.getElementById('spinStyle')) {
    const s = document.createElement('style');
    s.id = 'spinStyle';
    s.textContent = '.spinner{display:inline-block;width:16px;height:16px;border:2px solid rgba(255,255,255,.3);border-top-color:#fff;border-radius:50%;animation:spin .7s linear infinite;margin-right:6px}@keyframes spin{to{transform:rotate(360deg)}}';
    document.head.appendChild(s);
  }

  try {
    await FaceRecognition.loadModels();
    await FaceRecognition.startCamera(document.getElementById('kioskVideo'));
    FaceRecognition.setCanvas(document.getElementById('kioskCanvas'));
    btn.style.display = 'none';
    cameraStarted = true;

    FaceRecognition.startDetectionLoop(handleFaceDetected, { requireAntiSpoof: true });
    loadActivity();
    setInterval(loadActivity, 20000);

  } catch(err) {
    setStatus('❌ ' + err.message, 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-camera-video-fill"></i> Start Face Recognition';
  }
});

// ── Face detected callback ────────────────────────────
async function handleFaceDetected(descriptor, spoofData) {
  const now = Date.now();
  if (now - lastResultTime < 8000) return; // cooldown
  lastResultTime = now;
  FaceRecognition.markScanned();

  setStatus('<i class="bi bi-cpu me-2"></i>Identifying employee…', 'info');

  // Capture snapshot for fraud logging
  const snapshot = FaceRecognition.captureImage(0.7);

  const fd = new FormData();
  fd.append('_csrf_token', CSRF);
  fd.append('descriptor',  JSON.stringify(descriptor));
  fd.append('anti_spoof',  JSON.stringify(spoofData));
  fd.append('action',      'auto');
  fd.append('snapshot',    snapshot || '');
  if (geoLat) fd.append('latitude',  geoLat);
  if (geoLng) fd.append('longitude', geoLng);

  try {
    let data;
    if (!isOnline) {
      // Queue for later sync
      await FaceRecognition.queueOfflineScan({
        descriptor: JSON.stringify(descriptor),
        anti_spoof: JSON.stringify(spoofData),
        action: 'auto',
        latitude: geoLat, longitude: geoLng,
        queued_at: new Date().toISOString(),
      });
      showResult({ success:true, offline:true, message:'Attendance queued (offline). Will sync when internet is restored.' });
      return;
    }

    const resp = await fetch(BASE + '/attendance/kiosk/scan', { method:'POST', body:fd });
    data = await resp.json();
    showResult(data);

    if (data.success || data.already_done) {
      FaceRecognition.resetLiveness();
      autoResetResult();
    } else {
      // Unknown face — reset liveness after 4s so they can retry
      setTimeout(() => { FaceRecognition.resetLiveness(); lastResultTime = 0; }, 4000);
    }

  } catch(err) {
    if (!isOnline) {
      await FaceRecognition.queueOfflineScan({ descriptor: JSON.stringify(descriptor), action:'auto' });
      showResult({ success:true, offline:true, message:'No internet — attendance queued for sync.' });
    } else {
      showResult({ success:false, message:'Connection error: ' + err.message });
      setTimeout(() => { FaceRecognition.resetLiveness(); lastResultTime = 0; }, 4000);
    }
  }
}

// ── Show result card ──────────────────────────────────
function showResult(data) {
  const zone = document.getElementById('resultZone');
  clearTimeout(autoResetTimer);

  if (data.offline) {
    zone.innerHTML = `
      <div class="result-card warning" style="width:100%">
        <span class="result-emoji">📶</span>
        <div class="result-msg" style="color:#FCD34D">Attendance queued — Offline Mode</div>
        <p style="font-size:12px;color:var(--muted);margin-top:8px">${data.message}</p>
      </div>`;
    autoResetResult(8000);
    return;
  }

  if (data.already_done) {
    const emp = data.employee;
    zone.innerHTML = buildEmpHeader(emp) + `
      <div class="result-card warning" style="width:100%">
        ${buildEmpPhoto(emp)}
        <div class="result-name">${emp?.full_name || 'Employee'}</div>
        <div class="result-code">${emp?.employee_code || ''}</div>
        <div class="result-msg">Attendance already completed today.</div>
        <div class="result-chips"><span class="chip chip-ok">✓ Check-In</span><span class="chip chip-out">✓ Check-Out</span></div>
      </div>`;
    autoResetResult(6000);
    return;
  }

  if (!data.success) {
    const isUnknown = data.message?.toLowerCase().includes('not recognized') || data.message?.toLowerCase().includes('not enrolled');
    zone.innerHTML = `
      <div class="result-card error" style="width:100%">
        <span class="result-emoji">${isUnknown ? '🚫' : '⚠️'}</span>
        <div class="result-msg" style="color:#FCA5A5">${data.message || 'Recognition failed'}</div>
        ${isUnknown ? `<div class="fraud-alert"><i class="bi bi-exclamation-triangle me-1"></i>Unknown face detected and logged for security review.</div>` : ''}
        <p style="font-size:12px;color:var(--muted);margin-top:8px">Please try again or contact HR if you are enrolled.</p>
      </div>`;
    setStatus('❌ ' + (data.message || 'Not recognized'), 'error');
    autoResetResult(5000);
    return;
  }

  // ✅ SUCCESS
  const emp     = data.employee || {};
  const isIn    = data.action === 'check_in';
  const time    = new Date(isIn ? data.check_in_time : data.check_out_time).toLocaleTimeString('en-US', {hour:'2-digit',minute:'2-digit',hour12:false});
  const isLate  = data.status === 'late';
  const conf    = Math.round(data.confidence || data.match_score || 0);

  let chips = '';
  chips += isIn
    ? `<span class="chip ${isLate ? 'chip-late' : 'chip-ok'}">${isLate ? '⚠️ LATE' : '✅ ON TIME'}</span>`
    : `<span class="chip chip-out">👋 CHECKED OUT</span>`;
  if (conf >= 90) chips += `<span class="chip chip-conf"><i class="bi bi-shield-check"></i> ${conf}% match</span>`;
  if (isLate && data.late_minutes) chips += `<span class="chip chip-late">${data.late_minutes}m late</span>`;

  zone.innerHTML = `
    <div class="result-card success" style="width:100%">
      ${buildEmpPhoto(emp)}
      <div class="result-name">${emp.full_name || 'Employee'}</div>
      <div class="result-code">${emp.employee_code || ''} ${emp.department ? '· ' + emp.department : ''}</div>
      <div class="result-msg">${data.message || (isIn ? 'Check-In Successful' : 'Check-Out Successful')}</div>
      <div class="result-time">${time}</div>
      <div class="result-chips">${chips}</div>
      ${!isIn && data.working_hours ? `<div class="result-extra">⏱ Worked ${data.working_hours}h${data.overtime_hours > 0 ? ` · OT ${data.overtime_hours}h` : ''}</div>` : ''}
      <div class="progress-bar-wrap"><div class="progress-bar-fill" id="resetBar" style="width:100%"></div></div>
    </div>`;

  setStatus(`✅ Welcome ${emp.full_name || 'Employee'} — ${isIn ? 'Check-In' : 'Check-Out'} recorded`, 'success');
  document.getElementById('faceOval').className = 'face-oval matched';
  loadActivity();
  startResetBar(7000);
  autoResetResult(7000);
}

function buildEmpPhoto(emp) {
  if (emp?.photo) return `<img class="result-emp-photo" src="${BASE}/uploads/${emp.photo}" alt="" onerror="this.style.display='none'">`;
  const ini = ((emp?.full_name||'??').split(' ').map(w=>w[0]).join('').substring(0,2)).toUpperCase();
  return `<div class="result-emp-initials">${ini}</div>`;
}
function buildEmpHeader(emp) { return ''; }

function autoResetResult(ms) {
  clearTimeout(autoResetTimer);
  autoResetTimer = setTimeout(() => {
    document.getElementById('resultZone').innerHTML = `
      <div class="result-idle">
        <i class="bi bi-person-bounding-box"></i>
        <p>Face recognition ready.<br>Stand in front of the camera<br>and blink twice to begin.</p>
      </div>`;
    document.getElementById('faceOval').className = 'face-oval';
    setStatus('Ready — position your face and blink twice', 'info');
    lastResultTime = 0;
    FaceRecognition.resetLiveness();
  }, ms || 7000);
}

function startResetBar(ms) {
  const bar   = document.getElementById('resetBar');
  if (!bar) return;
  const start = Date.now();
  const timer = setInterval(() => {
    const pct = Math.max(0, 100 - ((Date.now()-start)/ms)*100);
    if (bar) bar.style.width = pct + '%';
    if (pct <= 0) clearInterval(timer);
  }, 100);
}

// ── Activity feed ─────────────────────────────────────
async function loadActivity() {
  try {
    const resp = await fetch(BASE + '/api/attendance/today');
    if (!resp.ok) return;
    const data = await resp.json();
    const records = (data.data || []).slice(0, 8);
    const el = document.getElementById('actFeed');
    if (!records.length) { el.innerHTML = '<div style="color:var(--muted);font-size:13px;text-align:center;padding:16px">No activity yet today</div>'; return; }
    el.innerHTML = records.map(r => {
      const ini = (((r.first_name||'?')[0]) + ((r.last_name||'?')[0])).toUpperCase();
      const isOut = !!r.check_out;
      const t   = isOut
        ? new Date(r.check_out).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:false})
        : new Date(r.check_in).toLocaleTimeString('en-US',{hour:'2-digit',minute:'2-digit',hour12:false});
      return `<div class="act-item">
        <div class="act-avatar">${ini}</div>
        <div><div class="act-name">${r.first_name} ${r.last_name}</div><div class="act-time">${t}</div></div>
        <span class="act-type ${isOut ? 'act-out' : 'act-in'}">${isOut ? 'OUT' : 'IN'}</span>
      </div>`;
    }).join('');
  } catch(e) {}
}

// ── Toast ─────────────────────────────────────────────
function showToastMsg(msg, type) {
  const t = document.createElement('div');
  t.style.cssText = `position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:${type==='success'?'#10B981':'#EF4444'};color:#fff;padding:10px 20px;border-radius:10px;font-weight:600;font-size:14px;z-index:9999;animation:fadeIn .3s`;
  t.textContent = msg;
  document.body.appendChild(t);
  setTimeout(() => t.remove(), 4000);
}

window.addEventListener('beforeunload', () => FaceRecognition.stopCamera());
</script>
</body>
</html>
