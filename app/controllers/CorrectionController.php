<?php
/**
 * Attendance Correction Controller
 * Employees request corrections; managers approve/reject
 */
class CorrectionController extends Controller
{
    // ── Employee: submit correction request ──────────────────────────
    public function create()
    {
        $this->requireAuth();
        $this->validateCsrf();

        $empId = Auth::employeeId();
        if (!$empId) $this->json(['success' => false, 'message' => 'No employee profile linked'], 400);

        $data = $this->input();
        $v    = new Validator($data);
        $v->required('attendance_date')->required('reason');
        if ($v->fails()) $this->json(['success' => false, 'message' => $v->firstError()], 422);

        $date   = $data['attendance_date'];
        $attRec = Database::getInstance()->fetch(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
            ['eid' => $empId, 'd' => $date]
        );

        // Prevent duplicate pending request
        $existing = Database::getInstance()->fetch(
            "SELECT id FROM attendance_corrections WHERE employee_id = :eid AND attendance_date = :d AND status = 'pending'",
            ['eid' => $empId, 'd' => $date]
        );
        if ($existing) $this->json(['success' => false, 'message' => 'You already have a pending correction for this date'], 409);

        $id = Database::getInstance()->insert('attendance_corrections', [
            'employee_id'          => $empId,
            'attendance_id'        => $attRec['id'] ?? null,
            'attendance_date'      => $date,
            'requested_check_in'   => !empty($data['requested_check_in'])  ? $date . ' ' . $data['requested_check_in']  : null,
            'requested_check_out'  => !empty($data['requested_check_out']) ? $date . ' ' . $data['requested_check_out'] : null,
            'current_check_in'     => $attRec['check_in']  ?? null,
            'current_check_out'    => $attRec['check_out'] ?? null,
            'reason'               => htmlspecialchars($data['reason']),
        ]);

        // Notify managers
        $this->notifyManagers($empId, $date, $id);

        try { Auth::audit('create', 'attendance_correction', "Employee {$empId} requested correction for {$date}"); } catch (\Exception $e) {}

        $this->json(['success' => true, 'message' => 'Correction request submitted. A manager will review it shortly.', 'id' => $id]);
    }

    // ── Employee: view own corrections ────────────────────────────────
    public function myRequests()
    {
        $this->requireAuth();
        $empId = Auth::employeeId();
        if (!$empId) { Flash::set('error', 'No employee profile.'); $this->redirect('/dashboard'); }

        $corrections = Database::getInstance()->fetchAll(
            "SELECT c.*, u.name AS reviewer_name
             FROM attendance_corrections c
             LEFT JOIN users u ON u.id = c.reviewed_by
             WHERE c.employee_id = :eid
             ORDER BY c.created_at DESC LIMIT 50",
            ['eid' => $empId]
        );

        $this->view('attendance/corrections', [
            'title'       => 'Correction Requests',
            'pageTitle'   => 'My Correction Requests',
            'corrections' => $corrections,
            'csrf'        => CSRF::field(),
        ]);
    }

    // ── Admin/HR: list all pending corrections ────────────────────────
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('manage_attendance');

        $status = $this->query('status', 'pending');
        $params = [];
        $where  = '1=1';
        if ($status !== 'all') { $where .= ' AND c.status = :s'; $params['s'] = $status; }

        $corrections = Database::getInstance()->fetchAll(
            "SELECT c.*, e.employee_code, e.first_name, e.last_name, e.photo,
                    d.name AS department_name, u.name AS reviewer_name
             FROM attendance_corrections c
             INNER JOIN employees e ON e.id = c.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN users u ON u.id = c.reviewed_by
             WHERE {$where}
             ORDER BY c.created_at DESC",
            $params
        );

        $this->view('attendance/corrections_admin', [
            'title'       => 'Correction Requests',
            'pageTitle'   => 'Attendance Corrections',
            'corrections' => $corrections,
            'status'      => $status,
            'csrf'        => CSRF::field(),
        ]);
    }

    // ── Admin/HR: approve ─────────────────────────────────────────────
    public function approve(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_attendance');

        $correction = Database::getInstance()->fetch(
            "SELECT * FROM attendance_corrections WHERE id = :id AND status = 'pending'", ['id' => $id]
        );
        if (!$correction) $this->json(['success' => false, 'message' => 'Request not found or already reviewed'], 404);

        $notes = htmlspecialchars($this->input('notes', ''));

        // Apply the correction
        $updateData = [];
        if ($correction['requested_check_in'])  $updateData['check_in']  = $correction['requested_check_in'];
        if ($correction['requested_check_out']) $updateData['check_out'] = $correction['requested_check_out'];
        $updateData['check_in_method'] = 'manual';

        if (!empty($updateData) && $correction['attendance_id']) {
            // Recalculate hours
            if (!empty($updateData['check_in']) && !empty($updateData['check_out'])) {
                $diff = (new DateTime($updateData['check_out']))->getTimestamp()
                      - (new DateTime($updateData['check_in']))->getTimestamp();
                $updateData['working_hours'] = round($diff / 3600, 2);
            }
            Database::getInstance()->update('attendance', $updateData, 'id = :id', ['id' => $correction['attendance_id']]);
        } elseif (!empty($updateData) && $correction['requested_check_in']) {
            // Create new record if none existed
            $hrs = null;
            if (!empty($updateData['check_in']) && !empty($updateData['check_out'])) {
                $diff = (new DateTime($updateData['check_out']))->getTimestamp()
                      - (new DateTime($updateData['check_in']))->getTimestamp();
                $hrs  = round($diff / 3600, 2);
            }
            Database::getInstance()->insert('attendance', [
                'employee_id'     => $correction['employee_id'],
                'attendance_date' => $correction['attendance_date'],
                'check_in'        => $updateData['check_in'] ?? null,
                'check_out'       => $updateData['check_out'] ?? null,
                'check_in_method' => 'manual',
                'working_hours'   => $hrs,
                'status'          => 'present',
            ]);
        }

        Database::getInstance()->update('attendance_corrections', [
            'status'       => 'approved',
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => date('Y-m-d H:i:s'),
            'review_notes' => $notes,
        ], 'id = :id', ['id' => $id]);

        $this->notifyEmployee($correction['employee_id'], 'approved', $correction['attendance_date']);
        try { Auth::audit('approve', 'attendance_correction', "Approved correction ID {$id}"); } catch (\Exception $e) {}

        $this->json(['success' => true, 'message' => 'Correction approved and attendance updated']);
    }

    // ── Admin/HR: reject ──────────────────────────────────────────────
    public function reject(int $id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_attendance');

        $correction = Database::getInstance()->fetch(
            "SELECT * FROM attendance_corrections WHERE id = :id AND status = 'pending'", ['id' => $id]
        );
        if (!$correction) $this->json(['success' => false, 'message' => 'Request not found'], 404);

        $notes = htmlspecialchars($this->input('notes', 'Request rejected.'));

        Database::getInstance()->update('attendance_corrections', [
            'status'       => 'rejected',
            'reviewed_by'  => Auth::id(),
            'reviewed_at'  => date('Y-m-d H:i:s'),
            'review_notes' => $notes,
        ], 'id = :id', ['id' => $id]);

        $this->notifyEmployee($correction['employee_id'], 'rejected', $correction['attendance_date']);
        try { Auth::audit('reject', 'attendance_correction', "Rejected correction ID {$id}"); } catch (\Exception $e) {}

        $this->json(['success' => true, 'message' => 'Correction request rejected']);
    }

    // ── Helpers ───────────────────────────────────────────────────────
    private function notifyManagers(int $empId, string $date, int $corrId)
    {
        try {
            $managers = Database::getInstance()->fetchAll(
                "SELECT u.id FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE r.slug IN('admin','hr_manager','super_admin') AND u.status = 'active'"
            );
            foreach ($managers as $m) {
                Database::getInstance()->insert('notifications', [
                    'user_id'  => $m['id'],
                    'type'     => 'correction',
                    'title'    => 'Attendance Correction Request',
                    'message'  => "Employee ID {$empId} requested correction for {$date}",
                    'channel'  => 'in_app',
                    'status'   => 'sent',
                    'sent_at'  => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {}
    }

    private function notifyEmployee(int $empId, string $status, string $date)
    {
        try {
            $user = Database::getInstance()->fetch("SELECT id FROM users WHERE employee_id = :eid", ['eid' => $empId]);
            if (!$user) return;
            $msg = $status === 'approved'
                ? "Your attendance correction for {$date} has been approved."
                : "Your attendance correction for {$date} has been rejected.";
            Database::getInstance()->insert('notifications', [
                'user_id'     => $user['id'],
                'employee_id' => $empId,
                'type'        => 'correction',
                'title'       => 'Correction ' . ucfirst($status),
                'message'     => $msg,
                'channel'     => 'in_app',
                'status'      => 'sent',
                'sent_at'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {}
    }
}
