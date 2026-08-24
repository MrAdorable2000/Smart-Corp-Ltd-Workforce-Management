<?php
/**
 * Face Recognition Controller - Enterprise v2
 * Handles face enrollment, verification, matching
 * NOTE: match() is public (no auth) to support kiosk attendance
 */
class FaceController extends Controller
{
    // ── Enroll page ─────────────────────────────────────────────────
    public function enroll($employeeId)
    {
        $this->requireAuth();
        $this->requirePermission('manage_face_data');

        $employee = Database::getInstance()->fetch(
            "SELECT e.*, CONCAT(e.first_name,' ',e.last_name) AS full_name
             FROM employees e WHERE e.id = :id",
            ['id' => $employeeId]
        );
        if (!$employee) { Flash::set('error','Employee not found.'); $this->redirect('/employees'); }

        $existingFaces = Database::getInstance()->fetchAll(
            "SELECT * FROM employee_faces WHERE employee_id = :id AND is_active = 1 ORDER BY created_at DESC",
            ['id' => $employeeId]
        );

        $this->view('face/enroll', [
            'title'         => 'Face Enrollment',
            'pageTitle'     => 'Enroll Face: ' . $employee['full_name'],
            'employee'      => $employee,
            'existingFaces' => $existingFaces,
            'csrf'          => CSRF::field(),
            'extraJs'       => [
                'https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js',
                ASSET_URL . '/js/face-recognition.js',
            ],
        ]);
    }

    // ── Store face descriptor ────────────────────────────────────────
    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_face_data');

        $employeeId = (int)$this->input('employee_id');
        $descriptor = $this->input('descriptor');
        $imageData  = $this->input('image', '');
        $label      = $this->input('label', 'front');

        if (!$employeeId || !$descriptor) {
            $this->json(['success' => false, 'message' => 'Missing required data'], 400);
        }

        // Validate descriptor is 128-element JSON array
        $parsed = json_decode($descriptor, true);
        if (!is_array($parsed) || count($parsed) !== 128) {
            $this->json(['success' => false, 'message' => 'Invalid face descriptor (must be 128-element array)'], 400);
        }

        $employee = Database::getInstance()->fetch("SELECT * FROM employees WHERE id = :id", ['id' => $employeeId]);
        if (!$employee) $this->json(['success' => false, 'message' => 'Employee not found'], 404);

        // Save face image
        $imagePath = null;
        if ($imageData) {
            $raw = preg_replace('/^data:image\/\w+;base64,/', '', $imageData);
            $decoded = base64_decode($raw);
            if ($decoded && strlen($decoded) > 100) {
                $dir = UPLOAD_PATH . '/faces';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $fname = 'face_' . $employeeId . '_' . time() . '.jpg';
                file_put_contents($dir . '/' . $fname, $decoded);
                $imagePath = 'faces/' . $fname;
            }
        }

        // Check if this is the first face for this employee
        $existingCount = Database::getInstance()->count(
            'employee_faces', 'employee_id = :eid', ['eid' => $employeeId]
        );

        $faceId = Database::getInstance()->insert('employee_faces', [
            'employee_id' => $employeeId,
            'descriptor'  => $descriptor,
            'label'       => $label,
            'image_path'  => $imagePath,
            'is_primary'  => ($existingCount === 0) ? 1 : 0,
            'is_active'   => 1,
        ]);

        Database::getInstance()->update('employees', ['face_enrolled' => 1], 'id = :id', ['id' => $employeeId]);

        try {
            Auth::audit('face_enroll', 'face', "Enrolled face for employee {$employee['employee_code']}", null, ['face_id' => $faceId]);
        } catch (\Exception $e) {}

        $this->json([
            'success'    => true,
            'message'    => 'Face enrolled successfully! Employee can now use face recognition attendance.',
            'face_id'    => $faceId,
            'image_path' => $imagePath,
            'total_faces'=> $existingCount + 1,
        ]);
    }

    // ── Verify face against ONE employee ────────────────────────────
    public function verify()
    {
        $this->requireAuth();
        $this->validateCsrf();

        $employeeId = (int)$this->input('employee_id');
        $descriptor = $this->input('descriptor');
        if (!$employeeId || !$descriptor) {
            $this->json(['success' => false, 'message' => 'Missing required data'], 400);
        }

        $probe = json_decode($descriptor, true);
        if (!is_array($probe) || count($probe) !== 128) {
            $this->json(['success' => false, 'message' => 'Invalid descriptor format'], 400);
        }

        $faces = Database::getInstance()->fetchAll(
            "SELECT * FROM employee_faces WHERE employee_id = :id AND is_active = 1",
            ['id' => $employeeId]
        );
        if (empty($faces)) {
            $this->json(['success' => false, 'message' => 'No face data enrolled for this employee'], 404);
        }

        $bestDist = 1.0;
        foreach ($faces as $face) {
            $stored = json_decode($face['descriptor'], true);
            if (!is_array($stored) || count($stored) !== 128) continue;
            $d = $this->euclideanDistance($probe, $stored);
            if ($d < $bestDist) $bestDist = $d;
        }

        $confidence = round((1 - $bestDist) * 100, 2);
        $matched    = $bestDist <= FACE_MATCH_THRESHOLD && $confidence >= FACE_MIN_CONFIDENCE;

        $this->logFaceEvent($employeeId, null, $matched ? 'face_detected' : 'face_mismatch', $bestDist);

        $this->json([
            'success'    => $matched,
            'matched'    => $matched,
            'confidence' => $confidence,
            'distance'   => round($bestDist, 4),
            'message'    => $matched
                ? "Face verified ({$confidence}% confidence)"
                : "Face did not match ({$confidence}% — minimum 90% required)",
        ]);
    }

    // ── Match face against ALL enrolled employees ────────────────────
    // PUBLIC — no login required (used by kiosk AND face_scan page)
    public function match()
    {
        $this->validateCsrf();

        $descriptor = $this->input('descriptor');
        $antiSpoof  = $this->input('anti_spoof', '{}');
        $snapshot   = $this->input('snapshot', '');

        if (!$descriptor) {
            $this->json(['success' => false, 'message' => 'Missing descriptor'], 400);
        }

        $probe = json_decode($descriptor, true);
        if (!is_array($probe) || count($probe) !== 128) {
            $this->json(['success' => false, 'message' => 'Invalid descriptor (expected 128-float array)'], 400);
        }

        // Parse anti-spoof data
        $spoof = is_string($antiSpoof) ? (json_decode($antiSpoof, true) ?? []) : $antiSpoof;

        // Validate liveness (relaxed — only require blink)
        $blinkOk     = (int)($spoof['blink_count'] ?? 0) >= 1;
        $livenessOk  = ($spoof['liveness_passed'] ?? false) || $blinkOk;

        if (!$livenessOk) {
            $this->logFaceEvent(null, null, 'spoof_detected', 1.0);
            $this->json([
                'success' => false,
                'message' => 'Liveness check failed. Please blink naturally while looking at the camera.',
            ], 403);
        }

        // Load all enrolled faces
        $allFaces = Database::getInstance()->fetchAll(
            "SELECT ef.id AS face_id, ef.employee_id, ef.descriptor,
                    e.employee_code, e.first_name, e.last_name, e.photo,
                    e.is_active AS emp_active, e.face_enrolled,
                    d.name AS department,
                    s.start_time, s.end_time, s.grace_period_minutes, s.name AS shift_name
             FROM employee_faces ef
             INNER JOIN employees e ON e.id = ef.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN shifts s ON s.id = e.shift_id
             WHERE ef.is_active = 1 AND e.is_active = 1 AND e.face_enrolled = 1"
        );

        if (empty($allFaces)) {
            $this->json(['success' => false, 'message' => 'No employees enrolled for face recognition. Ask admin to enroll faces.'], 404);
        }

        // Find best match
        $bestDist = 1.0;
        $bestFace = null;
        foreach ($allFaces as $face) {
            $stored = json_decode($face['descriptor'], true);
            if (!is_array($stored) || count($stored) !== 128) continue;
            $d = $this->euclideanDistance($probe, $stored);
            if ($d < $bestDist) { $bestDist = $d; $bestFace = $face; }
        }

        $confidence = round((1 - $bestDist) * 100, 2);
        $matched    = $bestDist <= FACE_MATCH_THRESHOLD && $confidence >= FACE_MIN_CONFIDENCE;

        if (!$matched) {
            // Log fraud attempt
            $this->logFaceEvent(null, null, 'face_mismatch', $bestDist);
            if (FACE_FRAUD_LOG) $this->saveFraudSnapshot($snapshot, $confidence);

            $msg = $confidence > 70
                ? "Low confidence ({$confidence}% — minimum 90% required). Please re-enroll your face."
                : ($confidence > 40
                    ? "Face partially recognized ({$confidence}%). Please look directly at the camera."
                    : 'Face not recognized. Please enroll first or contact HR.');

            $this->json(['success' => false, 'matched' => false, 'confidence' => $confidence, 'message' => $msg]);
        }

        // Log successful match
        $this->logFaceEvent($bestFace['employee_id'], null, 'face_detected', $bestDist);

        $this->json([
            'success'    => true,
            'matched'    => true,
            'confidence' => $confidence,
            'score'      => $confidence,
            'distance'   => round($bestDist, 4),
            'employee'   => [
                'id'            => (int)$bestFace['employee_id'],
                'employee_code' => $bestFace['employee_code'],
                'full_name'     => $bestFace['first_name'] . ' ' . $bestFace['last_name'],
                'department'    => $bestFace['department'],
                'shift_name'    => $bestFace['shift_name'],
                'start_time'    => $bestFace['start_time'],
                'end_time'      => $bestFace['end_time'],
                'photo'         => $bestFace['photo'],
            ],
        ]);
    }

    // ── Get all descriptors (client-side matching) ───────────────────
    public function descriptors()
    {
        // Accessible to logged-in users for face_scan page pre-loading
        if (!Auth::check()) {
            $this->json(['success' => false, 'message' => 'Authentication required'], 401);
        }

        $faces = Database::getInstance()->fetchAll(
            "SELECT ef.id, ef.employee_id, ef.descriptor,
                    e.employee_code, CONCAT(e.first_name,' ',e.last_name) AS name, e.photo
             FROM employee_faces ef
             INNER JOIN employees e ON e.id = ef.employee_id
             WHERE ef.is_active = 1 AND e.is_active = 1 AND e.face_enrolled = 1"
        );

        $this->json([
            'success'   => true,
            'threshold' => FACE_MATCH_THRESHOLD,
            'min_confidence' => FACE_MIN_CONFIDENCE,
            'count'     => count($faces),
            'data'      => $faces,
        ]);
    }

    // ── Delete face descriptor ───────────────────────────────────────
    public function destroy($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_face_data');

        $face = Database::getInstance()->fetch("SELECT * FROM employee_faces WHERE id = :id", ['id' => $id]);
        if (!$face) $this->json(['success' => false, 'message' => 'Face record not found'], 404);

        Database::getInstance()->update('employee_faces', ['is_active' => 0], 'id = :id', ['id' => $id]);

        // If no more active faces, clear face_enrolled flag
        $remaining = Database::getInstance()->count('employee_faces', 'employee_id = :eid AND is_active = 1', ['eid' => $face['employee_id']]);
        if ($remaining === 0) {
            Database::getInstance()->update('employees', ['face_enrolled' => 0], 'id = :id', ['id' => $face['employee_id']]);
        }

        try { Auth::audit('delete', 'face', "Deleted face descriptor ID {$id}"); } catch (\Exception $e) {}
        $this->json(['success' => true, 'message' => 'Face data deleted']);
    }

    // ── Helpers ──────────────────────────────────────────────────────
    private function euclideanDistance(array $a, array $b): float
    {
        $sum = 0.0;
        for ($i = 0; $i < 128; $i++) {
            $d = ($a[$i] ?? 0) - ($b[$i] ?? 0);
            $sum += $d * $d;
        }
        return sqrt($sum);
    }

    private function logFaceEvent(?int $empId, ?int $attId, string $type, float $dist): void
    {
        try {
            Database::getInstance()->insert('attendance_logs', [
                'employee_id'      => $empId,
                'attendance_id'    => $attId,
                'event_type'       => $type,
                'event_time'       => date('Y-m-d H:i:s'),
                'ip_address'       => Auth::clientIp(),
                'user_agent'       => Auth::userAgent(),
                'face_match_score' => min(1.0, max(0.0, round(1 - $dist, 4))),
            ]);
        } catch (\Exception $e) {}
    }

    private function saveFraudSnapshot(string $snapshot, float $confidence): void
    {
        if (!$snapshot || !preg_match('/^data:image\/\w+;base64,/', $snapshot)) return;
        try {
            $data = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $snapshot));
            if (!$data || strlen($data) < 100) return;
            $dir  = UPLOAD_PATH . '/fraud_faces/' . date('Y/m');
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $fname = 'fraud_' . time() . '_' . mt_rand(1000,9999) . '.jpg';
            file_put_contents($dir . '/' . $fname, $data);
            Database::getInstance()->insert('attendance_fraud_log', [
                'attempt_time'     => date('Y-m-d H:i:s'),
                'ip_address'       => Auth::clientIp(),
                'match_score'      => $confidence,
                'snapshot_path'    => 'fraud_faces/' . date('Y/m') . '/' . $fname,
                'user_agent'       => Auth::userAgent(),
            ]);
        } catch (\Exception $e) {}
    }
}
