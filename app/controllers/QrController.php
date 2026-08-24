<?php
/**
 * QR Code Attendance Controller
 * Handles QR generation, display, and scanning (kiosk + mobile)
 */
class QrController extends Controller
{
    // ── Employee QR page (authenticated) ─────────────────────────────
    public function myQr()
    {
        $this->requireAuth();
        $empId = Auth::employeeId();
        if (!$empId) { Flash::set('error', 'No employee profile linked.'); $this->redirect('/dashboard'); }

        $qr = QrAttendance::getOrCreate($empId);
        $employee = Database::getInstance()->fetch(
            "SELECT e.*, d.name AS dept FROM employees e LEFT JOIN departments d ON d.id = e.department_id WHERE e.id = :id",
            ['id' => $empId]
        );

        $this->view('attendance/my_qr', [
            'title'     => 'My QR Code',
            'pageTitle' => 'My Attendance QR Code',
            'qr'        => $qr,
            'employee'  => $employee,
            'qrImgUrl'  => QrAttendance::imageUrl($qr['qr_token'], 300),
            'scanUrl'   => BASE_URL . '/attendance/qr-scan?token=' . urlencode($qr['qr_token']),
            'csrf'      => CSRF::field(),
        ]);
    }

    // ── Regenerate QR (authenticated) ────────────────────────────────
    public function regenerate()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $empId = Auth::employeeId();
        if (!$empId) $this->json(['success' => false, 'message' => 'No employee profile'], 400);

        $qr = QrAttendance::generate($empId);
        $this->json([
            'success'   => true,
            'message'   => 'New QR code generated',
            'qr_token'  => $qr['qr_token'],
            'qr_img'    => QrAttendance::imageUrl($qr['qr_token'], 300),
            'expires_at'=> $qr['expires_at'],
        ]);
    }

    // ── Public QR Scan page (kiosk / mobile) ─────────────────────────
    public function scanPage()
    {
        $this->layout = 'public';
        $token = $this->query('token', '');

        $this->view('attendance/qr_scan', [
            'title'     => 'QR Attendance',
            'pageTitle' => 'QR Code Check-In',
            'token'     => $token,
            'csrf'      => CSRF::field(),
        ]);
    }

    // ── Process QR scan (AJAX POST, public) ──────────────────────────
    public function processScan()
    {
        $this->validateCsrf();

        $token = trim($this->input('token', ''));
        $lat   = $this->input('latitude', null);
        $lng   = $this->input('longitude', null);

        if (!$token) $this->json(['success' => false, 'message' => 'QR token is required'], 400);

        // Validate QR
        $validation = QrAttendance::validate($token);
        if (!$validation['valid']) {
            $this->json(['success' => false, 'message' => $validation['message']], 400);
        }

        $empId    = $validation['employee_id'];
        $today    = date('Y-m-d');
        $todayRec = Database::getInstance()->fetch(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
            ['eid' => $empId, 'd' => $today]
        );

        // Rate-limit: prevent scan spam (min 30 seconds between scans)
        if ($todayRec && $todayRec['check_in']) {
            $lastScan = max(
                strtotime($todayRec['check_in']),
                $todayRec['check_out'] ? strtotime($todayRec['check_out']) : 0
            );
            if ((time() - $lastScan) < 30) {
                $this->json(['success' => false, 'message' => 'Please wait 30 seconds before scanning again'], 429);
            }
        }

        // Determine action
        if (!$todayRec || !$todayRec['check_in']) {
            $action = 'check_in';
        } elseif (!$todayRec['check_out']) {
            $action = 'check_out';
        } else {
            $this->json([
                'success'      => true,
                'already_done' => true,
                'message'      => 'You have already completed attendance for today.',
                'employee'     => $validation['employee'],
            ]);
        }

        // Process
        $ctrl   = new AttendanceController();
        $result = $action === 'check_in'
            ? $ctrl->processCheckInPublic($empId, $lat, $lng, 100, 'qr')
            : $ctrl->processCheckOutPublic($empId, $lat, $lng, 100, 'qr');

        $result['employee'] = $validation['employee'];
        $result['action']   = $action;
        $this->json($result);
    }

    // ── Admin: bulk generate QR codes ─────────────────────────────────
    public function bulkGenerate()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_employees');

        $employees = Database::getInstance()->fetchAll(
            "SELECT id FROM employees WHERE is_active = 1 AND qr_enrolled = 0"
        );
        $count = 0;
        foreach ($employees as $e) {
            QrAttendance::generate((int)$e['id']);
            $count++;
        }
        $this->json(['success' => true, 'message' => "Generated QR codes for {$count} employees"]);
    }

    // ── Admin: view all employee QRs ──────────────────────────────────
    public function adminList()
    {
        $this->requireAuth();
        $this->requirePermission('manage_employees');

        $qrs = Database::getInstance()->fetchAll(
            "SELECT q.*, e.employee_code, e.first_name, e.last_name, e.photo, d.name AS dept
             FROM employee_qr_codes q
             INNER JOIN employees e ON e.id = q.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE q.is_active = 1
             ORDER BY e.first_name"
        );

        $this->view('attendance/qr_admin', [
            'title'     => 'Employee QR Codes',
            'pageTitle' => 'Attendance QR Codes',
            'qrs'       => $qrs,
            'csrf'      => CSRF::field(),
        ]);
    }
}
