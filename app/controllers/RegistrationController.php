<?php
/**
 * RegistrationController — Enterprise Employee Self-Registration
 * Handles multi-step wizard, face enrollment, admin approval workflow
 */
class RegistrationController extends Controller
{
    // ── Step 1: Show wizard ───────────────────────────────────────────
    public function index()
    {
        $this->layout = 'auth';
        $db = Database::getInstance();

        $departments = $db->fetchAll("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name");
        $branches    = $db->fetchAll("SELECT id, name, city FROM branches WHERE is_active = 1 ORDER BY name");

        $this->view('registration/wizard', [
            'title'       => 'Employee Self-Registration',
            'departments' => $departments,
            'branches'    => $branches,
            'csrf'        => CSRF::field(),
        ]);
    }

    // ── Step API: Check duplicate fields ─────────────────────────────
    public function checkDuplicate()
    {
        $this->validateCsrf();
        $field = $this->input('field');
        $value = trim($this->input('value', ''));

        if (!$field || !$value) {
            $this->json(['available' => false, 'message' => 'Missing data']);
        }

        $allowed = ['email', 'national_id', 'phone', 'employee_code'];
        if (!in_array($field, $allowed)) {
            $this->json(['available' => false, 'message' => 'Invalid field']);
        }

        $db = Database::getInstance();

        // Check in registration_requests
        $inReq = $db->count('registration_requests',
            "`{$field}` = :v AND `status` NOT IN ('rejected')",
            ['v' => $value]
        );
        // Check in employees
        $map = ['email' => 'email', 'national_id' => 'national_id', 'phone' => 'phone', 'employee_code' => 'employee_code'];
        $inEmp = isset($map[$field])
            ? $db->count('employees', "`{$map[$field]}` = :v", ['v' => $value])
            : 0;
        // Check in users (email only)
        $inUser = $field === 'email' ? $db->count('users', 'email = :v', ['v' => $value]) : 0;

        $taken = ($inReq + $inEmp + $inUser) > 0;
        $this->json([
            'available' => !$taken,
            'message'   => $taken ? ucfirst(str_replace('_', ' ', $field)) . ' is already registered' : 'Available',
        ]);
    }

    // ── Submit complete registration ──────────────────────────────────
    public function submit()
    {
        $this->validateCsrf();

        $data = $this->input();
        $db   = Database::getInstance();

        // ── Validate ──────────────────────────────────────────────────
        $v = new Validator($data);
        $v->required('first_name', 'First name')
          ->required('last_name', 'Last name')
          ->required('email', 'Email address')
          ->required('password', 'Password')
          ->required('confirm_password', 'Confirm password')
          ->required('position', 'Position')
          ->email('email')
          ->min('password', 8, 'Password');

        if ($v->fails()) {
            $this->json(['success' => false, 'message' => $v->firstError(), 'step' => 1], 422);
        }

        if ($data['password'] !== $data['confirm_password']) {
            $this->json(['success' => false, 'message' => 'Passwords do not match', 'step' => 2], 422);
        }

        // ── Duplicate checks ──────────────────────────────────────────
        $email = strtolower(trim($data['email']));

        $existsInReq  = $db->count('registration_requests', 'email = :e AND status NOT IN ("rejected")', ['e' => $email]);
        $existsInUser = $db->count('users', 'email = :e', ['e' => $email]);
        if ($existsInReq || $existsInUser) {
            $this->json(['success' => false, 'message' => 'This email is already registered. Please login or contact HR.', 'step' => 2], 409);
        }

        $phone = trim($data['phone'] ?? '');
        if ($phone) {
            $dupPhone = $db->count('registration_requests', 'phone = :p AND status NOT IN ("rejected")', ['p' => $phone])
                      + $db->count('employees', 'phone = :p', ['p' => $phone]);
            if ($dupPhone > 0) {
                $this->json(['success' => false, 'message' => 'This phone number is already registered.', 'step' => 1], 409);
            }
        }

        $nationalId = trim($data['national_id'] ?? '');
        if ($nationalId) {
            $dupNid = $db->count('registration_requests', 'national_id = :n AND status NOT IN ("rejected")', ['n' => $nationalId])
                    + $db->count('employees', 'national_id = :n', ['n' => $nationalId]);
            if ($dupNid > 0) {
                $this->json(['success' => false, 'message' => 'This National ID is already registered.', 'step' => 1], 409);
            }
        }

        // ── Process profile photo ─────────────────────────────────────
        $profilePhoto = null;
        if (!empty($data['profile_photo_data'])) {
            $profilePhoto = $this->saveBase64Image($data['profile_photo_data'], 'registrations');
        }

        // ── Process face descriptors ──────────────────────────────────
        $faceDescriptors = null;
        $faceQuality     = null;
        $faceAngles      = null;
        $faceEnrolledAt  = null;

        if (!empty($data['face_descriptors'])) {
            $decoded = json_decode($data['face_descriptors'], true);
            if (is_array($decoded) && count($decoded) >= 1) {
                // Validate each descriptor is 128 floats
                $valid = true;
                foreach ($decoded as $entry) {
                    $desc = $entry['descriptor'] ?? null;
                    if (!is_array($desc) || count($desc) !== 128) { $valid = false; break; }
                }
                if ($valid) {
                    $faceDescriptors = $data['face_descriptors'];
                    $faceAngles      = implode(',', array_column($decoded, 'label'));
                    $faceQuality     = (float)($data['face_quality_score'] ?? 85.0);
                    $faceEnrolledAt  = date('Y-m-d H:i:s');

                    // Save face images
                    foreach ($decoded as &$entry) {
                        if (!empty($entry['image_data'])) {
                            $entry['image_path'] = $this->saveBase64Image($entry['image_data'], 'registration_faces');
                            unset($entry['image_data']); // don't store raw base64 in DB
                        }
                    }
                    $faceDescriptors = json_encode($decoded);
                }
            }
        }

        // ── Auto-generate employee code if not provided ───────────────
        $empCode = trim($data['employee_code'] ?? '');
        if (!$empCode) {
            $empCode = $this->generateEmployeeCode($db);
        } else {
            // Check uniqueness
            $dupCode = $db->count('registration_requests', 'employee_code = :c AND status NOT IN ("rejected")', ['c' => $empCode])
                     + $db->count('employees', 'employee_code = :c', ['c' => $empCode]);
            if ($dupCode > 0) {
                $this->json(['success' => false, 'message' => 'Employee code already exists. Leave blank for auto-generation.', 'step' => 1], 409);
            }
        }

        // ── Get employee role ID ──────────────────────────────────────
        $role = $db->fetch("SELECT id FROM roles WHERE slug = 'employee' LIMIT 1");
        $roleId = $role['id'] ?? 4;

        // ── Generate secure token ─────────────────────────────────────
        $token = bin2hex(random_bytes(32));

        // ── Insert registration request ───────────────────────────────
        $requestId = $db->insert('registration_requests', [
            'token'                   => $token,
            'first_name'              => trim($data['first_name']),
            'last_name'               => trim($data['last_name']),
            'email'                   => $email,
            'phone'                   => $phone ?: null,
            'national_id'             => $nationalId ?: null,
            'passport_number'         => trim($data['passport_number'] ?? '') ?: null,
            'date_of_birth'           => $data['date_of_birth'] ?? null,
            'gender'                  => $data['gender'] ?? null,
            'address'                 => trim($data['address'] ?? '') ?: null,
            'employee_code'           => $empCode,
            'position'                => trim($data['position']),
            'department_id'           => ($data['department_id'] ?? null) ?: null,
            'branch_id'               => ($data['branch_id'] ?? null) ?: null,
            'employment_type'         => $data['employment_type'] ?? 'full_time',
            'emergency_contact_name'  => trim($data['emergency_contact_name'] ?? '') ?: null,
            'emergency_contact_phone' => trim($data['emergency_contact_phone'] ?? '') ?: null,
            'emergency_contact_rel'   => trim($data['emergency_contact_relation'] ?? '') ?: null,
            'password_hash'           => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]),
            'role_id'                 => $roleId,
            'face_descriptors'        => $faceDescriptors,
            'face_enrolled_at'        => $faceEnrolledAt,
            'face_quality_score'      => $faceQuality,
            'face_angles_captured'    => $faceAngles,
            'profile_photo'           => $profilePhoto,
            'status'                  => 'pending',
            'submitted_at'            => date('Y-m-d H:i:s'),
            'ip_address'              => Auth::clientIp(),
            'user_agent'              => Auth::userAgent(),
        ]);

        // ── Log ───────────────────────────────────────────────────────
        $db->insert('approval_logs', [
            'request_id'   => $requestId,
            'action'       => 'submitted',
            'performed_by' => null,
            'notes'        => "Self-registration submitted from IP: " . Auth::clientIp(),
        ]);

        // ── Notify admins ─────────────────────────────────────────────
        $this->notifyAdmins($requestId, $data['first_name'] . ' ' . $data['last_name'], $email, $db);

        $this->json([
            'success'   => true,
            'message'   => 'Registration submitted successfully!',
            'token'     => $token,
            'status_url'=> BASE_URL . '/registration/status/' . $token,
        ]);
    }

    // ── Status page (public, token-based) ────────────────────────────
    public function status($token)
    {
        $this->layout = 'auth';
        $db = Database::getInstance();

        $request = $db->fetch(
            "SELECT r.*, d.name AS dept_name, b.name AS branch_name
             FROM registration_requests r
             LEFT JOIN departments d ON d.id = r.department_id
             LEFT JOIN branches    b ON b.id = r.branch_id
             WHERE r.token = :t",
            ['t' => $token]
        );

        if (!$request) {
            $this->layout = 'auth';
            Flash::set('error', 'Registration not found. Check your link.');
            $this->redirect('/register');
        }

        $history = $db->fetchAll(
            "SELECT al.*, u.name AS reviewer_name
             FROM approval_logs al
             LEFT JOIN users u ON u.id = al.performed_by
             WHERE al.request_id = :id ORDER BY al.created_at ASC",
            ['id' => $request['id']]
        );

        $this->view('registration/status', [
            'title'   => 'Registration Status',
            'request' => $request,
            'history' => $history,
            'csrf'    => CSRF::field(),
        ]);
    }

    // ── Resubmit after changes requested ─────────────────────────────
    public function resubmit($token)
    {
        $this->validateCsrf();
        $db = Database::getInstance();

        $request = $db->fetch(
            "SELECT * FROM registration_requests WHERE token = :t AND status = 'changes_requested'",
            ['t' => $token]
        );
        if (!$request) {
            $this->json(['success' => false, 'message' => 'Request not found or not eligible for resubmission'], 404);
        }

        $data = $this->input();
        $updates = [];

        // Only update provided fields
        foreach (['first_name','last_name','phone','national_id','address','position',
                  'emergency_contact_name','emergency_contact_phone','emergency_contact_relation'] as $f) {
            if (isset($data[$f]) && trim($data[$f]) !== '') {
                $col = $f === 'emergency_contact_relation' ? 'emergency_contact_rel' : $f;
                $updates[$col] = trim($data[$f]);
            }
        }

        $updates['status']         = 'pending';
        $updates['resubmitted_at'] = date('Y-m-d H:i:s');
        $updates['resubmit_count'] = $request['resubmit_count'] + 1;

        $db->update('registration_requests', $updates, 'id = :id', ['id' => $request['id']]);
        $db->insert('approval_logs', [
            'request_id' => $request['id'],
            'action'     => 'resubmitted',
            'notes'      => 'Applicant resubmitted after changes requested',
        ]);

        $this->notifyAdmins($request['id'], $request['first_name'].' '.$request['last_name'], $request['email'], $db, 'resubmit');

        $this->json(['success' => true, 'message' => 'Your registration has been resubmitted for review.']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  ADMIN APPROVAL CENTER
    // ═══════════════════════════════════════════════════════════════

    // ── List all pending registrations ────────────────────────────────
    public function adminList()
    {
        $this->requireAuth();
        $this->requirePermission('manage_users');

        $status = $this->query('status', 'pending');
        $db     = Database::getInstance();

        $where  = $status !== 'all' ? "WHERE r.status = :s" : "WHERE 1=1";
        $params = $status !== 'all' ? ['s' => $status] : [];

        $requests = $db->fetchAll(
            "SELECT r.*, d.name AS dept_name, b.name AS branch_name,
                    u.name AS reviewer_name
             FROM registration_requests r
             LEFT JOIN departments d ON d.id = r.department_id
             LEFT JOIN branches    b ON b.id = r.branch_id
             LEFT JOIN users       u ON u.id = r.reviewed_by
             {$where}
             ORDER BY r.submitted_at DESC",
            $params
        );

        // KPIs
        $counts = $db->fetch(
            "SELECT
                SUM(status='pending')            AS pending,
                SUM(status='under_review')       AS under_review,
                SUM(status='approved')           AS approved,
                SUM(status='rejected')           AS rejected,
                SUM(status='changes_requested')  AS changes_requested
             FROM registration_requests"
        );

        $this->view('registration/admin_list', [
            'title'    => 'Registration Approval Center',
            'pageTitle'=> 'Employee Registration Requests',
            'requests' => $requests,
            'counts'   => $counts,
            'status'   => $status,
            'csrf'     => CSRF::field(),
        ]);
    }

    // ── Review single request ─────────────────────────────────────────
    public function adminReview($id)
    {
        $this->requireAuth();
        $this->requirePermission('manage_users');

        $db      = Database::getInstance();
        $request = $this->getRequestOrFail($id, $db);

        $history    = $db->fetchAll(
            "SELECT al.*, u.name AS reviewer_name FROM approval_logs al
             LEFT JOIN users u ON u.id = al.performed_by WHERE al.request_id = :id ORDER BY al.created_at ASC",
            ['id' => $id]
        );
        $departments = $db->fetchAll("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name");
        $branches    = $db->fetchAll("SELECT id, name FROM branches WHERE is_active = 1 ORDER BY name");
        $shifts      = $db->fetchAll("SELECT id, name, start_time, end_time FROM shifts WHERE is_active = 1 ORDER BY name");

        // Mark as under review
        if ($request['status'] === 'pending') {
            $db->update('registration_requests', ['status' => 'under_review'], 'id = :id', ['id' => $id]);
            $db->insert('approval_logs', [
                'request_id'   => $id,
                'action'       => 'reviewed',
                'performed_by' => Auth::id(),
                'notes'        => 'Admin opened for review',
            ]);
            $request['status'] = 'under_review';
        }

        $this->view('registration/admin_review', [
            'title'       => 'Review Registration',
            'pageTitle'   => 'Review: ' . $request['first_name'] . ' ' . $request['last_name'],
            'request'     => $request,
            'history'     => $history,
            'departments' => $departments,
            'branches'    => $branches,
            'shifts'      => $shifts,
            'csrf'        => CSRF::field(),
        ]);
    }

    // ── Approve ───────────────────────────────────────────────────────
    public function approve($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_users');

        $db      = Database::getInstance();
        $request = $this->getRequestOrFail($id, $db);

        if ($request['status'] === 'approved') {
            $this->json(['success' => false, 'message' => 'Already approved'], 409);
        }

        $notes      = htmlspecialchars($this->input('notes', ''));
        $shiftId    = ($this->input('shift_id') ?: null);
        $deptId     = ($this->input('department_id') ?: $request['department_id']);
        $branchId   = ($this->input('branch_id') ?: $request['branch_id']);
        $salary     = (float)$this->input('salary', 0);
        $empCode    = trim($this->input('employee_code', $request['employee_code'] ?? ''));

        // Auto-generate employee code if still empty
        if (!$empCode) $empCode = $this->generateEmployeeCode($db);

        // Prevent duplicate employee code
        $dupCode = $db->count('employees', 'employee_code = :c', ['c' => $empCode]);
        if ($dupCode > 0) $empCode = $this->generateEmployeeCode($db, true);

        $now = date('Y-m-d H:i:s');
        $today = date('Y-m-d');

        // ── 1. Create employee record ─────────────────────────────────
        $companyId = $db->fetch("SELECT id FROM companies LIMIT 1")['id'] ?? 1;

        $employeeId = $db->insert('employees', [
            'employee_code'           => $empCode,
            'company_id'              => $companyId,
            'branch_id'               => $branchId ?: null,
            'department_id'           => $deptId ?: null,
            'shift_id'                => $shiftId ?: null,
            'first_name'              => $request['first_name'],
            'last_name'               => $request['last_name'],
            'email'                   => $request['email'],
            'phone'                   => $request['phone'],
            'national_id'             => $request['national_id'],
            'passport_number'         => $request['passport_number'],
            'date_of_birth'           => $request['date_of_birth'],
            'gender'                  => $request['gender'],
            'address'                 => $request['address'],
            'position'                => $request['position'],
            'employment_type'         => $request['employment_type'] ?? 'full_time',
            'employment_status'       => 'permanent',
            'salary'                  => $salary,
            'date_joined'             => $today,
            'photo'                   => $request['profile_photo'],
            'emergency_contact_name'  => $request['emergency_contact_name'],
            'emergency_contact_phone' => $request['emergency_contact_phone'],
            'emergency_contact_relation' => $request['emergency_contact_rel'],
            'face_enrolled'           => ($request['face_descriptors'] ? 1 : 0),
            'is_active'               => 1,
            'created_by'              => Auth::id(),
            'registration_request_id' => $id,
        ]);

        // ── 2. Create user account ────────────────────────────────────
        $role = $db->fetch("SELECT id FROM roles WHERE slug = 'employee' LIMIT 1");
        $roleId = $role['id'] ?? $request['role_id'];

        $userId = $db->insert('users', [
            'role_id'       => $roleId,
            'employee_id'   => $employeeId,
            'name'          => $request['first_name'] . ' ' . $request['last_name'],
            'email'         => $request['email'],
            'phone'         => $request['phone'],
            'password_hash' => $request['password_hash'],
            'avatar'        => $request['profile_photo'],
            'status'        => 'active',
        ]);

        // ── 3. Copy face descriptors ──────────────────────────────────
        if ($request['face_descriptors']) {
            $faces = json_decode($request['face_descriptors'], true) ?? [];
            $isPrimary = true;
            foreach ($faces as $face) {
                $desc = $face['descriptor'] ?? null;
                if (!is_array($desc) || count($desc) !== 128) continue;
                $db->insert('employee_faces', [
                    'employee_id' => $employeeId,
                    'descriptor'  => json_encode($desc),
                    'label'       => $face['label'] ?? 'front',
                    'image_path'  => $face['image_path'] ?? null,
                    'is_primary'  => $isPrimary ? 1 : 0,
                    'is_active'   => 1,
                ]);
                $isPrimary = false;
            }
        }

        // ── 4. Generate QR code ───────────────────────────────────────
        try { QrAttendance::generate($employeeId); } catch (\Exception $e) {}

        // ── 5. Assign default shift if none ───────────────────────────
        if (!$shiftId) {
            $defaultShift = $db->fetch("SELECT id FROM shifts WHERE is_default = 1 AND is_active = 1 LIMIT 1");
            if ($defaultShift) {
                $db->update('employees', ['shift_id' => $defaultShift['id']], 'id = :id', ['id' => $employeeId]);
            }
        }

        // ── 6. Update request ─────────────────────────────────────────
        $db->update('registration_requests', [
            'status'      => 'approved',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => $now,
            'user_id'     => $userId,
            'employee_id' => $employeeId,
        ], 'id = :id', ['id' => $id]);

        $db->insert('approval_logs', [
            'request_id'   => $id,
            'action'       => 'approved',
            'performed_by' => Auth::id(),
            'notes'        => $notes ?: 'Approved and employee account created.',
        ]);

        // ── 7. Status history ─────────────────────────────────────────
        $db->insert('employee_status_history', [
            'employee_id' => $employeeId,
            'from_status' => 'pending',
            'to_status'   => 'active',
            'reason'      => 'Account approved via self-registration',
            'changed_by'  => Auth::id(),
        ]);

        // ── 8. Welcome notification ───────────────────────────────────
        $db->insert('notifications', [
            'user_id'    => $userId,
            'employee_id'=> $employeeId,
            'type'       => 'approval',
            'title'      => 'Account Approved — Welcome!',
            'message'    => "Congratulations {$request['first_name']}! Your account has been approved. Your Employee ID is {$empCode}. You can now log in and start recording attendance.",
            'channel'    => 'in_app',
            'status'     => 'sent',
            'sent_at'    => $now,
        ]);

        try { Auth::audit('approve', 'registration', "Approved registration {$id} — Employee #{$empCode}"); } catch (\Exception $e) {}

        $this->json([
            'success'      => true,
            'message'      => "Registration approved! Employee {$request['first_name']} {$request['last_name']} ({$empCode}) is now active.",
            'employee_id'  => $employeeId,
            'employee_code'=> $empCode,
            'user_id'      => $userId,
        ]);
    }

    // ── Reject ────────────────────────────────────────────────────────
    public function reject($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_users');

        $db      = Database::getInstance();
        $request = $this->getRequestOrFail($id, $db);

        $reason = trim($this->input('reason', ''));
        if (!$reason) {
            $this->json(['success' => false, 'message' => 'Rejection reason is required'], 422);
        }

        $db->update('registration_requests', [
            'status'           => 'rejected',
            'reviewed_by'      => Auth::id(),
            'reviewed_at'      => date('Y-m-d H:i:s'),
            'rejection_reason' => $reason,
        ], 'id = :id', ['id' => $id]);

        $db->insert('approval_logs', [
            'request_id'   => $id,
            'action'       => 'rejected',
            'performed_by' => Auth::id(),
            'notes'        => $reason,
        ]);

        try { Auth::audit('reject', 'registration', "Rejected registration {$id}: {$reason}"); } catch (\Exception $e) {}

        $this->json(['success' => true, 'message' => 'Registration rejected and applicant notified.']);
    }

    // ── Request changes ───────────────────────────────────────────────
    public function requestChanges($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_users');

        $db      = Database::getInstance();
        $request = $this->getRequestOrFail($id, $db);

        $notes = trim($this->input('notes', ''));
        if (!$notes) {
            $this->json(['success' => false, 'message' => 'Please specify what changes are needed'], 422);
        }

        $db->update('registration_requests', [
            'status'               => 'changes_requested',
            'reviewed_by'          => Auth::id(),
            'reviewed_at'          => date('Y-m-d H:i:s'),
            'change_request_notes' => $notes,
        ], 'id = :id', ['id' => $id]);

        $db->insert('approval_logs', [
            'request_id'   => $id,
            'action'       => 'changes_requested',
            'performed_by' => Auth::id(),
            'notes'        => $notes,
        ]);

        $this->json(['success' => true, 'message' => 'Changes requested. Applicant has been notified.']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Kiosk: enforce ACTIVE status check
    // ═══════════════════════════════════════════════════════════════
    public function checkEmployeeStatus()
    {
        // Called by kiosk after face match to verify employee can attend
        $this->validateCsrf();
        $empId = (int)$this->input('employee_id');
        if (!$empId) $this->json(['success' => false, 'message' => 'Employee ID required'], 400);

        $db = Database::getInstance();
        $emp = $db->fetch(
            "SELECT e.is_active, u.status AS user_status, u.id AS user_id
             FROM employees e LEFT JOIN users u ON u.employee_id = e.id
             WHERE e.id = :id",
            ['id' => $empId]
        );

        if (!$emp || !$emp['is_active']) {
            $this->json(['success' => false, 'blocked' => true, 'message' => 'Employee account is inactive. Contact HR.']);
        }
        if ($emp['user_status'] && !in_array($emp['user_status'], ['active'])) {
            $status = $emp['user_status'];
            $msg = match($status) {
                'pending'   => 'Your registration is pending admin approval. Attendance not yet enabled.',
                'suspended' => 'Your account has been suspended. Contact HR.',
                'inactive'  => 'Your account is inactive. Contact HR.',
                default     => 'Account status: ' . ucfirst($status) . '. Contact HR.',
            };
            $this->json(['success' => false, 'blocked' => true, 'status' => $status, 'message' => $msg]);
        }

        $this->json(['success' => true, 'active' => true, 'message' => 'Account is active']);
    }

    // ═══════════════════════════════════════════════════════════════
    //  Helpers
    // ═══════════════════════════════════════════════════════════════

    private function getRequestOrFail($id, $db)
    {
        $request = $db->fetch("SELECT * FROM registration_requests WHERE id = :id", ['id' => $id]);
        if (!$request) {
            if ($this->isAjax()) $this->json(['success' => false, 'message' => 'Request not found'], 404);
            Flash::set('error', 'Registration request not found.');
            $this->redirect('/registration/admin');
        }
        return $request;
    }

    private function generateEmployeeCode($db, $force = false): string
    {
        $prefix = 'EMP';
        $year   = date('Y');
        $base   = $prefix . $year;

        // Find the highest existing code with this pattern
        $last = $db->fetch(
            "SELECT employee_code FROM employees WHERE employee_code LIKE :p ORDER BY employee_code DESC LIMIT 1",
            ['p' => $base . '%']
        );
        $lastReq = $db->fetch(
            "SELECT employee_code FROM registration_requests WHERE employee_code LIKE :p AND status != 'rejected' ORDER BY employee_code DESC LIMIT 1",
            ['p' => $base . '%']
        );

        $nums = [];
        if ($last)    $nums[] = (int)substr($last['employee_code'], strlen($base));
        if ($lastReq) $nums[] = (int)substr($lastReq['employee_code'], strlen($base));
        $next = (max($nums ?: [0]) + 1);

        return $base . str_pad($next, 4, '0', STR_PAD_LEFT);
    }

    private function saveBase64Image(string $dataUri, string $subdir): ?string
    {
        if (!preg_match('/^data:image\/(\w+);base64,/', $dataUri, $m)) return null;
        $ext     = strtolower($m[1]);
        if (!in_array($ext, ['jpg','jpeg','png','webp'])) $ext = 'jpg';
        $data    = base64_decode(preg_replace('/^data:image\/\w+;base64,/', '', $dataUri));
        if (!$data || strlen($data) < 100) return null;

        $dir   = UPLOAD_PATH . '/' . $subdir . '/' . date('Y/m');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $fname = uniqid('img_', true) . '.' . $ext;
        file_put_contents($dir . '/' . $fname, $data);
        return $subdir . '/' . date('Y/m') . '/' . $fname;
    }

    private function notifyAdmins($requestId, $name, $email, $db, $type = 'new'): void
    {
        try {
            $admins = $db->fetchAll(
                "SELECT DISTINCT u.id FROM users u
                 INNER JOIN roles r ON r.id = u.role_id
                 WHERE r.slug IN ('super_admin','hr_manager','admin') AND u.status = 'active'"
            );
            $title = $type === 'resubmit'
                ? 'Registration Resubmitted'
                : 'New Registration Request';
            $msg   = $type === 'resubmit'
                ? "{$name} ({$email}) has resubmitted their registration after changes were requested."
                : "{$name} ({$email}) has submitted a new employee registration. Please review.";

            foreach ($admins as $admin) {
                $db->insert('notifications', [
                    'user_id' => $admin['id'],
                    'type'    => 'approval',
                    'title'   => $title,
                    'message' => $msg,
                    'channel' => 'in_app',
                    'status'  => 'sent',
                    'sent_at' => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {}
    }
}
