<?php
class LeaveController extends Controller
{
    public function index()
    {
        $this->requireAuth();

        $status = $this->query('status', '');
        $sql = "SELECT lr.*, lt.name AS leave_type, lt.code AS leave_code,
                       e.employee_code, e.first_name, e.last_name, e.photo, d.name AS department
                FROM leave_requests lr
                INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
                INNER JOIN employees e ON e.id = lr.employee_id
                LEFT JOIN departments d ON d.id = e.department_id";
        $params = [];

        // If employee role, restrict to own
        if (Auth::role() === 'employee' && Auth::employeeId()) {
            $sql .= " WHERE lr.employee_id = :eid";
            $params['eid'] = Auth::employeeId();
            if ($status) { $sql .= " AND lr.status = :s"; $params['s'] = $status; }
        } else {
            if ($status) { $sql .= " WHERE lr.status = :s"; $params['s'] = $status; }
        }
        $sql .= " ORDER BY lr.created_at DESC";

        $requests = Database::getInstance()->fetchAll($sql, $params);
        $leaveTypes = Database::getInstance()->fetchAll("SELECT * FROM leave_types WHERE is_active = 1");

        $this->view('leaves/index', [
            'title' => 'Leave Requests',
            'pageTitle' => 'Leave Management',
            'requests' => $requests,
            'leaveTypes' => $leaveTypes,
            'status' => $status,
            'csrf' => CSRF::field()
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        $this->requirePermission('apply_leave');

        $leaveTypes = Database::getInstance()->fetchAll("SELECT * FROM leave_types WHERE is_active = 1");

        $employee = null;
        if (Auth::employeeId()) {
            $employee = Database::getInstance()->fetch("SELECT * FROM employees WHERE id = :id", ['id' => Auth::employeeId()]);
        }

        $this->view('leaves/form', [
            'title' => 'Apply Leave',
            'pageTitle' => 'Apply for Leave',
            'leaveTypes' => $leaveTypes,
            'employee' => $employee,
            'csrf' => CSRF::field()
        ]);
    }

    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('apply_leave');

        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('leave_type_id')->required('start_date')->required('end_date')->required('reason');

        if ($validator->fails()) {
            Flash::set('error', $validator->firstError());
            $this->redirect('/leaves/apply');
        }

        $start = new DateTime($data['start_date']);
        $end = new DateTime($data['end_date']);
        if ($end < $start) {
            Flash::set('error', 'End date cannot be before start date.');
            $this->redirect('/leaves/apply');
        }
        $days = $start->diff($end)->days + 1;

        $employeeId = Auth::employeeId() ?: $data['employee_id'];
        if (!$employeeId) {
            Flash::set('error', 'No employee profile linked.');
            $this->redirect('/leaves/apply');
        }

        $id = Database::getInstance()->insert('leave_requests', [
            'employee_id'    => $employeeId,
            'leave_type_id'  => $data['leave_type_id'],
            'start_date'     => $data['start_date'],
            'end_date'       => $data['end_date'],
            'total_days'     => $days,
            'reason'         => $data['reason'],
            'attachment_path'=> null,
            'status'         => 'pending',
            'applied_by'     => Auth::id(),
            'emergency_contact' => $data['emergency_contact'] ?? null,
        ]);

        Auth::audit('create', 'leaves', "Applied for leave ID {$id}");

        // Notify managers
        $managers = Database::getInstance()->fetchAll(
            "SELECT u.id FROM users u INNER JOIN role_permissions rp ON rp.role_id = u.role_id
             INNER JOIN permissions p ON p.id = rp.permission_id
             WHERE p.slug = 'manage_leaves' AND u.status = 'active'"
        );
        foreach ($managers as $m) {
            Database::getInstance()->insert('notifications', [
                'user_id' => $m['id'],
                'employee_id' => $employeeId,
                'type' => 'leave',
                'title' => 'New Leave Request',
                'message' => "Leave request #{$id} awaiting approval",
                'channel' => 'in_app',
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s')
            ]);
        }

        Flash::set('success', 'Leave request submitted. Awaiting approval.');
        $this->redirect('/leaves');
    }

    public function show($id)
    {
        $this->requireAuth();
        $leave = Database::getInstance()->fetch(
            "SELECT lr.*, lt.name AS leave_type, lt.code AS leave_code,
                    e.employee_code, e.first_name, e.last_name, e.photo, e.email, e.phone,
                    d.name AS department,
                    u.name AS applied_by_name
             FROM leave_requests lr
             INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
             INNER JOIN employees e ON e.id = lr.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN users u ON u.id = lr.applied_by
             WHERE lr.id = :id",
            ['id' => $id]
        );
        if (!$leave) { Flash::set('error', 'Not found'); $this->redirect('/leaves'); }
        $this->view('leaves/show', [
            'title' => 'Leave Details',
            'pageTitle' => 'Leave Request #' . $id,
            'leave' => $leave,
            'csrf' => CSRF::field()
        ]);
    }

    public function approve($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_leaves');

        $leave = Database::getInstance()->fetch("SELECT * FROM leave_requests WHERE id = :id", ['id' => $id]);
        if (!$leave) { Flash::set('error', 'Not found'); $this->redirect('/leaves'); }
        if ($leave['status'] !== 'pending') { Flash::set('error', 'Cannot approve - already processed'); $this->redirect('/leaves'); }

        Database::getInstance()->update('leave_requests', [
            'status' => 'approved',
            'approved_by' => Auth::id(),
            'approved_at' => date('Y-m-d H:i:s'),
            'approval_notes' => $this->input('notes', 'Approved')
        ], 'id = :id', ['id' => $id]);

        // Update leave balance
        $this->updateLeaveBalance($leave['employee_id'], $leave['leave_type_id'], $leave['total_days']);

        // Mark attendance as leave
        $start = new DateTime($leave['start_date']);
        $end = new DateTime($leave['end_date']);
        while ($start <= $end) {
            $dateStr = $start->format('Y-m-d');
            $existing = Database::getInstance()->fetch(
                "SELECT id FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
                ['eid' => $leave['employee_id'], 'd' => $dateStr]
            );
            if ($existing) {
                Database::getInstance()->update('attendance', ['status' => 'leave'],
                    'id = :id', ['id' => $existing['id']]);
            } else {
                Database::getInstance()->insert('attendance', [
                    'employee_id' => $leave['employee_id'],
                    'attendance_date' => $dateStr,
                    'status' => 'leave',
                    'notes' => 'On ' . $leave['leave_type_id'] . ' leave'
                ]);
            }
            $start->modify('+1 day');
        }

        Auth::audit('approve', 'leaves', "Approved leave ID {$id}");

        // Notify employee
        $emp = Database::getInstance()->fetch("SELECT id AS user_id FROM users WHERE employee_id = :eid", ['eid' => $leave['employee_id']]);
        if ($emp && $emp['user_id']) {
            Database::getInstance()->insert('notifications', [
                'user_id' => $emp['user_id'],
                'employee_id' => $leave['employee_id'],
                'type' => 'leave',
                'title' => 'Leave Approved',
                'message' => "Your leave request #{$id} has been approved.",
                'channel' => 'in_app',
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s')
            ]);
        }

        Flash::set('success', 'Leave approved.');
        $this->redirect('/leaves');
    }

    public function reject($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_leaves');

        Database::getInstance()->update('leave_requests', [
            'status' => 'rejected',
            'approved_by' => Auth::id(),
            'approved_at' => date('Y-m-d H:i:s'),
            'approval_notes' => $this->input('notes', 'Rejected')
        ], 'id = :id', ['id' => $id]);

        Auth::audit('reject', 'leaves', "Rejected leave ID {$id}");

        $leave = Database::getInstance()->fetch("SELECT employee_id FROM leave_requests WHERE id = :id", ['id' => $id]);
        $emp = Database::getInstance()->fetch("SELECT id AS user_id FROM users WHERE employee_id = :eid", ['eid' => $leave['employee_id']]);
        if ($emp && $emp['user_id']) {
            Database::getInstance()->insert('notifications', [
                'user_id' => $emp['user_id'],
                'employee_id' => $leave['employee_id'],
                'type' => 'leave',
                'title' => 'Leave Rejected',
                'message' => "Your leave request #{$id} has been rejected.",
                'channel' => 'in_app',
                'status' => 'sent',
                'sent_at' => date('Y-m-d H:i:s')
            ]);
        }

        Flash::set('success', 'Leave rejected.');
        $this->redirect('/leaves');
    }

    public function balance()
    {
        $this->requireAuth();
        $empId = Auth::employeeId();
        if (!$empId) { Flash::set('error', 'No employee profile'); $this->redirect('/dashboard'); }

        $balances = Database::getInstance()->fetchAll(
            "SELECT lb.*, lt.name AS leave_type, lt.code, lt.is_paid
             FROM leave_balances lb
             INNER JOIN leave_types lt ON lt.id = lb.leave_type_id
             WHERE lb.employee_id = :eid AND lb.year = :y",
            ['eid' => $empId, 'y' => date('Y')]
        );

        // If no balances, generate defaults
        if (empty($balances)) {
            $leaveTypes = Database::getInstance()->fetchAll("SELECT * FROM leave_types WHERE is_active = 1");
            foreach ($leaveTypes as $lt) {
                Database::getInstance()->insert('leave_balances', [
                    'employee_id' => $empId,
                    'leave_type_id' => $lt['id'],
                    'year' => date('Y'),
                    'entitled_days' => $lt['default_days_per_year'],
                    'used_days' => 0,
                    'carried_forward' => 0
                ]);
            }
            $balances = Database::getInstance()->fetchAll(
                "SELECT lb.*, lt.name AS leave_type, lt.code, lt.is_paid
                 FROM leave_balances lb INNER JOIN leave_types lt ON lt.id = lb.leave_type_id
                 WHERE lb.employee_id = :eid AND lb.year = :y",
                ['eid' => $empId, 'y' => date('Y')]
            );
        }

        $this->view('leaves/balance', [
            'title' => 'Leave Balance',
            'pageTitle' => 'My Leave Balance',
            'balances' => $balances
        ]);
    }

    private function updateLeaveBalance($employeeId, $leaveTypeId, $days)
    {
        $year = date('Y');
        $bal = Database::getInstance()->fetch(
            "SELECT * FROM leave_balances WHERE employee_id = :eid AND leave_type_id = :lt AND year = :y",
            ['eid' => $employeeId, 'lt' => $leaveTypeId, 'y' => $year]
        );
        if (!$bal) {
            $lt = Database::getInstance()->fetch("SELECT default_days_per_year FROM leave_types WHERE id = :id", ['id' => $leaveTypeId]);
            $balId = Database::getInstance()->insert('leave_balances', [
                'employee_id' => $employeeId,
                'leave_type_id' => $leaveTypeId,
                'year' => $year,
                'entitled_days' => $lt['default_days_per_year'] ?? 0,
                'used_days' => $days,
                'carried_forward' => 0
            ]);
        } else {
            Database::getInstance()->update('leave_balances', [
                'used_days' => $bal['used_days'] + $days
            ], 'id = :id', ['id' => $bal['id']]);
        }
    }
}
