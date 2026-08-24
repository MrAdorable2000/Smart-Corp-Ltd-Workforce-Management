<?php /** @var array $data */ ?>
<?php
$shiftStart  = $employee['start_time'] ?? '08:00:00';
$shiftEnd    = $employee['end_time']   ?? '17:00:00';
$hasIn       = !empty($todayRec['check_in']);
$hasOut      = !empty($todayRec['check_out']);
$nowStr      = date('H:i:s');
$liveWorking = $hasIn && !$hasOut;
$liveSeconds = $liveWorking ? (time() - strtotime($todayRec['check_in'])) : 0;
$liveHours   = round($liveSeconds / 3600, 2);
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">
            My Attendance
            <?php if ($liveWorking): ?>
            <span class="badge bg-success ms-2" style="font-size:11px;font-weight:600;vertical-align:middle;">
                <span style="display:inline-block;width:7px;height:7px;border-radius:50%;background:#fff;animation:liveBlink2 1s infinite;margin-right:4px;"></span>
                WORKING NOW
            </span>
            <?php endif; ?>
        </h4>
        <p class="text-muted mb-0"><?= date('l, F j, Y') ?> · <?= htmlspecialchars($employee['dept_name'] ?? '') ?></p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= BASE_URL ?>/attendance/kiosk" class="btn btn-primary" target="_blank">
            <i class="bi bi-camera-video-fill"></i> Face Check-In
        </a>
        <a href="<?= BASE_URL ?>/attendance/qr" class="btn btn-light">
            <i class="bi bi-qr-code"></i> My QR
        </a>
        <a href="<?= BASE_URL ?>/attendance/mobile" class="btn btn-light">
            <i class="bi bi-phone"></i> Mobile
        </a>
        <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#correctionModal">
            <i class="bi bi-pencil-square"></i> Request Correction
        </button>
    </div>
</div>
<style>
@keyframes liveBlink2{0%,100%{opacity:1}50%{opacity:.3}}
.ss-today-card{border-radius:20px;padding:28px;margin-bottom:24px;position:relative;overflow:hidden}
.ss-today-card::before{content:'';position:absolute;top:-40px;right:-40px;width:180px;height:180px;border-radius:50%;background:rgba(255,255,255,.06)}
.progress-ring{transform:rotate(-90deg)}
.progress-ring-bg{fill:none;stroke:rgba(255,255,255,.15);stroke-width:6}
.progress-ring-fg{fill:none;stroke:#fff;stroke-width:6;stroke-linecap:round;transition:stroke-dashoffset 1s ease}
.time-big{font-size:38px;font-weight:900;letter-spacing:1px;font-variant-numeric:tabular-nums}
.ss-stat{background:rgba(255,255,255,.12);border-radius:12px;padding:12px 16px;text-align:center}
.ss-stat .val{font-size:20px;font-weight:800}
.ss-stat .lbl{font-size:11px;opacity:.75;margin-top:2px}
.chip-status{display:inline-flex;align-items:center;gap:5px;padding:4px 12px;border-radius:100px;font-size:12px;font-weight:700}
.rec-row{display:grid;grid-template-columns:80px 70px 70px 60px 50px 40px 80px;align-items:center;gap:8px;padding:10px 0;border-bottom:1px solid var(--border-color,#e2e8f0);font-size:13px}
.rec-row:last-child{border-bottom:none}
</style>

<!-- Today Status Card -->
<div class="ss-today-card mb-4" style="background:linear-gradient(135deg,<?= $hasOut ? '#334155,#1E293B' : ($hasIn ? '#1D4ED8,#4F46E5' : '#6B7280,#4B5563') ?>);">
    <div class="row g-3 align-items-center">
        <!-- Clock / progress -->
        <div class="col-auto">
            <?php
            $shiftTotalSec = strtotime('today '.$shiftEnd) - strtotime('today '.$shiftStart);
            $workedSec     = $hasIn ? min($liveSeconds, $shiftTotalSec) : 0;
            $circumference = 2 * M_PI * 42;
            $offset        = $shiftTotalSec > 0 ? $circumference * (1 - $workedSec / $shiftTotalSec) : $circumference;
            ?>
            <svg class="progress-ring" width="100" height="100" viewBox="0 0 100 100">
                <circle class="progress-ring-bg" cx="50" cy="50" r="42"/>
                <circle class="progress-ring-fg" cx="50" cy="50" r="42"
                    stroke-dasharray="<?= $circumference ?>"
                    stroke-dashoffset="<?= $offset ?>"
                    id="workProgressRing"/>
                <text x="50" y="54" text-anchor="middle" fill="#fff" font-size="14" font-weight="900" font-family="Inter,sans-serif">
                    <?= $hasIn ? ($hasOut ? 'Done' : 'Live') : 'Not In' ?>
                </text>
            </svg>
        </div>

        <!-- Main info -->
        <div class="col" style="color:#fff">
            <?php if ($hasIn): ?>
            <div class="time-big" id="liveTimer">
                <?php
                $h = floor($liveSeconds/3600);
                $m = floor(($liveSeconds%3600)/60);
                echo sprintf('%02d:%02d', $h, $m) . ($hasOut ? '' : ' hrs');
                ?>
            </div>
            <div style="font-size:14px;opacity:.75;margin-top:2px;">
                <?= $hasOut ? 'Total time worked today' : 'Time worked so far today' ?>
            </div>
            <?php else: ?>
            <div class="time-big">Not Checked In</div>
            <div style="font-size:14px;opacity:.75;margin-top:2px;">
                Shift starts at <?= date('g:i A', strtotime($shiftStart)) ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Stats -->
        <div class="col-auto">
            <div class="d-flex gap-2 flex-wrap">
                <div class="ss-stat" style="color:#fff;min-width:85px;">
                    <div class="val"><?= $hasIn ? date('H:i', strtotime($todayRec['check_in'])) : '—' ?></div>
                    <div class="lbl">Check In</div>
                </div>
                <div class="ss-stat" style="color:#fff;min-width:85px;">
                    <div class="val"><?= $hasOut ? date('H:i', strtotime($todayRec['check_out'])) : '—' ?></div>
                    <div class="lbl">Check Out</div>
                </div>
                <div class="ss-stat" style="color:#fff;min-width:85px;">
                    <?php $late = $todayRec['late_minutes'] ?? 0; ?>
                    <div class="val"><?= $late > 0 ? $late.'m' : '—' ?></div>
                    <div class="lbl">Late Min</div>
                </div>
                <div class="ss-stat" style="color:#fff;min-width:85px;">
                    <div class="val"><?= $todayRec['overtime_hours'] ?? '0' ?>h</div>
                    <div class="lbl">Overtime</div>
                </div>
            </div>
        </div>

        <!-- Status badge -->
        <div class="col-auto">
            <?php
            $st   = $todayRec['status'] ?? 'not_recorded';
            $cols = ['present'=>'#10B981','late'=>'#F59E0B','absent'=>'#EF4444','leave'=>'#3B82F6','half_day'=>'#8B5CF6','remote'=>'#06B6D4','not_recorded'=>'#6B7280'];
            $col  = $cols[$st] ?? '#6B7280';
            ?>
            <div style="background:<?= $col ?>20;border:2px solid <?= $col ?>;border-radius:12px;padding:12px 18px;text-align:center;color:#fff;">
                <div style="font-size:22px;font-weight:900;"><?= ucfirst(str_replace('_',' ',$st)) ?></div>
                <div style="font-size:11px;opacity:.7;">Today's Status</div>
            </div>
        </div>
    </div>
</div>

<!-- Month Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-emerald">
            <div class="stat-icon"><i class="bi bi-check2-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $monthSummary['present_days'] ?? 0 ?></div>
                <div class="stat-label">Present Days</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-amber">
            <div class="stat-icon"><i class="bi bi-clock-history"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $monthSummary['late_days'] ?? 0 ?></div>
                <div class="stat-label">Late Days</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-sky">
            <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= round($monthSummary['total_hours'] ?? 0, 1) ?>h</div>
                <div class="stat-label">Hours Worked</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-violet">
            <div class="stat-icon"><i class="bi bi-graph-up-arrow"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= round($monthSummary['overtime_hours'] ?? 0, 1) ?>h</div>
                <div class="stat-label">Overtime</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-rose">
            <div class="stat-icon"><i class="bi bi-x-circle"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $monthSummary['absent_days'] ?? 0 ?></div>
                <div class="stat-label">Absent</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-xl-2">
        <div class="stat-card stat-emerald" style="cursor:pointer;" onclick="window.location='<?= BASE_URL ?>/corrections'">
            <div class="stat-icon"><i class="bi bi-bar-chart-line"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $attendanceRate ?>%</div>
                <div class="stat-label">Attendance Rate</div>
                <?php if ($pendingCorrections > 0): ?>
                <div class="stat-trend"><i class="bi bi-exclamation-circle"></i> <?= $pendingCorrections ?> correction pending</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Chart + Recent -->
<div class="row g-3">
    <!-- Weekly chart -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>Last 10 Days</div>
            <div class="card-body">
                <canvas id="myWeekChart" height="220"></canvas>
            </div>
        </div>
    </div>

    <!-- Recent attendance table -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-calendar3 me-2"></i>Recent Attendance</span>
                <a href="<?= BASE_URL ?>/attendance/my" class="btn btn-sm btn-light">View All</a>
            </div>
            <div class="card-body p-0">
                <div style="overflow-y:auto;max-height:380px;">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>In</th>
                            <th>Out</th>
                            <th>Hours</th>
                            <th>Late</th>
                            <th>OT</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-600"><?= date('d', strtotime($r['attendance_date'])) ?></div>
                                <div class="text-muted text-xs"><?= date('D', strtotime($r['attendance_date'])) ?></div>
                            </td>
                            <td class="fw-600 text-success"><?= $r['check_in'] ? date('H:i', strtotime($r['check_in'])) : '—' ?></td>
                            <td class="fw-600 text-primary"><?= $r['check_out'] ? date('H:i', strtotime($r['check_out'])) : '—' ?></td>
                            <td><?= $r['working_hours'] ? number_format($r['working_hours'],1).'h' : '—' ?></td>
                            <td><?= ($r['late_minutes']??0)>0 ? '<span class="text-warning">'.$r['late_minutes'].'m</span>' : '—' ?></td>
                            <td><?= ($r['overtime_hours']??0)>0 ? '<span class="text-info">'.$r['overtime_hours'].'h</span>' : '—' ?></td>
                            <td>
                                <?php $bg=['present'=>'success','late'=>'warning','absent'=>'danger','leave'=>'info','half_day'=>'secondary','remote'=>'primary'][$r['status']]??'secondary'; ?>
                                <span class="badge bg-<?= $bg ?>"><?= ucfirst(str_replace('_',' ',$r['status'])) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recent)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No attendance records yet</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Correction Modal -->
<div class="modal fade" id="correctionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Request Attendance Correction</h5>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="corrForm">
                    <?= $csrf ?>
                    <div class="mb-3">
                        <label class="form-label">Date to Correct <span class="text-danger">*</span></label>
                        <input type="date" name="attendance_date" class="form-control" max="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Correct Check-In</label>
                            <input type="time" name="requested_check_in" class="form-control">
                        </div>
                        <div class="col-6">
                            <label class="form-label">Correct Check-Out</label>
                            <input type="time" name="requested_check_out" class="form-control">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control" rows="3" required placeholder="Explain why you need this correction…"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary" id="submitCorrBtn"><i class="bi bi-send me-1"></i>Submit</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Live timer
<?php if ($liveWorking): ?>
let liveSec = <?= $liveSeconds ?>;
const timerEl = document.getElementById('liveTimer');
if (timerEl) {
    setInterval(() => {
        liveSec++;
        const h = Math.floor(liveSec/3600);
        const m = Math.floor((liveSec%3600)/60);
        const s = liveSec % 60;
        timerEl.textContent = (h>0?String(h).padStart(2,'0')+':':'') + String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    }, 1000);
}
<?php endif; ?>

// Week chart
const wData = <?= json_encode($weekData) ?>;
const dark  = document.documentElement.getAttribute('data-theme') === 'dark';
const tc    = dark ? '#94A3B8' : '#64748B';

new Chart(document.getElementById('myWeekChart'), {
    type: 'bar',
    data: {
        labels: wData.map(r => {
            const d = new Date(r.attendance_date);
            return d.toLocaleDateString('en-US', {month:'short', day:'numeric'});
        }).reverse(),
        datasets: [{
            label: 'Hours Worked',
            data: wData.map(r => parseFloat(r.working_hours)||0).reverse(),
            backgroundColor: wData.map(r => {
                const s = r.status;
                return s==='present'?'rgba(16,185,129,.7)':s==='late'?'rgba(245,158,11,.7)':s==='absent'?'rgba(239,68,68,.5)':'rgba(99,102,241,.5)';
            }).reverse(),
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend:{ display:false } },
        scales: {
            x: { ticks:{color:tc}, grid:{color:dark?'rgba(255,255,255,.05)':'rgba(0,0,0,.04)'} },
            y: { ticks:{color:tc}, grid:{color:dark?'rgba(255,255,255,.05)':'rgba(0,0,0,.04)'}, beginAtZero:true, max:10 }
        }
    }
});

// Correction submit
document.getElementById('submitCorrBtn').addEventListener('click', async function() {
    const fd = new FormData(document.getElementById('corrForm'));
    this.disabled = true;
    this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Submitting…';
    try {
        const resp = await fetch('<?= BASE_URL ?>/corrections/create', {method:'POST',body:fd});
        const data = await resp.json();
        if (data.success) {
            bootstrap.Modal.getInstance(document.getElementById('correctionModal')).hide();
            showToast(data.message,'success');
        } else showToast(data.message||'Failed','error');
    } catch(e) { showToast('Error: '+e.message,'error'); }
    this.disabled = false;
    this.innerHTML = '<i class="bi bi-send me-1"></i>Submit';
});
</script>
