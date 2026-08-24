<?php
/**
 * Attendance Controller - Complete Implementation
 */
class AttendanceController extends Controller
{
    // Shift start time used when no employee shift is configured
    const DEFAULT_SHIFT_START = '08:00';
    const DEFAULT_SHIFT_END   = '17:00';
    const LATE_GRACE_MINUTES  = 15;   // minutes after shift start before "late"
    const HALF_DAY_HOURS      = 4.0;  // fewer than this = half day
    const EARLY_THRESHOLD_MIN = 15;   // minutes before shift end = early departure

    // =========================================================
    //  ADMIN: Attendance list
    // =========================================================
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('view_attendance');

        $date   = $this->query('date', date('Y-m-d'));
        $deptId = $this->query('department_id', '');
        $status = $this->query('status', '');

        $sql = "SELECT a.*, e.employee_code, e.first_name, e.last_name, e.photo,
                       d.name AS department_name, s.name AS shift_name,
                       s.start_time, s.end_time
                FROM attendance a
                INNER JOIN employees e ON e.id = a.employee_id
                LEFT JOIN departments d ON d.id = e.department_id
                LEFT JOIN shifts s ON s.id = a.shift_id
                WHERE a.attendance_date = :date";
        $params = ['date' => $date];

        if ($deptId) {
            $sql .= " AND e.department_id = :dept";
            $params['dept'] = $deptId;
        }
        if ($status) {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }
        $sql .= " ORDER BY a.check_in DESC";

        $records     = Database::getInstance()->fetchAll($sql, $params);
        $departments = Database::getInstance()->fetchAll("SELECT * FROM departments WHERE is_active = 1");

        // Today KPI stats
        $today = date('Y-m-d');
        $totalEmployees = Database::getInstance()->count('employees', 'is_active = 1');
        $checkedIn   = Database::getInstance()->count('attendance',
            'attendance_date = :d AND check_in IS NOT NULL', ['d' => $today]);
        $checkedOut  = Database::getInstance()->count('attendance',
            'attendance_date = :d AND check_out IS NOT NULL', ['d' => $today]);

        // Summary for selected date
        $summary = ['present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0, 'half_day' => 0, 'remote' => 0, 'early_departure' => 0];
        $totalHours = 0; $totalOvertime = 0;
        foreach ($records as $r) {
            if (isset($summary[$r['status']])) $summary[$r['status']]++;
            $totalHours   += (float)($r['working_hours']  ?? 0);
            $totalOvertime += (float)($r['overtime_hours'] ?? 0);
        }
        $summary['total_hours']   = round($totalHours, 2);
        $summary['total_overtime'] = round($totalOvertime, 2);

        // Attendance percentage
        $attPct = $totalEmployees > 0
            ? round((($summary['present'] + $summary['late'] + $summary['half_day']) / $totalEmployees) * 100, 1)
            : 0;

        // Weekly trend
        $weekTrend = Database::getInstance()->fetchAll(
            "SELECT attendance_date,
                    SUM(CASE WHEN status IN('present','late','half_day','remote') THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent,
                    COALESCE(SUM(working_hours),0) as total_hours,
                    COALESCE(SUM(overtime_hours),0) as overtime
             FROM attendance
             WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY attendance_date ORDER BY attendance_date ASC"
        );

        // Monthly trend (current month by week)
        $monthlyTrend = Database::getInstance()->fetchAll(
            "SELECT WEEK(attendance_date,1) as week_num,
                    MIN(attendance_date) as week_start,
                    SUM(CASE WHEN status IN('present','late','half_day','remote') THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status='absent' THEN 1 ELSE 0 END) as absent
             FROM attendance
             WHERE attendance_date BETWEEN DATE_FORMAT(CURDATE(),'%Y-%m-01') AND LAST_DAY(CURDATE())
             GROUP BY week_num ORDER BY week_num ASC"
        );

        // Department attendance today
        $deptAttendance = Database::getInstance()->fetchAll(
            "SELECT d.name AS dept,
                    COUNT(DISTINCT e.id) as total,
                    SUM(CASE WHEN a.attendance_date=:d AND a.status IN('present','late','half_day','remote') THEN 1 ELSE 0 END) as present
             FROM departments d
             LEFT JOIN employees e ON e.department_id = d.id AND e.is_active = 1
             LEFT JOIN attendance a ON a.employee_id = e.id AND a.attendance_date = :d2
             WHERE d.is_active = 1
             GROUP BY d.id, d.name
             ORDER BY d.name",
            ['d' => $today, 'd2' => $today]
        );

        $this->view('attendance/index', [
            'title'          => 'Attendance',
            'pageTitle'      => 'Attendance Records',
            'records'        => $records,
            'departments'    => $departments,
            'date'           => $date,
            'deptId'         => $deptId,
            'statusFilter'   => $status,
            'summary'        => $summary,
            'totalEmployees' => $totalEmployees,
            'checkedIn'      => $checkedIn,
            'checkedOut'     => $checkedOut,
            'attPct'         => $attPct,
            'weekTrend'      => $weekTrend,
            'monthlyTrend'   => $monthlyTrend,
            'deptAttendance' => $deptAttendance,
            'csrf'           => CSRF::field(),
        ]);
    }

    // =========================================================
    //  Face scan page (authenticated employee)
    // =========================================================
    public function faceScan()
    {
        $this->requireAuth();

        $todayRecord = null;
        if (Auth::employeeId()) {
            $todayRecord = Database::getInstance()->fetch(
                "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
                ['eid' => Auth::employeeId(), 'd' => date('Y-m-d')]
            );
        }

        $this->view('attendance/face_scan', [
            'title'       => 'Face Check-In/Out',
            'pageTitle'   => 'Face Recognition Attendance',
            'todayRecord' => $todayRecord,
            'csrf'        => CSRF::field(),
        ]);
    }

    // =========================================================
    //  Kiosk Mode (public, no login required)
    // =========================================================
    public function kiosk()
    {
        $this->layout = 'public';
        $companyName = APP_NAME;

        // Get company info if available
        try {
            $company = Database::getInstance()->fetch("SELECT * FROM companies LIMIT 1");
            if ($company) $companyName = $company['name'];
        } catch (\Exception $e) {}

        $this->view('attendance/kiosk', [
            'title'       => 'Attendance Kiosk',
            'pageTitle'   => 'Attendance Kiosk',
            'companyName' => $companyName,
            'csrf'        => CSRF::field(),
        ]);
    }

    // =========================================================
    //  Face scan for kiosk (public endpoint, CSRF-protected)
    // =========================================================
    public function kioskFaceScan()
    {
        $this->validateCsrf();

        $descriptor = $this->input('descriptor');
        $antiSpoof  = $this->input('anti_spoof', []);
        $lat        = $this->input('latitude',  null);
        $lng        = $this->input('longitude', null);
        $action     = $this->input('action', 'auto');
        $snapshot   = $this->input('snapshot', '');  // base64 selfie for fraud logging

        if (!$descriptor) {
            $this->json(['success' => false, 'message' => 'Missing face descriptor'], 400);
        }

        $probe = json_decode($descriptor, true);
        if (!is_array($probe) || count($probe) !== 128) {
            $this->json(['success' => false, 'message' => 'Invalid descriptor format'], 400);
        }

        // Anti-spoofing validation
        if (is_string($antiSpoof)) $antiSpoof = json_decode($antiSpoof, true) ?? [];
        $livenessOk = ($antiSpoof['liveness_passed'] ?? false)
                   || (($antiSpoof['blink_count'] ?? 0) >= 2 && ($antiSpoof['head_movement'] ?? false));

        if (!$livenessOk) {
            $this->json(['success' => false, 'message' => 'Liveness check failed. Please blink twice and turn your head slightly.'], 403);
        }

        // Match face — enforce 90% minimum confidence
        $result = $this->matchFaceToEmployee($probe);

        // Log unknown face attempts for security
        if (!$result['matched'] || ($result['confidence'] ?? 0) < FACE_MIN_CONFIDENCE) {
            if (FACE_FRAUD_LOG) {
                $this->logFraudAttempt($probe, $result, $lat, $lng, $snapshot);
            }
            $score = round($result['score'] ?? 0);
            $msg   = $score > 50
                ? "Face recognized at {$score}% but minimum 90% required. Please re-enroll."
                : 'Face not recognized. Ensure you are enrolled or contact HR.';
            $this->json(['success' => false, 'message' => $msg, 'score' => $score]);
        }

        $employeeId = $result['employee_id'];
        $confidence = round($result['confidence']);
        $matchScore = round($result['score'], 2);

        // Auto-determine action
        $today    = date('Y-m-d');
        $todayRec = Database::getInstance()->fetch(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
            ['eid' => $employeeId, 'd' => $today]
        );

        if ($action === 'auto') {
            if (!$todayRec || !$todayRec['check_in'])  $action = 'check_in';
            elseif (!$todayRec['check_out'])             $action = 'check_out';
            else {
                $this->json([
                    'success'      => true,
                    'already_done' => true,
                    'message'      => 'You have already checked in and out today.',
                    'employee'     => $result['employee'],
                    'confidence'   => $confidence,
                ]);
            }
        }

        // ── Verify employee account is ACTIVE (not pending approval) ──────
        $empCheck = Database::getInstance()->fetch(
            "SELECT e.is_active, u.status AS user_status
             FROM employees e LEFT JOIN users u ON u.employee_id = e.id
             WHERE e.id = :id LIMIT 1",
            ['id' => $employeeId]
        );
        if (!$empCheck || !$empCheck['is_active']) {
            $this->json(['success' => false, 'message' => 'Your account is inactive. Please contact HR.', 'employee' => $result['employee']]);
        }
        $userStatus = $empCheck['user_status'] ?? 'active';
        if (!in_array($userStatus, ['active'])) {
            $msg = match($userStatus) {
                'pending'   => 'Your account is pending approval. Attendance will be enabled once HR approves your registration.',
                'suspended' => 'Your account has been suspended. Contact HR immediately.',
                'inactive'  => 'Your account is inactive. Contact HR.',
                default     => 'Account not active (' . $userStatus . '). Contact HR.',
            };
            $this->json(['success' => false, 'message' => $msg, 'blocked' => true, 'employee' => $result['employee']]);
        }

        $res = $action === 'check_in'
            ? $this->processCheckIn($employeeId, $lat, $lng, $matchScore, 'face')
            : $this->processCheckOut($employeeId, $lat, $lng, $matchScore, 'face');

        // Save kiosk selfie snapshot
        if ($snapshot && $res['success']) {
            $this->saveAttendanceSnapshot($employeeId, $snapshot, $res['attendance_id'] ?? null);
        }

        $res['employee']   = $result['employee'];
        $res['action']     = $action;
        $res['confidence'] = $confidence;
        $res['match_score']= $matchScore;
        $this->json($res);
    }

    // =========================================================
    //  AJAX Check-In (authenticated)
    // =========================================================
    public function checkIn()
    {
        $this->validateCsrf();

        $employeeId = (int) $this->input('employee_id');
        $lat        = $this->input('latitude',   null);
        $lng        = $this->input('longitude',  null);
        $matchScore = (float) $this->input('match_score', 0);
        $method     = $this->input('method', 'face');

        // Must be either: logged-in employee checking themselves in,
        // OR a face-recognition scan with a valid match score (>= 70 = already validated by /face/match)
        $isLoggedIn = Auth::check();
        $isFaceAuth = $matchScore >= 70.0; // came from face/match endpoint

        if (!$employeeId) {
            $this->json(['success' => false, 'message' => 'Employee ID required'], 400);
        }

        if (!$isLoggedIn && !$isFaceAuth) {
            $this->json(['success' => false, 'message' => 'Authentication required'], 401);
        }

        // Security: logged-in non-admin users can only check in themselves
        if ($isLoggedIn && !Auth::can('manage_attendance')) {
            $myEmpId = Auth::employeeId();
            if ($myEmpId && (int)$myEmpId !== $employeeId) {
                $this->json(['success' => false, 'message' => 'You can only record your own attendance'], 403);
            }
        }

        $result = $this->processCheckIn($employeeId, $lat, $lng, $matchScore, $method);
        $this->json($result, $result['success'] ? 200 : 400);
    }

    // =========================================================
    //  AJAX Check-Out
    // =========================================================
    public function checkOut()
    {
        $this->validateCsrf();

        $employeeId = (int) $this->input('employee_id');
        $lat        = $this->input('latitude',   null);
        $lng        = $this->input('longitude',  null);
        $matchScore = (float) $this->input('match_score', 0);
        $method     = $this->input('method', 'face');

        $isLoggedIn = Auth::check();
        $isFaceAuth = $matchScore >= 70.0;

        if (!$employeeId) {
            $this->json(['success' => false, 'message' => 'Employee ID required'], 400);
        }

        if (!$isLoggedIn && !$isFaceAuth) {
            $this->json(['success' => false, 'message' => 'Authentication required'], 401);
        }

        if ($isLoggedIn && !Auth::can('manage_attendance')) {
            $myEmpId = Auth::employeeId();
            if ($myEmpId && (int)$myEmpId !== $employeeId) {
                $this->json(['success' => false, 'message' => 'You can only record your own attendance'], 403);
            }
        }

        $result = $this->processCheckOut($employeeId, $lat, $lng, $matchScore, $method);
        $this->json($result, $result['success'] ? 200 : 400);
    }

    // =========================================================
    //  Public proxies (used by Api\Attendance controller)
    // =========================================================
    public function processCheckInPublic($empId, $lat, $lng, $score, $method)
    {
        return $this->processCheckIn($empId, $lat, $lng, $score, $method);
    }

    public function processCheckOutPublic($empId, $lat, $lng, $score, $method)
    {
        return $this->processCheckOut($empId, $lat, $lng, $score, $method);
    }

    public function matchFacePublic($probe)
    {
        return $this->matchFaceToEmployee($probe);
    }

    // =========================================================
    //  Core Check-In logic
    // =========================================================
    private function processCheckIn($employeeId, $lat, $lng, $matchScore, $method = 'face')
    {
        $today  = date('Y-m-d');
        $nowStr = date('Y-m-d H:i:s');

        // Duplicate check
        $existing = Database::getInstance()->fetch(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
            ['eid' => $employeeId, 'd' => $today]
        );

        if ($existing && $existing['check_in']) {
            return [
                'success' => false,
                'message' => 'Already checked in today at ' . date('H:i', strtotime($existing['check_in']))
            ];
        }

        // Get employee + shift
        $employee = Database::getInstance()->fetch(
            "SELECT e.*, s.start_time, s.end_time, s.grace_period_minutes,
                    s.late_threshold_minutes, s.working_hours_per_day
             FROM employees e
             LEFT JOIN shifts s ON s.id = e.shift_id
             WHERE e.id = :id",
            ['id' => $employeeId]
        );

        if (!$employee) return ['success' => false, 'message' => 'Employee not found'];

        // Status & late minutes calculation
        [$status, $lateMinutes] = $this->calculateCheckInStatus($employee, $nowStr);

        // GPS geofence
        $gpsVerified = 0;
        if ($lat && $lng && $employee['branch_id']) {
            $gpsVerified = $this->verifyGeofence($employee['branch_id'], $lat, $lng);
        }

        $ip = $this->clientIp();

        if ($existing) {
            Database::getInstance()->update('attendance', [
                'check_in'           => $nowStr,
                'check_in_method'    => $method,
                'check_in_latitude'  => $lat,
                'check_in_longitude' => $lng,
                'late_minutes'       => $lateMinutes,
                'status'             => $status,
                'verified_by_face'   => ($method === 'face') ? 1 : 0,
                'verified_by_gps'    => $gpsVerified,
                'shift_id'           => $employee['shift_id'],
            ], 'id = :id', ['id' => $existing['id']]);
            $attendanceId = $existing['id'];
        } else {
            $attendanceId = Database::getInstance()->insert('attendance', [
                'employee_id'        => $employeeId,
                'attendance_date'    => $today,
                'shift_id'           => $employee['shift_id'],
                'check_in'           => $nowStr,
                'check_in_method'    => $method,
                'check_in_latitude'  => $lat,
                'check_in_longitude' => $lng,
                'late_minutes'       => $lateMinutes,
                'status'             => $status,
                'verified_by_face'   => ($method === 'face') ? 1 : 0,
                'verified_by_gps'    => $gpsVerified,
            ]);
        }

        // Log
        $this->logAttendanceEvent($employeeId, $attendanceId, 'check_in', $nowStr, $lat, $lng, $matchScore, $ip);

        // Audit
        try { Auth::audit('check_in', 'attendance', "Employee {$employeeId} checked in", null, ['time' => $nowStr, 'status' => $status]); } catch (\Exception $e) {}

        // Late notification
        if ($status === 'late') {
            $this->sendLateNotification($employeeId, $lateMinutes);
        }

        return [
            'success'       => true,
            'message'       => 'Check-In Successful! Good ' . (date('H') < 12 ? 'morning' : 'afternoon') . '!',
            'status'        => $status,
            'late_minutes'  => $lateMinutes,
            'attendance_id' => $attendanceId,
            'check_in_time' => $nowStr,
        ];
    }

    // =========================================================
    //  Core Check-Out logic
    // =========================================================
    private function processCheckOut($employeeId, $lat, $lng, $matchScore, $method = 'face')
    {
        $today  = date('Y-m-d');
        $nowStr = date('Y-m-d H:i:s');

        $record = Database::getInstance()->fetch(
            "SELECT a.*, e.shift_id, s.end_time, s.early_leave_threshold_minutes, s.working_hours_per_day
             FROM attendance a
             INNER JOIN employees e ON e.id = a.employee_id
             LEFT JOIN shifts s ON s.id = e.shift_id
             WHERE a.employee_id = :eid AND a.attendance_date = :d",
            ['eid' => $employeeId, 'd' => $today]
        );

        if (!$record || !$record['check_in']) {
            return ['success' => false, 'message' => 'No check-in record found for today. Please check in first.'];
        }
        if ($record['check_out']) {
            return ['success' => false, 'message' => 'Already checked out today at ' . date('H:i', strtotime($record['check_out']))];
        }

        // Working hours
        $checkIn     = new DateTime($record['check_in']);
        $checkOut    = new DateTime($nowStr);
        $diffSeconds = $checkOut->getTimestamp() - $checkIn->getTimestamp();
        $workingHours = round($diffSeconds / 3600, 2);

        // Overtime & early leave
        [$overtimeHours, $earlyLeaveMinutes, $finalStatus] = $this->calculateCheckOutMetrics(
            $record, $nowStr, $workingHours, $today
        );

        $ip = $this->clientIp();

        Database::getInstance()->update('attendance', [
            'check_out'            => $nowStr,
            'check_out_method'     => $method,
            'check_out_latitude'   => $lat,
            'check_out_longitude'  => $lng,
            'working_hours'        => $workingHours,
            'overtime_hours'       => $overtimeHours,
            'early_leave_minutes'  => $earlyLeaveMinutes,
            'status'               => $finalStatus,
            'verified_by_face'     => ($method === 'face') ? 1 : 0,
        ], 'id = :id', ['id' => $record['id']]);

        $this->logAttendanceEvent($employeeId, $record['id'], 'check_out', $nowStr, $lat, $lng, $matchScore, $ip);

        try { Auth::audit('check_out', 'attendance', "Employee {$employeeId} checked out", null, ['time' => $nowStr, 'hours' => $workingHours]); } catch (\Exception $e) {}

        return [
            'success'            => true,
            'message'            => 'Check-Out Successful! See you tomorrow!',
            'check_out_time'     => $nowStr,
            'working_hours'      => $workingHours,
            'overtime_hours'     => $overtimeHours,
            'early_leave_minutes'=> $earlyLeaveMinutes,
            'final_status'       => $finalStatus,
        ];
    }

    // =========================================================
    //  Status calculation for check-in
    // =========================================================
    private function calculateCheckInStatus($employee, $nowStr)
    {
        $shiftStart = $employee['start_time'] ?? self::DEFAULT_SHIFT_START;
        $grace      = (int)($employee['grace_period_minutes'] ?? self::LATE_GRACE_MINUTES);

        $today = date('Y-m-d');
        $shiftStartDt = new DateTime($today . ' ' . $shiftStart);
        $nowDt        = new DateTime($nowStr);

        $diffMinutes = ($nowDt->getTimestamp() - $shiftStartDt->getTimestamp()) / 60;

        $lateMinutes = 0;
        $status = 'present';

        if ($diffMinutes > $grace) {
            $status = 'late';
            $lateMinutes = (int) max(0, $diffMinutes - $grace);
        }

        return [$status, $lateMinutes];
    }

    // =========================================================
    //  Metrics calculation for check-out
    // =========================================================
    private function calculateCheckOutMetrics($record, $nowStr, $workingHours, $today)
    {
        $shiftEnd         = $record['end_time'] ?? self::DEFAULT_SHIFT_END;
        $earlyThreshold   = (int)($record['early_leave_threshold_minutes'] ?? self::EARLY_THRESHOLD_MIN);
        $requiredHours    = (float)($record['working_hours_per_day'] ?? 8.0);

        $shiftEndDt = new DateTime($today . ' ' . $shiftEnd);
        $checkOutDt = new DateTime($nowStr);

        $diffSeconds  = $checkOutDt->getTimestamp() - $shiftEndDt->getTimestamp();
        $diffHours    = $diffSeconds / 3600;

        $overtimeHours    = 0;
        $earlyLeaveMinutes = 0;

        if ($diffHours > 0) {
            $overtimeHours = round($diffHours, 2);
        } else {
            $earlyMin = abs($diffSeconds / 60);
            if ($earlyMin > $earlyThreshold) {
                $earlyLeaveMinutes = (int) $earlyMin;
            }
        }

        // Determine final status
        $originalStatus = $record['status'] ?? 'present';
        $finalStatus = $originalStatus;

        if ($workingHours < self::HALF_DAY_HOURS) {
            $finalStatus = 'half_day';
        } elseif ($earlyLeaveMinutes > self::EARLY_THRESHOLD_MIN) {
            // Keep original (present/late) but note early departure
        } elseif ($overtimeHours > 0) {
            // status stays present/late, overtime tracked separately
        }

        return [$overtimeHours, $earlyLeaveMinutes, $finalStatus];
    }

    // =========================================================
    //  Match face descriptor to any enrolled employee
    // =========================================================
    private function matchFaceToEmployee($probe)
    {
        $allFaces = Database::getInstance()->fetchAll(
            "SELECT ef.*, e.employee_code, e.first_name, e.last_name, e.photo, e.department_id,
                    d.name AS department
             FROM employee_faces ef
             INNER JOIN employees e ON e.id = ef.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE ef.is_active = 1 AND e.is_active = 1 AND e.face_enrolled = 1"
        );

        if (empty($allFaces)) {
            return ['matched' => false, 'score' => 0, 'confidence' => 0, 'distance' => 1.0];
        }

        $bestDist = 1.0;
        $bestFace = null;

        foreach ($allFaces as $face) {
            $stored = json_decode($face['descriptor'], true);
            if (!is_array($stored) || count($stored) !== 128) continue;
            $dist = $this->euclideanDistance($probe, $stored);
            if ($dist < $bestDist) { $bestDist = $dist; $bestFace = $face; }
        }

        $confidence = round((1 - $bestDist) * 100, 2);
        $matched    = $bestDist <= FACE_MATCH_THRESHOLD && $confidence >= FACE_MIN_CONFIDENCE;

        if (!$matched || !$bestFace) {
            return ['matched' => false, 'score' => $confidence, 'confidence' => $confidence, 'distance' => round($bestDist, 4)];
        }

        return [
            'matched'     => true,
            'employee_id' => (int)$bestFace['employee_id'],
            'score'       => $confidence,
            'confidence'  => $confidence,
            'distance'    => round($bestDist, 4),
            'employee'    => [
                'id'            => $bestFace['employee_id'],
                'employee_code' => $bestFace['employee_code'],
                'full_name'     => $bestFace['first_name'] . ' ' . $bestFace['last_name'],
                'photo'         => $bestFace['photo'],
                'department'    => $bestFace['department'] ?? null,
            ],
        ];
    }

    // =========================================================
    //  My Attendance (employee self-service)
    // =========================================================
    public function myAttendance()
    {
        $this->requireAuth();
        $empId = Auth::employeeId();

        if (!$empId) {
            Flash::set('error', 'No employee profile linked to your account.');
            $this->redirect('/dashboard');
        }

        $month      = $this->query('month', date('Y-m'));
        $startDate  = date('Y-m-01', strtotime($month));
        $endDate    = date('Y-m-t',  strtotime($month));

        $records = Database::getInstance()->fetchAll(
            "SELECT * FROM attendance
             WHERE employee_id = :eid AND attendance_date BETWEEN :s AND :e
             ORDER BY attendance_date DESC",
            ['eid' => $empId, 's' => $startDate, 'e' => $endDate]
        );

        $summary = $this->calculateSummary($records);

        // Today's record
        $todayRecord = Database::getInstance()->fetch(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
            ['eid' => $empId, 'd' => date('Y-m-d')]
        );

        // Monthly stats (last 6 months)
        $monthlyStats = Database::getInstance()->fetchAll(
            "SELECT DATE_FORMAT(attendance_date,'%Y-%m') as month,
                    COUNT(*) as total_days,
                    SUM(CASE WHEN status IN('present','late') THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status='late' THEN 1 ELSE 0 END) as late,
                    COALESCE(SUM(working_hours),0) as total_hours,
                    COALESCE(SUM(overtime_hours),0) as overtime,
                    COALESCE(SUM(late_minutes),0) as late_minutes
             FROM attendance
             WHERE employee_id = :eid AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
             GROUP BY month ORDER BY month ASC",
            ['eid' => $empId]
        );

        $this->view('attendance/my', [
            'title'        => 'My Attendance',
            'pageTitle'    => 'My Attendance Records',
            'records'      => $records,
            'summary'      => $summary,
            'month'        => $month,
            'todayRecord'  => $todayRecord,
            'monthlyStats' => $monthlyStats,
        ]);
    }

    // =========================================================
    //  Attendance Report
    // =========================================================
    public function report()
    {
        $this->requireAuth();
        $this->requirePermission('view_attendance');

        $startDate = $this->query('start_date', date('Y-m-01'));
        $endDate   = $this->query('end_date',   date('Y-m-d'));
        $deptId    = $this->query('department_id', '');
        $empId     = $this->query('employee_id', '');

        $sql = "SELECT e.id, e.employee_code, e.first_name, e.last_name, d.name AS department,
                       COUNT(a.id) as total_days,
                       SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present,
                       SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END) as late,
                       SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) as absent,
                       SUM(CASE WHEN a.status='leave' THEN 1 ELSE 0 END) as on_leave,
                       SUM(CASE WHEN a.status='half_day' THEN 1 ELSE 0 END) as half_day,
                       COALESCE(SUM(a.working_hours),0) as total_hours,
                       COALESCE(SUM(a.overtime_hours),0) as overtime,
                       COALESCE(SUM(a.late_minutes),0) as total_late_min,
                       COALESCE(SUM(a.early_leave_minutes),0) as total_early_min
                FROM employees e
                LEFT JOIN attendance a ON a.employee_id = e.id AND a.attendance_date BETWEEN :s AND :e
                LEFT JOIN departments d ON d.id = e.department_id
                WHERE e.is_active = 1";
        $params = ['s' => $startDate, 'e' => $endDate];

        if ($deptId) { $sql .= " AND e.department_id = :dept"; $params['dept'] = $deptId; }
        if ($empId)  { $sql .= " AND e.id = :eid"; $params['eid'] = $empId; }
        $sql .= " GROUP BY e.id ORDER BY e.first_name";

        $report      = Database::getInstance()->fetchAll($sql, $params);
        $departments = Database::getInstance()->fetchAll("SELECT * FROM departments WHERE is_active = 1");
        $employees   = Database::getInstance()->fetchAll("SELECT id, CONCAT(first_name,' ',last_name) AS name FROM employees WHERE is_active=1 ORDER BY first_name");

        $this->view('attendance/report', [
            'title'       => 'Attendance Report',
            'pageTitle'   => 'Attendance Report',
            'report'      => $report,
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'departments' => $departments,
            'employees'   => $employees,
            'deptId'      => $deptId,
            'empId'       => $empId,
            'csrf'        => CSRF::field(),
        ]);
    }

    // =========================================================
    //  Manual attendance add (admin)
    // =========================================================
    public function manualAdd()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_attendance');

        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('employee_id')->required('attendance_date')->required('status');

        if ($validator->fails()) {
            $this->json(['success' => false, 'message' => $validator->firstError()], 400);
        }

        $existing = Database::getInstance()->fetch(
            "SELECT id FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
            ['eid' => $data['employee_id'], 'd' => $data['attendance_date']]
        );

        if ($existing) {
            // Update existing record
            Database::getInstance()->update('attendance', [
                'check_in'        => $data['check_in'] ?? null,
                'check_out'       => $data['check_out'] ?? null,
                'check_in_method' => 'manual',
                'status'          => $data['status'],
                'notes'           => $data['notes'] ?? null,
            ], 'id = :id', ['id' => $existing['id']]);

            // Recalculate hours if both times provided
            if (!empty($data['check_in']) && !empty($data['check_out'])) {
                $checkIn  = new DateTime($data['attendance_date'] . ' ' . $data['check_in']);
                $checkOut = new DateTime($data['attendance_date'] . ' ' . $data['check_out']);
                $hrs = round(($checkOut->getTimestamp() - $checkIn->getTimestamp()) / 3600, 2);
                Database::getInstance()->update('attendance', ['working_hours' => $hrs], 'id = :id', ['id' => $existing['id']]);
            }

            try { Auth::audit('update', 'attendance', "Updated attendance for employee {$data['employee_id']}"); } catch (\Exception $e) {}
            $this->json(['success' => true, 'message' => 'Attendance updated']);
        }

        $id = Database::getInstance()->insert('attendance', [
            'employee_id'     => $data['employee_id'],
            'attendance_date' => $data['attendance_date'],
            'shift_id'        => $data['shift_id'] ?? null,
            'check_in'        => !empty($data['check_in']) ? $data['attendance_date'] . ' ' . $data['check_in'] : null,
            'check_out'       => !empty($data['check_out']) ? $data['attendance_date'] . ' ' . $data['check_out'] : null,
            'check_in_method' => 'manual',
            'status'          => $data['status'],
            'notes'           => $data['notes'] ?? null,
            'verified_by_face'=> 0,
        ]);

        try { Auth::audit('create', 'attendance', "Manually added attendance for employee {$data['employee_id']}"); } catch (\Exception $e) {}
        $this->json(['success' => true, 'message' => 'Attendance added', 'id' => $id]);
    }

    // =========================================================
    //  Delete attendance record (admin)
    // =========================================================
    public function delete($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_attendance');

        $record = Database::getInstance()->fetch("SELECT * FROM attendance WHERE id = :id", ['id' => $id]);
        if (!$record) $this->json(['success' => false, 'message' => 'Record not found'], 404);

        Database::getInstance()->query("DELETE FROM attendance WHERE id = :id", ['id' => $id]);
        try { Auth::audit('delete', 'attendance', "Deleted attendance record {$id}"); } catch (\Exception $e) {}
        $this->json(['success' => true, 'message' => 'Attendance record deleted']);
    }

    // =========================================================
    //  API: Today's attendance
    // =========================================================
    public function apiToday()
    {
        $this->requireAuth();
        $today = date('Y-m-d');
        $records = Database::getInstance()->fetchAll(
            "SELECT a.*, e.employee_code, e.first_name, e.last_name, e.photo
             FROM attendance a INNER JOIN employees e ON e.id = a.employee_id
             WHERE a.attendance_date = :today ORDER BY a.check_in DESC",
            ['today' => $today]
        );
        $this->json(['success' => true, 'data' => $records]);
    }

    // =========================================================
    //  API: Statistics
    // =========================================================
    public function apiStats()
    {
        $this->requireAuth();
        $today = date('Y-m-d');

        $totalEmployees = Database::getInstance()->count('employees', 'is_active = 1');
        $present = Database::getInstance()->count('attendance',
            'attendance_date = :d AND status IN("present","late","half_day","remote")', ['d' => $today]);
        $late    = Database::getInstance()->count('attendance',
            'attendance_date = :d AND status = "late"', ['d' => $today]);
        $absent  = Database::getInstance()->count('attendance',
            'attendance_date = :d AND status = "absent"', ['d' => $today]);
        $onLeave = Database::getInstance()->count('leave_requests',
            'status = "approved" AND :d BETWEEN start_date AND end_date', ['d' => $today]);
        $checkedOut = Database::getInstance()->count('attendance',
            'attendance_date = :d AND check_out IS NOT NULL', ['d' => $today]);

        $overtimeSummary = Database::getInstance()->fetch(
            "SELECT COALESCE(SUM(overtime_hours),0) as total_overtime,
                    COUNT(CASE WHEN overtime_hours > 0 THEN 1 END) as overtime_employees
             FROM attendance WHERE attendance_date = :d",
            ['d' => $today]
        );

        $attPct = $totalEmployees > 0 ? round(($present / $totalEmployees) * 100, 1) : 0;

        $this->json(['success' => true, 'data' => [
            'total_employees'     => $totalEmployees,
            'present'             => $present,
            'late'                => $late,
            'absent'              => $absent,
            'on_leave'            => $onLeave,
            'checked_out'         => $checkedOut,
            'attendance_pct'      => $attPct,
            'total_overtime'      => $overtimeSummary['total_overtime'] ?? 0,
            'overtime_employees'  => $overtimeSummary['overtime_employees'] ?? 0,
        ]]);
    }

    // =========================================================
    //  API: Attendance history for employee
    // =========================================================
    public function apiHistory()
    {
        $this->requireAuth();
        $empId = $this->query('employee_id', Auth::employeeId());
        $month = $this->query('month', date('Y-m'));
        $start = date('Y-m-01', strtotime($month));
        $end   = date('Y-m-t',  strtotime($month));

        $records = Database::getInstance()->fetchAll(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date BETWEEN :s AND :e ORDER BY attendance_date DESC",
            ['eid' => $empId, 's' => $start, 'e' => $end]
        );
        $this->json(['success' => true, 'data' => $records]);
    }

    // =========================================================
    //  Export attendance report
    // =========================================================
    public function exportReport()
    {
        $this->requireAuth();
        $this->requirePermission('view_attendance');

        $format    = $this->input('format', 'csv');
        $startDate = $this->input('start_date', date('Y-m-01'));
        $endDate   = $this->input('end_date',   date('Y-m-d'));
        $deptId    = $this->input('department_id', '');

        $sql = "SELECT e.employee_code, CONCAT(e.first_name,' ',e.last_name) AS name,
                       d.name AS department,
                       COUNT(a.id) as total_days,
                       SUM(CASE WHEN a.status='present' THEN 1 ELSE 0 END) as present,
                       SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END) as late,
                       SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) as absent,
                       SUM(CASE WHEN a.status='leave' THEN 1 ELSE 0 END) as on_leave,
                       COALESCE(SUM(a.working_hours),0) as total_hours,
                       COALESCE(SUM(a.overtime_hours),0) as overtime,
                       COALESCE(SUM(a.late_minutes),0) as late_minutes
                FROM employees e
                LEFT JOIN attendance a ON a.employee_id = e.id AND a.attendance_date BETWEEN :s AND :e
                LEFT JOIN departments d ON d.id = e.department_id
                WHERE e.is_active = 1";
        $params = ['s' => $startDate, 'e' => $endDate];
        if ($deptId) { $sql .= " AND e.department_id = :dept"; $params['dept'] = $deptId; }
        $sql .= " GROUP BY e.id ORDER BY e.first_name";

        $rows = Database::getInstance()->fetchAll($sql, $params);
        $headers = ['Code', 'Name', 'Department', 'Total Days', 'Present', 'Late', 'Absent', 'Leave', 'Total Hours', 'Overtime', 'Late Minutes'];
        $filename = 'attendance_' . $startDate . '_to_' . $endDate;

        if ($format === 'pdf') {
            $this->exportHtml($rows, $headers, $filename, "Attendance Report ({$startDate} to {$endDate})");
        } else {
            $excel = ($format === 'excel');
            header('Content-Type: ' . ($excel ? 'application/vnd.ms-excel' : 'text/csv'));
            header('Content-Disposition: attachment; filename="' . $filename . '.' . ($excel ? 'xls' : 'csv') . '"');
            $out = fopen('php://output', 'w');
            fputcsv($out, $headers);
            foreach ($rows as $row) fputcsv($out, array_values($row));
            fclose($out);
            exit;
        }
    }

    private function exportHtml($rows, $headers, $filename, $title)
    {
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.html"');
        echo '<html><head><title>' . $title . '</title><style>body{font-family:sans-serif;font-size:12px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #ccc;padding:6px}th{background:#334155;color:#fff}</style></head><body>';
        echo '<h2>' . htmlspecialchars($title) . '</h2>';
        echo '<table><thead><tr>';
        foreach ($headers as $h) echo '<th>' . htmlspecialchars($h) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($rows as $row) {
            echo '<tr>';
            foreach (array_values($row) as $v) echo '<td>' . htmlspecialchars((string)$v) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table></body></html>';
        exit;
    }

    // =========================================================
    //  Helpers
    // =========================================================
    private function logAttendanceEvent($empId, $attId, $type, $time, $lat, $lng, $score, $ip)
    {
        try {
            Database::getInstance()->insert('attendance_logs', [
                'employee_id'      => $empId,
                'attendance_id'    => $attId,
                'event_type'       => $type,
                'event_time'       => $time,
                'ip_address'       => $ip,
                'user_agent'       => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'latitude'         => $lat,
                'longitude'        => $lng,
                'face_match_score' => $score,
            ]);
        } catch (\Exception $e) {}
    }

    private function verifyGeofence($branchId, $lat, $lng)
    {
        if (!$branchId || !$lat || !$lng) return 0;
        $branch = Database::getInstance()->fetch(
            "SELECT latitude, longitude, geofence_radius FROM branches WHERE id = :id",
            ['id' => $branchId]
        );
        if (!$branch || !$branch['latitude']) return 0;
        $distance = $this->haversineDistance($lat, $lng, $branch['latitude'], $branch['longitude']);
        return $distance <= $branch['geofence_radius'] ? 1 : 0;
    }

    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $R = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    private function euclideanDistance($a, $b)
    {
        $sum = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $d = $a[$i] - $b[$i];
            $sum += $d * $d;
        }
        return sqrt($sum);
    }

    private function calculateSummary($records)
    {
        $summary = ['present' => 0, 'late' => 0, 'absent' => 0, 'leave' => 0, 'half_day' => 0, 'holiday' => 0, 'weekend' => 0, 'remote' => 0];
        $totalHours = 0; $totalLate = 0; $totalOvertime = 0;
        foreach ($records as $r) {
            if (isset($summary[$r['status']])) $summary[$r['status']]++;
            $totalHours    += (float)($r['working_hours']   ?? 0);
            $totalLate     += (int)  ($r['late_minutes']    ?? 0);
            $totalOvertime += (float)($r['overtime_hours']  ?? 0);
        }
        $summary['total_hours']         = round($totalHours, 2);
        $summary['total_late_minutes']  = $totalLate;
        $summary['total_overtime']      = round($totalOvertime, 2);
        return $summary;
    }


    // ── Fraud detection: log unknown faces ──────────────────────
    private function logFraudAttempt($probe, $matchResult, $lat, $lng, $snapshot)
    {
        $ip  = $this->clientIp();
        $dir = UPLOAD_PATH . "/fraud_faces/" . date("Y/m");
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $imgPath = null;
        if ($snapshot && preg_match("/^data:image\/\w+;base64,/", $snapshot)) {
            $data = base64_decode(preg_replace("/^data:image\/\w+;base64,/", "", $snapshot));
            if ($data) {
                $fname    = "fraud_" . time() . "_" . mt_rand(1000,9999) . ".jpg";
                file_put_contents($dir . "/" . $fname, $data);
                $imgPath  = "fraud_faces/" . date("Y/m") . "/" . $fname;
            }
        }
        try {
            Database::getInstance()->insert("attendance_fraud_log", [
                "attempt_time"    => date("Y-m-d H:i:s"),
                "ip_address"      => $ip,
                "latitude"        => $lat,
                "longitude"       => $lng,
                "match_score"     => $matchResult["score"] ?? 0,
                "closest_employee"=> $matchResult["employee"]["full_name"] ?? null,
                "snapshot_path"   => $imgPath,
                "user_agent"      => $_SERVER["HTTP_USER_AGENT"] ?? null,
            ]);
        } catch (\Exception $e) {}
    }

    // ── Save attendance snapshot (selfie) ────────────────────────
    private function saveAttendanceSnapshot($employeeId, $snapshot, $attendanceId)
    {
        if (!$snapshot || !preg_match("/^data:image\/\w+;base64,/", $snapshot)) return;
        $data = base64_decode(preg_replace("/^data:image\/\w+;base64,/", "", $snapshot));
        if (!$data) return;
        $dir  = UPLOAD_PATH . "/attendance_snaps/" . date("Y/m");
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fname = "snap_" . $employeeId . "_" . time() . ".jpg";
        file_put_contents($dir . "/" . $fname, $data);
        if ($attendanceId) {
            try {
                Database::getInstance()->update("attendance", [
                    "snapshot_path" => "attendance_snaps/" . date("Y/m") . "/" . $fname,
                ], "id = :id", ["id" => $attendanceId]);
            } catch (\Exception $e) {}
        }
    }

    private function sendLateNotification($employeeId, $lateMinutes)
    {
        try {
            $user = Database::getInstance()->fetch("SELECT id FROM users WHERE employee_id = :eid", ['eid' => $employeeId]);
            if ($user) {
                Database::getInstance()->insert('notifications', [
                    'user_id'     => $user['id'],
                    'employee_id' => $employeeId,
                    'type'        => 'attendance',
                    'title'       => 'Late Arrival',
                    'message'     => "You arrived {$lateMinutes} minutes late today.",
                    'channel'     => 'in_app',
                    'status'      => 'sent',
                    'sent_at'     => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {}
    }

    private function clientIp()
    {
        $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'REMOTE_ADDR'];
        foreach ($keys as $k) {
            if (!empty($_SERVER[$k])) return explode(',', $_SERVER[$k])[0];
        }
        return '0.0.0.0';
    }
}
