<?php
/**
 * Employee Self-Registration Wizard — Standalone page
 * 6-step guided registration with face enrollment
 */
$csrfToken = Session::getInstance()->csrfToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
<title>Employee Registration — <?= APP_NAME ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
*{box-sizing:border-box;margin:0;padding:0}
:root{
  --primary:#16A34A;--primary-dark:#15803D;--primary-light:#D1FAE5;
  --success:#10B981;--danger:#EF4444;--warn:#F59E0B;
  --bg:#F0FDF4;--card:#fff;--border:#D1FAE5;
  --text:#111827;--muted:#6B7280;--dim:#9CA3AF;
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh}

/* ── Top bar ─────────────────────────────────────────── */
.reg-topbar{background:#fff;border-bottom:1px solid var(--border);padding:14px 24px;display:flex;align-items:center;justify-content:space-between}
.reg-brand{display:flex;align-items:center;gap:10px;text-decoration:none;color:var(--primary)}
.reg-brand i{font-size:26px}
.reg-brand span{font-size:18px;font-weight:800;color:var(--text)}
.reg-login-link{font-size:13px;color:var(--muted)}
.reg-login-link a{color:var(--primary);font-weight:600;text-decoration:none}

/* ── Progress stepper ────────────────────────────────── */
.reg-stepper{background:#fff;border-bottom:1px solid var(--border);padding:0 24px}
.stepper-inner{max-width:900px;margin:0 auto;display:flex;align-items:center;padding:16px 0}
.step-item{flex:1;display:flex;flex-direction:column;align-items:center;gap:4px;position:relative}
.step-item:not(:last-child)::after{content:'';position:absolute;top:16px;left:calc(50% + 20px);right:calc(-50% + 20px);height:2px;background:#E5E7EB;z-index:0;transition:background .3s}
.step-item.done::after{background:var(--primary)}
.step-num{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;border:2px solid #E5E7EB;background:#fff;color:var(--muted);transition:all .3s;z-index:1;position:relative}
.step-item.active .step-num{border-color:var(--primary);background:var(--primary);color:#fff;box-shadow:0 0 0 4px rgba(22,163,74,.15)}
.step-item.done   .step-num{border-color:var(--primary);background:var(--primary);color:#fff}
.step-item.done   .step-num::before{content:'✓'}
.step-label{font-size:11px;font-weight:600;color:var(--muted);text-align:center;white-space:nowrap}
.step-item.active .step-label,.step-item.done .step-label{color:var(--primary)}

/* ── Main layout ─────────────────────────────────────── */
.reg-main{max-width:900px;margin:0 auto;padding:32px 16px}

/* ── Cards ───────────────────────────────────────────── */
.step-card{background:#fff;border:1px solid var(--border);border-radius:20px;padding:32px;display:none}
.step-card.active{display:block;animation:fadeSlide .3s ease}
@keyframes fadeSlide{from{opacity:0;transform:translateY(8px)}to{opacity:1;transform:translateY(0)}}

.step-card-title{font-size:22px;font-weight:800;margin-bottom:6px}
.step-card-sub{font-size:14px;color:var(--muted);margin-bottom:28px}

/* ── Form ────────────────────────────────────────────── */
.form-label{font-size:13px;font-weight:600;margin-bottom:5px;color:var(--text)}
.form-label .req{color:var(--danger);margin-left:2px}
.form-control,.form-select{
  border:1.5px solid #E5E7EB;border-radius:10px;
  padding:10px 14px;font-size:14px;transition:border-color .2s;
  background:#fff;
}
.form-control:focus,.form-select:focus{border-color:var(--primary);box-shadow:0 0 0 3px rgba(22,163,74,.1);outline:none}
.form-control.is-valid  {border-color:var(--success)}
.form-control.is-invalid{border-color:var(--danger)}
.field-hint{font-size:11px;color:var(--muted);margin-top:4px}
.field-error{font-size:11px;color:var(--danger);margin-top:4px;display:none}
.section-title{font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:var(--primary);margin:20px 0 12px;padding-bottom:6px;border-bottom:2px solid var(--primary-light)}

/* ── Password strength ───────────────────────────────── */
.pwd-bar{height:4px;border-radius:2px;background:#E5E7EB;margin-top:6px;overflow:hidden}
.pwd-fill{height:100%;width:0;transition:all .3s;border-radius:2px}

/* ── Face enrollment ─────────────────────────────────── */
.face-wrap{position:relative;background:#000;border-radius:16px;overflow:hidden;aspect-ratio:4/3;max-height:360px}
#regVideo{width:100%;height:100%;object-fit:cover;display:block}
#regCanvas{position:absolute;inset:0;width:100%;height:100%;pointer-events:none}
.face-guide{position:absolute;top:50%;left:50%;transform:translate(-50%,-55%);width:160px;height:200px;border:3px solid rgba(22,163,74,.5);border-radius:50%;pointer-events:none;transition:all .3s}
.face-guide.detected{border-color:#16A34A;box-shadow:0 0 20px rgba(22,163,74,.4)}
.face-guide.bad{border-color:#EF4444;box-shadow:0 0 20px rgba(239,68,68,.4)}
.face-overlay-status{position:absolute;bottom:12px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.75);backdrop-filter:blur(6px);color:#fff;padding:6px 16px;border-radius:100px;font-size:13px;font-weight:600;white-space:nowrap}

.angle-grid{display:grid;grid-template-columns:repeat(5,1fr);gap:8px;margin:16px 0}
.angle-item{border:2px solid #E5E7EB;border-radius:10px;padding:10px 6px;text-align:center;font-size:11px;color:var(--muted);transition:all .3s;cursor:default}
.angle-item i{font-size:20px;display:block;margin-bottom:4px}
.angle-item.done{border-color:var(--primary);background:var(--primary-light);color:var(--primary)}
.angle-item.active-angle{border-color:var(--warn);background:rgba(245,158,11,.1);color:#B45309;animation:pulse .8s ease infinite alternate}
@keyframes pulse{to{box-shadow:0 0 12px rgba(245,158,11,.4)}}

.face-qual-bar{height:6px;border-radius:3px;background:#E5E7EB;overflow:hidden;margin-top:4px}
.face-qual-fill{height:100%;background:var(--primary);border-radius:3px;transition:width .3s}

/* ── Review step ─────────────────────────────────────── */
.review-section{background:#F9FFF9;border:1px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:16px}
.review-section h4{font-size:13px;font-weight:700;color:var(--primary);margin-bottom:10px;text-transform:uppercase;letter-spacing:.5px}
.review-row{display:flex;gap:8px;font-size:13px;margin-bottom:6px}
.review-label{color:var(--muted);min-width:140px;flex-shrink:0}
.review-value{font-weight:600}

/* ── Buttons ─────────────────────────────────────────── */
.btn-reg-next{background:linear-gradient(135deg,var(--primary),var(--primary-dark));color:#fff;border:none;border-radius:12px;padding:12px 28px;font-size:15px;font-weight:700;cursor:pointer;transition:all .2s;display:flex;align-items:center;gap:8px}
.btn-reg-next:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(22,163,74,.4)}
.btn-reg-next:disabled{opacity:.6;cursor:not-allowed;transform:none}
.btn-reg-back{background:transparent;border:1.5px solid #E5E7EB;border-radius:12px;padding:12px 24px;font-size:14px;font-weight:600;cursor:pointer;color:var(--muted);transition:all .2s}
.btn-reg-back:hover{border-color:var(--primary);color:var(--primary)}
.btn-nav{display:flex;justify-content:space-between;align-items:center;margin-top:28px;padding-top:20px;border-top:1px solid #F3F4F6}

/* ── Success / Status ─────────────────────────────────── */
.success-icon{width:80px;height:80px;border-radius:50%;background:var(--primary-light);color:var(--primary);font-size:36px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px}
.status-timeline{border-left:2px solid var(--primary-light);padding-left:20px}
.status-event{position:relative;margin-bottom:14px;font-size:13px}
.status-event::before{content:'';position:absolute;left:-26px;top:4px;width:10px;height:10px;border-radius:50%;background:var(--primary);border:2px solid #fff;box-shadow:0 0 0 2px var(--primary)}

/* ── Responsive ─────────────────────────────────────────*/
@media(max-width:600px){
  .step-label{display:none}
  .reg-main{padding:16px 12px}
  .step-card{padding:20px 16px}
  .angle-grid{grid-template-columns:repeat(5,1fr);gap:4px}
}
</style>
</head>
<body>

<!-- Top bar -->
<div class="reg-topbar">
  <a href="<?= BASE_URL ?>/" class="reg-brand">
    <i class="bi bi-shield-check-fill"></i>
    <span><?= htmlspecialchars(APP_NAME) ?></span>
  </a>
  <div class="reg-login-link">
    Already have an account? <a href="<?= BASE_URL ?>/login">Sign In</a>
  </div>
</div>

<!-- Stepper -->
<div class="reg-stepper">
  <div class="stepper-inner">
    <?php
    $steps = [
      ['Personal Info','bi-person'],
      ['Account','bi-lock'],
      ['Face Enrollment','bi-camera'],
      ['Review','bi-eye'],
      ['Submit','bi-check2-circle'],
    ];
    foreach ($steps as $i => [$label, $icon]): $n = $i+1; ?>
    <div class="step-item <?= $n === 1 ? 'active' : '' ?>" id="stepper<?= $n ?>">
      <div class="step-num"><?= $n ?></div>
      <div class="step-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Main -->
<div class="reg-main">

<!-- ═══ STEP 1: Personal Information ═══ -->
<div class="step-card active" id="step1">
  <div class="step-card-title">Personal Information</div>
  <div class="step-card-sub">Tell us about yourself. Fields marked <span style="color:#EF4444">*</span> are required.</div>

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">First Name <span class="req">*</span></label>
      <input type="text" id="first_name" class="form-control" placeholder="John" autocomplete="given-name">
      <div class="field-error" id="err_first_name"></div>
    </div>
    <div class="col-md-6">
      <label class="form-label">Last Name <span class="req">*</span></label>
      <input type="text" id="last_name" class="form-control" placeholder="Smith" autocomplete="family-name">
      <div class="field-error" id="err_last_name"></div>
    </div>
    <div class="col-md-6">
      <label class="form-label">National ID / Passport <span class="req">*</span></label>
      <input type="text" id="national_id" class="form-control" placeholder="ID or Passport number">
      <div class="field-error" id="err_national_id"></div>
    </div>
    <div class="col-md-6">
      <label class="form-label">Date of Birth</label>
      <input type="date" id="date_of_birth" class="form-control">
    </div>
    <div class="col-md-6">
      <label class="form-label">Gender</label>
      <select id="gender" class="form-select">
        <option value="">Select gender</option>
        <option>male</option><option>female</option><option>other</option>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Phone Number</label>
      <input type="tel" id="phone" class="form-control" placeholder="+1 555 123 4567">
      <div class="field-error" id="err_phone"></div>
    </div>

    <div class="col-12"><div class="section-title">Employment</div></div>

    <div class="col-md-6">
      <label class="form-label">Position / Job Title <span class="req">*</span></label>
      <input type="text" id="position" class="form-control" placeholder="e.g. Software Engineer">
    </div>
    <div class="col-md-6">
      <label class="form-label">Department</label>
      <select id="department_id" class="form-select">
        <option value="">Select department</option>
        <?php foreach ($departments as $d): ?>
        <option value="<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Branch / Office</label>
      <select id="branch_id" class="form-select">
        <option value="">Select branch</option>
        <?php foreach ($branches as $b): ?>
        <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['name']) ?><?= $b['city'] ? ' — '.$b['city'] : '' ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-6">
      <label class="form-label">Employment Type</label>
      <select id="employment_type" class="form-select">
        <option value="full_time">Full Time</option>
        <option value="part_time">Part Time</option>
        <option value="remote">Remote</option>
        <option value="hybrid">Hybrid</option>
      </select>
    </div>
    <div class="col-12">
      <label class="form-label">Address</label>
      <textarea id="address" class="form-control" rows="2" placeholder="Street address, city, country"></textarea>
    </div>

    <div class="col-12"><div class="section-title">Emergency Contact</div></div>

    <div class="col-md-4">
      <label class="form-label">Contact Name</label>
      <input type="text" id="emergency_contact_name" class="form-control" placeholder="Jane Smith">
    </div>
    <div class="col-md-4">
      <label class="form-label">Contact Phone</label>
      <input type="tel" id="emergency_contact_phone" class="form-control" placeholder="+1 555 000 0000">
    </div>
    <div class="col-md-4">
      <label class="form-label">Relationship</label>
      <select id="emergency_contact_relation" class="form-select">
        <option value="">Select</option>
        <option>Spouse</option><option>Parent</option><option>Sibling</option>
        <option>Child</option><option>Friend</option><option>Other</option>
      </select>
    </div>
  </div>

  <div class="btn-nav">
    <div></div>
    <button class="btn-reg-next" onclick="goStep(2)">
      Next: Account Setup <i class="bi bi-arrow-right"></i>
    </button>
  </div>
</div>

<!-- ═══ STEP 2: Account Information ═══ -->
<div class="step-card" id="step2">
  <div class="step-card-title">Account Information</div>
  <div class="step-card-sub">Set up your login credentials for the attendance system.</div>

  <div class="row g-3">
    <div class="col-md-6">
      <label class="form-label">Email Address <span class="req">*</span></label>
      <input type="email" id="email" class="form-control" placeholder="you@company.com" autocomplete="email">
      <div class="field-error" id="err_email"></div>
      <div class="field-hint">This will be your login username</div>
    </div>
    <div class="col-md-6">
      <label class="form-label">Employee Code <span style="font-weight:400;color:var(--muted)">(optional — auto-generated if blank)</span></label>
      <input type="text" id="employee_code" class="form-control" placeholder="e.g. EMP2024001">
      <div class="field-error" id="err_employee_code"></div>
    </div>
    <div class="col-md-6">
      <label class="form-label">Password <span class="req">*</span></label>
      <div class="position-relative">
        <input type="password" id="password" class="form-control" placeholder="Minimum 8 characters" autocomplete="new-password">
        <button type="button" onclick="togglePwd('password',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;padding:4px">
          <i class="bi bi-eye"></i>
        </button>
      </div>
      <div class="pwd-bar"><div class="pwd-fill" id="pwdFill"></div></div>
      <div class="field-hint" id="pwdStrengthLabel">Enter a strong password</div>
    </div>
    <div class="col-md-6">
      <label class="form-label">Confirm Password <span class="req">*</span></label>
      <div class="position-relative">
        <input type="password" id="confirm_password" class="form-control" placeholder="Repeat password" autocomplete="new-password">
        <button type="button" onclick="togglePwd('confirm_password',this)" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;color:var(--muted);cursor:pointer;padding:4px">
          <i class="bi bi-eye"></i>
        </button>
      </div>
      <div class="field-error" id="err_confirm_password"></div>
    </div>
  </div>

  <div class="btn-nav">
    <button class="btn-reg-back" onclick="goStep(1)"><i class="bi bi-arrow-left"></i> Back</button>
    <button class="btn-reg-next" onclick="goStep(3)">
      Next: Face Enrollment <i class="bi bi-arrow-right"></i>
    </button>
  </div>
</div>

<!-- ═══ STEP 3: Face Enrollment ═══ -->
<div class="step-card" id="step3">
  <div class="step-card-title">Face Enrollment</div>
  <div class="step-card-sub">Capture your face for biometric attendance. You need to capture 3 angles minimum.</div>

  <div class="row g-4">
    <div class="col-lg-7">
      <div class="face-wrap">
        <video id="regVideo" autoplay muted playsinline></video>
        <canvas id="regCanvas"></canvas>
        <div class="face-guide" id="faceGuide"></div>
        <div class="face-overlay-status" id="faceStatusText">
          <i class="bi bi-camera-video-off me-1"></i>Click Start Camera
        </div>
      </div>

      <div class="d-flex gap-2 mt-3">
        <button class="btn btn-success flex-fill" id="startFaceBtn" onclick="startFaceCamera()">
          <i class="bi bi-camera-video me-1"></i>Start Camera
        </button>
        <button class="btn btn-primary" id="captureBtn" onclick="captureAngle()" disabled>
          <i class="bi bi-camera me-1"></i>Capture
        </button>
        <button class="btn btn-light" onclick="retakeAll()" title="Restart enrollment">
          <i class="bi bi-arrow-clockwise"></i>
        </button>
      </div>

      <!-- Face quality indicator -->
      <div class="mt-2">
        <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--muted)">
          <span>Face Quality</span><span id="qualityPct">—</span>
        </div>
        <div class="face-qual-bar"><div class="face-qual-fill" id="qualFill" style="width:0"></div></div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="mb-3" style="font-size:13px;font-weight:700;color:var(--text)">Required Angles</div>
      <div class="angle-grid">
        <?php foreach ([
          ['front', 'Front', 'bi-person-circle'],
          ['left',  'Left',  'bi-arrow-left'],
          ['right', 'Right', 'bi-arrow-right'],
          ['up',    'Up',    'bi-arrow-up'],
          ['down',  'Down',  'bi-arrow-down'],
        ] as [$val, $label, $icon]): ?>
        <div class="angle-item" id="angle_<?= $val ?>">
          <i class="bi <?= $icon ?>"></i><?= $label ?>
        </div>
        <?php endforeach; ?>
      </div>

      <div id="currentAngleHint" class="mt-3 p-3" style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:10px;font-size:13px;">
        <i class="bi bi-info-circle text-warning me-1"></i>
        Start camera and capture each angle one by one.
      </div>

      <div id="enrolledList" class="mt-3"></div>

      <div id="faceSkipNote" class="mt-3 p-3" style="background:#F0FDF4;border:1px solid var(--border);border-radius:10px;font-size:13px;display:none">
        <i class="bi bi-shield-check text-success me-1"></i>
        Face enrollment is optional. You can complete it at HR later. Without it, you cannot use face recognition kiosk.
      </div>
    </div>
  </div>

  <div class="btn-nav">
    <button class="btn-reg-back" onclick="goStep(2)"><i class="bi bi-arrow-left"></i> Back</button>
    <div class="d-flex gap-2">
      <button class="btn-reg-back" onclick="skipFace()" id="skipFaceBtn">Skip for now</button>
      <button class="btn-reg-next" id="faceNextBtn" onclick="goStep(4)" disabled>
        Next: Review <i class="bi bi-arrow-right"></i>
      </button>
    </div>
  </div>
</div>

<!-- ═══ STEP 4: Review ═══ -->
<div class="step-card" id="step4">
  <div class="step-card-title">Review Your Information</div>
  <div class="step-card-sub">Please verify your details before submitting.</div>

  <div id="reviewContent"><!-- filled by JS --></div>

  <div class="btn-nav">
    <button class="btn-reg-back" onclick="goStep(3)"><i class="bi bi-arrow-left"></i> Back</button>
    <button class="btn-reg-next" id="submitBtn" onclick="submitRegistration()">
      <i class="bi bi-check2-circle me-1"></i>Submit Registration
    </button>
  </div>
</div>

<!-- ═══ STEP 5: Pending ═══ -->
<div class="step-card text-center" id="step5">
  <div class="success-icon"><i class="bi bi-clock-history"></i></div>
  <div class="step-card-title" style="text-align:center">Registration Submitted!</div>
  <p class="step-card-sub" style="text-align:center">
    Your registration has been submitted successfully and is awaiting administrator approval.
  </p>

  <div class="alert alert-success text-start">
    <div class="fw-600 mb-2"><i class="bi bi-check-circle-fill me-2"></i>What happens next?</div>
    <ol style="margin:0;padding-left:18px;font-size:13px;">
      <li class="mb-1">HR will review your registration and face enrollment within 1-2 business days</li>
      <li class="mb-1">If approved, your Employee ID and QR code will be generated automatically</li>
      <li class="mb-1">You will receive a notification when your account is activated</li>
      <li>You can check your status using the link below</li>
    </ol>
  </div>

  <div id="statusLinkBox" class="mt-3 p-3" style="background:#F0FDF4;border:1px solid var(--border);border-radius:12px;text-align:left">
    <div style="font-size:13px;font-weight:600;margin-bottom:6px;color:var(--muted)">Track your registration status:</div>
    <a id="statusLink" href="#" style="font-size:13px;color:var(--primary);word-break:break-all;font-weight:600"></a>
    <button onclick="copyStatusLink()" class="btn btn-sm btn-light mt-2" style="display:block">
      <i class="bi bi-copy me-1"></i>Copy Link
    </button>
  </div>

  <div class="mt-3">
    <a href="<?= BASE_URL ?>/login" class="btn btn-success">
      <i class="bi bi-box-arrow-in-right me-1"></i>Go to Login
    </a>
  </div>
</div>

</div><!-- /.reg-main -->

<script>window.ASSET_URL = '<?= ASSET_URL ?>';</script>
<script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
<script src="<?= ASSET_URL ?>/js/face-recognition.js"></script>
<script>
'use strict';

const CSRF     = '<?= htmlspecialchars($csrfToken) ?>';
const BASE     = '<?= BASE_URL ?>';
const CSRF_KEY = '<?= CSRF_TOKEN_NAME ?>';

// ── State ────────────────────────────────────────────────
let currentStep = 1;
const ANGLES    = ['front','left','right','up','down'];
const ANGLE_LABELS = {front:'Look straight ahead',left:'Turn head LEFT',right:'Turn head RIGHT',up:'Tilt head UP',down:'Tilt head DOWN'};
let capturedFaces  = {}; // angle → {descriptor, image_data, quality}
let videoStream    = null;
let detectLoop     = null;
let currentAngleIdx= 0;
let faceDetected   = false;
let faceSkipped    = false;
let lastQuality    = 0;

// ── Navigation ────────────────────────────────────────────
async function goStep(n) {
  if (n > currentStep) {
    const ok = await validateStep(currentStep);
    if (!ok) return;
  }
  // Update stepper
  for (let i=1; i<=5; i++) {
    const el = document.getElementById('stepper'+i);
    if (!el) continue;
    el.className = 'step-item';
    if (i < n)  el.classList.add('done');
    if (i === n) el.classList.add('active');
  }
  // Update connector lines
  document.querySelectorAll('.step-item:not(:last-child)').forEach((el,i) => {
    el.style.setProperty('--line-color', i+1 < n ? 'var(--primary)' : '#E5E7EB');
  });
  document.querySelectorAll('.step-card').forEach(c => c.classList.remove('active'));
  const card = document.getElementById('step'+n);
  if (card) card.classList.add('active');
  currentStep = n;
  if (n === 4) buildReview();
  window.scrollTo(0,0);
}

// ── Validation ────────────────────────────────────────────
async function validateStep(n) {
  clearErrors();
  if (n === 1) {
    let ok = true;
    if (!v('first_name')) { showErr('first_name','First name is required'); ok=false; }
    if (!v('last_name'))  { showErr('last_name','Last name is required');   ok=false; }
    if (!v('national_id')){ showErr('national_id','National ID is required'); ok=false; }
    if (!v('position'))   { showErr('position','Position is required'); ok=false; }
    if (!ok) return false;

    // Async duplicate checks
    const dups = await Promise.all([
      v('national_id') ? checkDup('national_id', v('national_id')) : null,
      v('phone')       ? checkDup('phone', v('phone'))             : null,
    ]);
    for (const r of dups) {
      if (r && !r.available) { showErr(r.field, r.message); return false; }
    }
    return true;
  }

  if (n === 2) {
    let ok = true;
    const email = v('email'), pwd = v('password'), cpwd = v('confirm_password');
    if (!email) { showErr('email','Email is required'); ok=false; }
    else if (!/\S+@\S+\.\S+/.test(email)) { showErr('email','Invalid email format'); ok=false; }
    if (!pwd || pwd.length < 8) { showErr('password','Password must be at least 8 characters'); ok=false; }
    if (pwd !== cpwd) { showErr('confirm_password','Passwords do not match'); ok=false; }
    if (!ok) return false;

    // Check email duplicate
    const r = await checkDup('email', email);
    if (r && !r.available) { showErr('email', r.message); return false; }

    const ec = v('employee_code');
    if (ec) {
      const r2 = await checkDup('employee_code', ec);
      if (r2 && !r2.available) { showErr('employee_code', r2.message); return false; }
    }
    return true;
  }

  return true; // steps 3,4 have their own guards
}

async function checkDup(field, value) {
  try {
    const fd = new FormData();
    fd.append(CSRF_KEY,'_no_csrf_here'); // handled by server
    fd.append('field', field);
    fd.append('value', value);
    // We use a lightweight fetch without CSRF since this is a GET-equivalent check
    const resp = await fetch(BASE+'/registration/check-duplicate', {
      method:'POST', body: (() => {
        const f=new FormData();
        f.append(CSRF_KEY, CSRF);
        f.append('field',field);
        f.append('value',value);
        return f;
      })()
    });
    const data = await resp.json();
    return { field, available: data.available, message: data.message };
  } catch(e) { return { field, available: true }; }
}

// ── Face Enrollment ───────────────────────────────────────
FaceRecognition.onStatus(msg => {
  const el = document.getElementById('faceStatusText');
  if (el) el.textContent = msg;
});

async function startFaceCamera() {
  const btn = document.getElementById('startFaceBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Loading AI…';
  try {
    await FaceRecognition.loadModels();
    await FaceRecognition.startCamera(document.getElementById('regVideo'));
    FaceRecognition.setCanvas(document.getElementById('regCanvas'));
    document.getElementById('captureBtn').disabled = false;
    btn.style.display = 'none';
    setCurrentAngle(0);
    startDetecting();
  } catch(e) {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-camera-video me-1"></i>Start Camera';
    showToastMsg(e.message, 'error');
  }
}

function setCurrentAngle(idx) {
  currentAngleIdx = idx;
  const angle = ANGLES[idx];
  const hint  = ANGLE_LABELS[angle] || angle;
  const hintEl = document.getElementById('currentAngleHint');
  if (hintEl) hintEl.innerHTML = `<i class="bi bi-camera text-success me-1"></i><strong>Now capture:</strong> ${hint}`;
  ANGLES.forEach((a,i) => {
    const el = document.getElementById('angle_'+a);
    if (!el) return;
    if (capturedFaces[a]) el.className='angle-item done';
    else if (i===idx) el.className='angle-item active-angle';
    else el.className='angle-item';
  });
}

function startDetecting() {
  if (detectLoop) clearInterval(detectLoop);
  detectLoop = setInterval(async () => {
    try {
      const vid = document.getElementById('regVideo');
      if (!vid || !FaceRecognition.isModelsLoaded()) return;
      const det = await faceapi
        .detectAllFaces(vid, new faceapi.TinyFaceDetectorOptions({inputSize:224,scoreThreshold:0.4}))
        .withFaceLandmarks();
      const fc = det.length;
      const guide = document.getElementById('faceGuide');
      if (!guide) return;
      if (fc === 1) {
        const box = det[0].detection.box;
        const minDim = Math.min(vid.videoWidth||640, vid.videoHeight||480);
        const sizeOk = box.width > minDim * 0.2;
        const qual = Math.min(100, Math.round(det[0].detection.score * 100));
        lastQuality = qual;
        document.getElementById('qualityPct').textContent = qual+'%';
        document.getElementById('qualFill').style.width = qual+'%';
        guide.className = sizeOk ? 'face-guide detected' : 'face-guide';
        document.getElementById('faceStatusText').textContent = sizeOk
          ? '✅ Face detected — click Capture'
          : 'Move closer to the camera';
        faceDetected = sizeOk;
      } else if (fc > 1) {
        guide.className = 'face-guide bad';
        document.getElementById('faceStatusText').textContent = '⚠️ Multiple faces — only one person';
        faceDetected = false;
      } else {
        guide.className = 'face-guide';
        document.getElementById('faceStatusText').textContent = 'Position your face in the oval';
        faceDetected = false;
      }
    } catch(e) {}
  }, 500);
}

async function captureAngle() {
  if (!faceDetected) {
    showToastMsg('No face detected. Look directly at the camera.', 'warn');
    return;
  }
  if (lastQuality < 60) {
    showToastMsg('Image quality too low. Ensure good lighting.', 'warn');
    return;
  }

  const vid = document.getElementById('regVideo');
  const desc = await FaceRecognition.captureDescriptor();
  if (!desc) { showToastMsg('Could not extract face features. Try again.', 'error'); return; }

  const imgData = FaceRecognition.captureImage(0.8);
  const angle   = ANGLES[currentAngleIdx];

  capturedFaces[angle] = { descriptor: desc, image_data: imgData, label: angle, quality: lastQuality };

  // Update UI
  const el = document.getElementById('angle_'+angle);
  if (el) { el.className = 'angle-item done'; }
  updateEnrolledList();

  // Move to next uncaptured angle
  const nextUncaptured = ANGLES.findIndex((a,i) => i > currentAngleIdx && !capturedFaces[a]);
  if (nextUncaptured !== -1) {
    setCurrentAngle(nextUncaptured);
  } else {
    // Check if minimum (front) is done
    const captured = Object.keys(capturedFaces);
    if (captured.includes('front') && captured.length >= 1) {
      document.getElementById('faceNextBtn').disabled = false;
      document.getElementById('skipFaceBtn').style.display = 'none';
      document.getElementById('currentAngleHint').innerHTML =
        `<i class="bi bi-check-circle-fill text-success me-1"></i><strong>${captured.length} angle(s) captured!</strong> Click Next to continue.`;
    }
  }
  showToastMsg(`${angle.charAt(0).toUpperCase()+angle.slice(1)} face captured ✓`, 'success');
}

function updateEnrolledList() {
  const el = document.getElementById('enrolledList');
  const captured = Object.keys(capturedFaces);
  if (!captured.length) { el.innerHTML=''; return; }
  el.innerHTML = `<div style="font-size:12px;color:var(--muted);font-weight:600;margin-bottom:6px">${captured.length} of ${ANGLES.length} angles captured</div>
    <div style="display:flex;gap:6px;flex-wrap:wrap">` +
    captured.map(a => `<span style="background:var(--primary-light);color:var(--primary);padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;"><i class="bi bi-check2"></i> ${a}</span>`).join('') +
    `</div>`;

  // Enable next if front is captured
  if (capturedFaces['front']) {
    document.getElementById('faceNextBtn').disabled = false;
    document.getElementById('skipFaceBtn').style.display = 'none';
  }
}

function retakeAll() {
  capturedFaces = {};
  currentAngleIdx = 0;
  setCurrentAngle(0);
  updateEnrolledList();
  document.getElementById('faceNextBtn').disabled = true;
  document.getElementById('skipFaceBtn').style.display = '';
}

function skipFace() {
  faceSkipped = true;
  capturedFaces = {};
  goStep(4);
}

// ── Review ────────────────────────────────────────────────
function buildReview() {
  const deptSel = document.getElementById('department_id');
  const branchSel = document.getElementById('branch_id');
  const deptName  = deptSel.options[deptSel.selectedIndex]?.text || '—';
  const branchName= branchSel.options[branchSel.selectedIndex]?.text || '—';
  const faceCount = Object.keys(capturedFaces).length;

  document.getElementById('reviewContent').innerHTML = `
    <div class="review-section">
      <h4><i class="bi bi-person me-2"></i>Personal Information</h4>
      <div class="row">
        <div class="col-md-6">
          <div class="review-row"><span class="review-label">Full Name</span><span class="review-value">${esc(v('first_name'))} ${esc(v('last_name'))}</span></div>
          <div class="review-row"><span class="review-label">National ID</span><span class="review-value">${esc(v('national_id'))||'—'}</span></div>
          <div class="review-row"><span class="review-label">Phone</span><span class="review-value">${esc(v('phone'))||'—'}</span></div>
          <div class="review-row"><span class="review-label">Gender</span><span class="review-value">${esc(v('gender'))||'—'}</span></div>
        </div>
        <div class="col-md-6">
          <div class="review-row"><span class="review-label">Position</span><span class="review-value">${esc(v('position'))}</span></div>
          <div class="review-row"><span class="review-label">Department</span><span class="review-value">${esc(deptName)}</span></div>
          <div class="review-row"><span class="review-label">Branch</span><span class="review-value">${esc(branchName)}</span></div>
          <div class="review-row"><span class="review-label">Employment Type</span><span class="review-value">${esc(v('employment_type'))}</span></div>
        </div>
      </div>
    </div>
    <div class="review-section">
      <h4><i class="bi bi-lock me-2"></i>Account</h4>
      <div class="review-row"><span class="review-label">Email</span><span class="review-value">${esc(v('email'))}</span></div>
      <div class="review-row"><span class="review-label">Employee Code</span><span class="review-value">${esc(v('employee_code'))||'<em style="color:var(--muted)">Auto-generated on approval</em>'}</span></div>
      <div class="review-row"><span class="review-label">Password</span><span class="review-value">••••••••</span></div>
    </div>
    <div class="review-section">
      <h4><i class="bi bi-camera me-2"></i>Face Enrollment</h4>
      <div class="review-row"><span class="review-label">Status</span>
        <span class="review-value ${faceCount ? 'text-success' : 'text-warning'}">
          ${faceCount ? `✅ ${faceCount} angle(s) captured (${Object.keys(capturedFaces).join(', ')})` : '⚠️ Skipped — can be completed later'}
        </span>
      </div>
    </div>
    <div class="review-section" style="border-color:#FDE68A;background:#FFFBEB">
      <h4><i class="bi bi-info-circle me-2"></i>What Happens After Submit</h4>
      <div style="font-size:13px;color:var(--muted);line-height:1.7">
        <div><i class="bi bi-1-circle me-1"></i>Your registration will be reviewed by HR within 1-2 business days</div>
        <div><i class="bi bi-2-circle me-1"></i>You will receive a notification when approved or if changes are needed</div>
        <div><i class="bi bi-3-circle me-1"></i>Upon approval, your Employee ID and QR code will be generated</div>
        <div><i class="bi bi-4-circle me-1"></i>You can then login and start using face recognition attendance</div>
      </div>
    </div>`;
}

// ── Submit ────────────────────────────────────────────────
async function submitRegistration() {
  const btn = document.getElementById('submitBtn');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting…';

  const fd = new FormData();
  fd.append(CSRF_KEY, CSRF);
  fd.append('first_name',               v('first_name'));
  fd.append('last_name',                v('last_name'));
  fd.append('national_id',              v('national_id'));
  fd.append('passport_number',          v('passport_number')||'');
  fd.append('date_of_birth',            v('date_of_birth')||'');
  fd.append('gender',                   v('gender')||'');
  fd.append('phone',                    v('phone')||'');
  fd.append('address',                  v('address')||'');
  fd.append('position',                 v('position'));
  fd.append('department_id',            v('department_id')||'');
  fd.append('branch_id',                v('branch_id')||'');
  fd.append('employment_type',          v('employment_type'));
  fd.append('emergency_contact_name',   v('emergency_contact_name')||'');
  fd.append('emergency_contact_phone',  v('emergency_contact_phone')||'');
  fd.append('emergency_contact_relation', v('emergency_contact_relation')||'');
  fd.append('email',                    v('email'));
  fd.append('employee_code',            v('employee_code')||'');
  fd.append('password',                 v('password'));
  fd.append('confirm_password',         v('confirm_password'));

  // Face descriptors
  if (Object.keys(capturedFaces).length) {
    const faceArr = Object.values(capturedFaces).map(f => ({
      label:       f.label,
      descriptor:  f.descriptor,
      image_data:  f.image_data,
      quality:     f.quality,
    }));
    fd.append('face_descriptors', JSON.stringify(faceArr));
    const avgQual = faceArr.reduce((s,f)=>s+f.quality,0) / faceArr.length;
    fd.append('face_quality_score', avgQual.toFixed(2));
  }

  try {
    const resp = await fetch(BASE+'/registration/submit', { method:'POST', body:fd });
    const data = await resp.json();

    if (data.success) {
      // Stop camera
      FaceRecognition.stopCamera();
      if (detectLoop) clearInterval(detectLoop);

      // Show success step
      document.querySelectorAll('.step-card').forEach(c=>c.classList.remove('active'));
      document.getElementById('step5').classList.add('active');
      // Update stepper - all done
      for(let i=1;i<=5;i++){
        const el=document.getElementById('stepper'+i);
        if(el){el.className='step-item done';}
      }

      // Set status link
      if (data.status_url) {
        document.getElementById('statusLink').href = data.status_url;
        document.getElementById('statusLink').textContent = data.status_url;
        window._statusUrl = data.status_url;
      }
      currentStep = 5;
    } else {
      showToastMsg(data.message || 'Submission failed. Please try again.', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Submit Registration';
    }
  } catch(e) {
    showToastMsg('Network error: '+e.message, 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Submit Registration';
  }
}

function copyStatusLink() {
  const url = window._statusUrl || document.getElementById('statusLink').href;
  navigator.clipboard.writeText(url).then(() => showToastMsg('Link copied!','success'));
}

// ── Helpers ───────────────────────────────────────────────
const v     = id => (document.getElementById(id)||{}).value || '';
const esc   = s  => String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
const showErr = (field, msg) => {
  const el = document.getElementById('err_'+field) || document.getElementById(field);
  if (!el) return;
  if (el.classList.contains('field-error')) { el.textContent=msg; el.style.display=''; }
  else { const fc = document.getElementById(field); if(fc) fc.classList.add('is-invalid'); }
};
const clearErrors = () => {
  document.querySelectorAll('.field-error').forEach(e=>{e.textContent='';e.style.display='none'});
  document.querySelectorAll('.is-invalid').forEach(e=>e.classList.remove('is-invalid'));
};
function togglePwd(id, btn) {
  const el = document.getElementById(id);
  const isText = el.type==='text';
  el.type = isText?'password':'text';
  btn.querySelector('i').className = 'bi bi-eye'+(isText?'':'-slash');
}

// Password strength meter
document.getElementById('password')?.addEventListener('input', function() {
  const p = this.value;
  let s=0;
  if(p.length>=8)s++;if(p.length>=12)s++;
  if(/[A-Z]/.test(p))s++;if(/[0-9]/.test(p))s++;if(/[^A-Za-z0-9]/.test(p))s++;
  const pct=[0,20,40,70,90,100][s];
  const color=['#EF4444','#EF4444','#F59E0B','#F59E0B','#16A34A','#16A34A'][s];
  const label=['','Too short','Weak','Fair','Good','Strong'][s];
  document.getElementById('pwdFill').style.cssText=`width:${pct}%;background:${color}`;
  document.getElementById('pwdStrengthLabel').textContent = label || 'Enter a strong password';
});

function showToastMsg(msg, type='info') {
  const colors={success:'#16A34A',error:'#EF4444',warn:'#F59E0B',info:'#6366F1'};
  const t=document.createElement('div');
  t.style.cssText=`position:fixed;bottom:24px;left:50%;transform:translateX(-50%);background:${colors[type]||colors.info};color:#fff;padding:12px 22px;border-radius:12px;font-weight:600;font-size:14px;z-index:9999;box-shadow:0 4px 20px rgba(0,0,0,.2);animation:fadeIn .3s`;
  t.textContent=msg;
  document.body.appendChild(t);
  setTimeout(()=>t.remove(),4000);
}
window.addEventListener('beforeunload', () => { FaceRecognition.stopCamera(); if(detectLoop)clearInterval(detectLoop); });
</script>
</body>
</html>
