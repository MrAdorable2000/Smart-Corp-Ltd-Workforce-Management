<?php
/**
 * Mobile Attendance Controller
 * GPS + selfie based attendance for mobile employees
 */
class MobileAttendanceController extends Controller
{
    // ── Mobile check-in page ──────────────────────────────────────────
    public function index()
    {
        $this->requireAuth();
        $empId = Auth::employeeId();
        if (!$empId) { Flash::set('error', 'No employee profile linked.'); $this->redirect('/dashboard'); }

        $employee  = Database::getInstance()->fetch(
            "SELECT e.*, b.name AS branch_name, b.latitude AS branch_lat, b.longitude AS branch_lng, b.geofence_radius
             FROM employees e
             LEFT JOIN branches b ON b.id = e.branch_id
             WHERE e.id = :id",
            ['id' => $empId]
        );

        $todayRecord = Database::getInstance()->fetch(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
            ['eid' => $empId, 'd' => date('Y-m-d')]
        );

        $this->view('attendance/mobile', [
            'title'       => 'Mobile Attendance',
            'pageTitle'   => 'Mobile Check-In / Check-Out',
            'employee'    => $employee,
            'todayRecord' => $todayRecord,
            'csrf'        => CSRF::field(),
        ]);
    }

    // ── Process mobile attendance (AJAX) ──────────────────────────────
    public function process()
    {
        $this->requireAuth();
        $this->validateCsrf();

        $empId   = Auth::employeeId();
        if (!$empId) $this->json(['success' => false, 'message' => 'No employee profile'], 400);

        $lat     = (float)($this->input('latitude',  0));
        $lng     = (float)($this->input('longitude', 0));
        $accuracy= (float)($this->input('accuracy',  999));
        $action  = $this->input('action', 'auto'); // auto|check_in|check_out
        $selfie  = $this->input('selfie', '');     // base64 image data

        if (!$lat || !$lng) {
            $this->json(['success' => false, 'message' => 'GPS location is required for mobile attendance'], 400);
        }

        // GPS accuracy check
        if ($accuracy > 100) {
            $this->json(['success' => false, 'message' => 'Poor GPS accuracy (' . round($accuracy) . 'm). Please move to an open area.'], 400);
        }

        // Load employee + branch geofence
        $employee = Database::getInstance()->fetch(
            "SELECT e.*, b.latitude AS blat, b.longitude AS blng, b.geofence_radius AS bradius, b.name AS branch_name
             FROM employees e LEFT JOIN branches b ON b.id = e.branch_id WHERE e.id = :id",
            ['id' => $empId]
        );

        // Geofence check
        $geofencePassed  = true;
        $distanceFromOffice = null;
        if ($employee && $employee['blat'] && $employee['blng']) {
            $distanceFromOffice = $this->haversine($lat, $lng, $employee['blat'], $employee['blng']);
            $radius = (int)($employee['bradius'] ?? MOBILE_GPS_RADIUS_DEFAULT);
            if ($distanceFromOffice > $radius) {
                $geofencePassed = false;
                $this->json([
                    'success'  => false,
                    'message'  => sprintf(
                        'You are %.0fm away from the office. Attendance is only allowed within %dm radius.',
                        $distanceFromOffice,
                        $radius
                    ),
                    'distance' => round($distanceFromOffice),
                    'radius'   => $radius,
                    'geofence_failed' => true,
                ], 403);
            }
        }

        // Save selfie
        $selfiePath = null;
        if ($selfie && preg_match('/^data:image\/(\w+);base64,/', $selfie, $m)) {
            $ext  = strtolower($m[1]);
            $data = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $selfie));
            if ($data) {
                $dir = UPLOAD_PATH . '/selfies/' . date('Y/m');
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'selfie_' . $empId . '_' . time() . '.' . $ext;
                file_put_contents($dir . '/' . $fname, $data);
                $selfiePath = 'selfies/' . date('Y/m') . '/' . $fname;
            }
        }

        // Determine action
        $today    = date('Y-m-d');
        $todayRec = Database::getInstance()->fetch(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
            ['eid' => $empId, 'd' => $today]
        );

        if ($action === 'auto') {
            $action = (!$todayRec || !$todayRec['check_in']) ? 'check_in' : 'check_out';
        }

        if ($action === 'check_out' && (!$todayRec || !$todayRec['check_in'])) {
            $this->json(['success' => false, 'message' => 'You have not checked in today yet.'], 400);
        }
        if ($action === 'check_out' && $todayRec && $todayRec['check_out']) {
            $this->json(['success' => false, 'message' => 'You have already checked out today.'], 409);
        }

        // Process attendance
        $ctrl   = new AttendanceController();
        $result = $action === 'check_in'
            ? $ctrl->processCheckInPublic($empId, $lat, $lng, 100, 'gps')
            : $ctrl->processCheckOutPublic($empId, $lat, $lng, 100, 'gps');

        if (!$result['success']) {
            $this->json($result, 400);
        }

        // Save mobile attendance record
        $attId = $result['attendance_id'] ?? ($todayRec['id'] ?? null);
        if (!$attId) {
            $rec  = Database::getInstance()->fetch(
                "SELECT id FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
                ['eid' => $empId, 'd' => $today]
            );
            $attId = $rec['id'] ?? null;
        }

        if ($attId) {
            Database::getInstance()->insert('mobile_attendance', [
                'attendance_id'       => $attId,
                'employee_id'         => $empId,
                'type'                => $action,
                'selfie_path'         => $selfiePath,
                'latitude'            => $lat,
                'longitude'           => $lng,
                'accuracy'            => $accuracy,
                'distance_from_office'=> $distanceFromOffice,
                'geofence_passed'     => $geofencePassed ? 1 : 0,
                'branch_id'           => $employee['branch_id'] ?? null,
                'device_info'         => $_SERVER['HTTP_USER_AGENT'] ?? null,
            ]);
        }

        $result['distance']      = $distanceFromOffice ? round($distanceFromOffice) : null;
        $result['geofence']      = $geofencePassed;
        $result['selfie_saved']  = !empty($selfiePath);
        $this->json($result);
    }

    private function haversine($lat1, $lng1, $lat2, $lng2): float
    {
        $R    = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat/2)**2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng/2)**2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
