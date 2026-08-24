<?php
/**
 * API Attendance Controller — RESTful JSON endpoints
 * All responses are JSON. Auth via session or API token.
 */

namespace Api;

class Attendance extends \Controller
{
    // POST /api/attendance/check-in
    public function checkIn()
    {
        $this->requireAuth();
        $this->validateCsrf();

        $employeeId = (int) $this->input('employee_id');
        $lat        = $this->input('latitude', null);
        $lng        = $this->input('longitude', null);
        $matchScore = (float) $this->input('match_score', 0);

        if (!$employeeId) {
            $this->json(['success' => false, 'message' => 'employee_id required'], 400);
        }

        // Delegate to main controller logic via shared method
        $ctrl = new \AttendanceController();
        $result = $ctrl->processCheckInPublic($employeeId, $lat, $lng, $matchScore, 'face');
        $this->json($result, $result['success'] ? 200 : 400);
    }

    // POST /api/attendance/check-out
    public function checkOut()
    {
        $this->requireAuth();
        $this->validateCsrf();

        $employeeId = (int) $this->input('employee_id');
        $lat        = $this->input('latitude', null);
        $lng        = $this->input('longitude', null);
        $matchScore = (float) $this->input('match_score', 0);

        if (!$employeeId) {
            $this->json(['success' => false, 'message' => 'employee_id required'], 400);
        }

        $ctrl = new \AttendanceController();
        $result = $ctrl->processCheckOutPublic($employeeId, $lat, $lng, $matchScore, 'face');
        $this->json($result, $result['success'] ? 200 : 400);
    }

    // POST /api/attendance/face-scan (combined auto check-in/out)
    public function faceScan()
    {
        $this->requireAuth();
        $this->validateCsrf();

        $descriptor = $this->input('descriptor');
        $antiSpoof  = $this->input('anti_spoof', []);
        $lat        = $this->input('latitude', null);
        $lng        = $this->input('longitude', null);

        if (!$descriptor) {
            $this->json(['success' => false, 'message' => 'descriptor required'], 400);
        }

        // Validate anti-spoof
        if (is_string($antiSpoof)) $antiSpoof = json_decode($antiSpoof, true) ?? [];
        if (($antiSpoof['blink_count'] ?? 0) < 2) {
            $this->json(['success' => false, 'message' => 'Liveness check failed'], 403);
        }

        $probe = json_decode($descriptor, true);
        if (!is_array($probe) || count($probe) !== 128) {
            $this->json(['success' => false, 'message' => 'Invalid descriptor'], 400);
        }

        // Match face
        $ctrl   = new \AttendanceController();
        $result = $ctrl->matchFacePublic($probe);

        if (!$result['matched']) {
            $this->json(['success' => false, 'message' => 'Face not recognized', 'score' => $result['score'] ?? 0]);
        }

        $empId  = $result['employee_id'];
        $today  = date('Y-m-d');
        $todayRec = \Database::getInstance()->fetch(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
            ['eid' => $empId, 'd' => $today]
        );

        if (!$todayRec || !$todayRec['check_in']) {
            $res = $ctrl->processCheckInPublic($empId, $lat, $lng, $result['score'], 'face');
        } elseif (!$todayRec['check_out']) {
            $res = $ctrl->processCheckOutPublic($empId, $lat, $lng, $result['score'], 'face');
        } else {
            $res = ['success' => true, 'already_done' => true, 'message' => 'Already completed check-in and check-out today.'];
        }

        $res['employee'] = $result['employee'] ?? null;
        $this->json($res);
    }

    // GET /api/attendance/today
    public function today()
    {
        $this->requireAuth();
        $today = date('Y-m-d');
        $records = \Database::getInstance()->fetchAll(
            "SELECT a.*, e.employee_code, e.first_name, e.last_name, e.photo,
                    d.name AS department_name
             FROM attendance a
             INNER JOIN employees e ON e.id = a.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE a.attendance_date = :today ORDER BY a.check_in DESC",
            ['today' => $today]
        );
        $this->json(['success' => true, 'date' => $today, 'count' => count($records), 'data' => $records]);
    }

    // GET /api/attendance/history
    public function history()
    {
        $this->requireAuth();
        $empId = $this->query('employee_id', \Auth::employeeId());
        $month = $this->query('month', date('Y-m'));
        $start = date('Y-m-01', strtotime($month));
        $end   = date('Y-m-t',  strtotime($month));

        $records = \Database::getInstance()->fetchAll(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date BETWEEN :s AND :e ORDER BY attendance_date DESC",
            ['eid' => $empId, 's' => $start, 'e' => $end]
        );
        $this->json(['success' => true, 'month' => $month, 'count' => count($records), 'data' => $records]);
    }

    // GET /api/attendance/stats
    public function stats()
    {
        $this->requireAuth();
        $today = date('Y-m-d');
        $db    = \Database::getInstance();

        $total    = $db->count('employees', 'is_active = 1');
        $present  = $db->count('attendance', 'attendance_date = :d AND status IN("present","late","half_day","remote")', ['d' => $today]);
        $late     = $db->count('attendance', 'attendance_date = :d AND status = "late"', ['d' => $today]);
        $absent   = $db->count('attendance', 'attendance_date = :d AND status = "absent"', ['d' => $today]);
        $checkedIn  = $db->count('attendance', 'attendance_date = :d AND check_in IS NOT NULL', ['d' => $today]);
        $checkedOut = $db->count('attendance', 'attendance_date = :d AND check_out IS NOT NULL', ['d' => $today]);
        $ot = $db->fetch("SELECT COALESCE(SUM(overtime_hours),0) as ot FROM attendance WHERE attendance_date = :d", ['d' => $today]);

        $this->json(['success' => true, 'date' => $today, 'data' => [
            'total_employees' => $total,
            'present'         => $present,
            'late'            => $late,
            'absent'          => $absent,
            'not_checked_in'  => $total - $checkedIn,
            'checked_in'      => $checkedIn,
            'checked_out'     => $checkedOut,
            'still_working'   => $checkedIn - $checkedOut,
            'total_overtime'  => $ot['ot'] ?? 0,
            'attendance_pct'  => $total > 0 ? round(($present / $total) * 100, 1) : 0,
        ]]);
    }

    // GET /api/attendance/reports
    public function reports()
    {
        $this->requireAuth();
        $this->requirePermission('view_attendance');

        $startDate = $this->query('start_date', date('Y-m-01'));
        $endDate   = $this->query('end_date',   date('Y-m-d'));
        $deptId    = $this->query('department_id', '');

        $sql = "SELECT e.employee_code, CONCAT(e.first_name,' ',e.last_name) AS name,
                       d.name AS department,
                       COUNT(a.id) as total_days,
                       SUM(CASE WHEN a.status IN('present','late') THEN 1 ELSE 0 END) as present,
                       SUM(CASE WHEN a.status='late' THEN 1 ELSE 0 END) as late,
                       SUM(CASE WHEN a.status='absent' THEN 1 ELSE 0 END) as absent,
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

        $data = \Database::getInstance()->fetchAll($sql, $params);
        $this->json(['success' => true, 'start_date' => $startDate, 'end_date' => $endDate, 'data' => $data]);
    }
}
