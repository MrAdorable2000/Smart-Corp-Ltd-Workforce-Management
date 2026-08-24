<?php
class UserController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('manage_users');
        $users = Database::getInstance()->fetchAll(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug,
                    e.employee_code, e.first_name, e.last_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN employees e ON e.id = u.employee_id
             ORDER BY
                CASE u.status WHEN 'pending' THEN 0 ELSE 1 END,
                u.name"
        );
        $roles = Database::getInstance()->fetchAll("SELECT * FROM roles");
        $this->view('users/index', [
            'title' => 'Users',
            'pageTitle' => 'System Users',
            'users' => $users,
            'roles' => $roles,
            'csrf' => CSRF::field()
        ]);
    }

    public function create()
    {
        $this->requireAuth();
        $this->requirePermission('manage_users');
        $roles = Database::getInstance()->fetchAll("SELECT * FROM roles");
        $this->view('users/form', [
            'title' => 'Add User',
            'pageTitle' => 'Add System User',
            'user' => null,
            'roles' => $roles,
            'csrf' => CSRF::field()
        ]);
    }

    public function store()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_users');
        $data = $this->input();
        $validator = new Validator($data);
        $validator->required('name')->required('email')->required('role_id')->required('password')
                  ->email('email')->unique('email', 'users');

        if ($validator->fails()) {
            Flash::set('error', $validator->firstError());
            $this->redirect('/users/create');
        }

        // Sanitize employee_id: empty string or 0 should be NULL (foreign key constraint)
        $employeeId = $data['employee_id'] ?? '';
        $employeeId = trim((string)$employeeId);
        $employeeId = ($employeeId === '' || $employeeId === '0') ? null : (int)$employeeId;

        // Sanitize phone: empty should be NULL
        $phone = $data['phone'] ?? '';
        $phone = trim($phone);
        $phone = $phone === '' ? null : $phone;

        Database::getInstance()->insert('users', [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $phone,
            'role_id' => (int)$data['role_id'],
            'employee_id' => $employeeId,
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]),
            'status' => 'active'
        ]);
        Auth::audit('create', 'users', "Created user {$data['email']}");
        Flash::set('success', 'User created.');
        $this->redirect('/users');
    }

    public function edit($id)
    {
        $this->requireAuth();
        $this->requirePermission('manage_users');
        $user = Database::getInstance()->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
        if (!$user) { Flash::set('error', 'Not found'); $this->redirect('/users'); }
        $roles = Database::getInstance()->fetchAll("SELECT * FROM roles");
        $this->view('users/form', [
            'title' => 'Edit User',
            'pageTitle' => 'Edit ' . $user['name'],
            'user' => $user,
            'roles' => $roles,
            'csrf' => CSRF::field()
        ]);
    }

    public function update($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_users');
        $data = $this->input();

        // Sanitize phone
        $phone = $data['phone'] ?? '';
        $phone = trim($phone);
        $phone = $phone === '' ? null : $phone;

        $update = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $phone,
            'role_id' => (int)$data['role_id'],
            'status' => $data['status'] ?? 'active'
        ];

        // Also allow updating employee_id (sanitize empty → null)
        if (isset($data['employee_id'])) {
            $empId = trim((string)$data['employee_id']);
            $update['employee_id'] = ($empId === '' || $empId === '0') ? null : (int)$empId;
        }

        if (!empty($data['password'])) {
            $update['password_hash'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => PASSWORD_COST]);
        }
        Database::getInstance()->update('users', $update, 'id = :id', ['id' => $id]);
        Auth::audit('update', 'users', "Updated user ID {$id}");
        Flash::set('success', 'User updated.');
        $this->redirect('/users');
    }

    /**
     * PERMANENTLY DELETE a user from the database.
     * - Captures old user data for audit log BEFORE deletion
     * - Cleans up all related records (notifications, audit_logs, activity_logs)
     *   to prevent foreign key constraint violations
     * - Prevents self-deletion and Super Admin deletion
     * - Then performs a hard DELETE (row is removed from database)
     */
    public function destroy($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_users');

        $user = Database::getInstance()->fetch(
            "SELECT u.*, r.slug AS role_slug
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.id = :id",
            ['id' => $id]
        );
        if (!$user) {
            Flash::set('error', 'User not found.');
            $this->redirect('/users');
        }

        // Prevent self-deletion
        if ($user['id'] == Auth::id()) {
            Flash::set('error', 'You cannot delete your own account.');
            $this->redirect('/users');
        }

        // Prevent deleting Super Admin accounts
        if ($user['role_slug'] === 'super_admin') {
            Flash::set('error', 'Super Admin accounts cannot be deleted. Suspend them instead.');
            $this->redirect('/users');
        }

        $userName = $user['name'];
        $userEmail = $user['email'];
        $db = Database::getInstance();

        // Begin transaction (all-or-nothing)
        $db->beginTransaction();

        try {
            // 1. Clean up records in tables that reference users.id (FK constraints)
            // Set user_id to NULL in audit_logs and activity_logs (FK allows NULL)
            $db->update('audit_logs', ['user_id' => null], 'user_id = :uid', ['uid' => $id]);
            $db->update('activity_logs', ['user_id' => null], 'user_id = :uid', ['uid' => $id]);

            // Delete user's notifications (FK CASCADE would handle this, but be explicit)
            $db->delete('notifications', 'user_id = :uid', ['uid' => $id]);

            // 2. If user was linked to an employee, unlink them
            // (employees.user_id doesn't exist, but employees may have this user via employee_id)
            // Actually employees table has no FK to users — but users has FK to employees
            // So just set users.employee_id = null is not needed since we're deleting the user

            // 3. PERMANENTLY DELETE the user record from the database
            $db->delete('users', 'id = :id', ['id' => $id]);

            $db->commit();

            // Log the audit (user_id is null since the user is gone)
            Auth::audit('delete', 'users', "PERMANENTLY DELETED user: {$userName} ({$userEmail})");

            Flash::set('success', "User '{$userName}' has been PERMANENTLY DELETED from the database.");
        } catch (Exception $e) {
            $db->rollBack();
            error_log("User deletion failed: " . $e->getMessage());
            Flash::set('error', 'Failed to delete user: ' . $e->getMessage());
        }

        $this->redirect('/users');
    }

    /**
     * Approve a pending user registration (admin action)
     */
    public function approve($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_users');

        $user = Database::getInstance()->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
        if (!$user) {
            Flash::set('error', 'User not found.');
            $this->redirect('/users');
        }

        if ($user['status'] !== 'pending') {
            Flash::set('error', 'User is not pending approval.');
            $this->redirect('/users');
        }

        Database::getInstance()->update('users', ['status' => 'active'], 'id = :id', ['id' => $id]);
        Auth::audit('approve', 'users', "Approved user registration: {$user['email']}");

        // Notify the user that their account is approved
        Database::getInstance()->insert('notifications', [
            'user_id' => $id,
            'type' => 'system',
            'title' => 'Account Approved!',
            'message' => "Hello {$user['name']}! Your account has been approved by an administrator. You can now login to the system.",
            'channel' => 'in_app',
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s')
        ]);

        Flash::set('success', "User '{$user['name']}' has been approved. They can now login.");
        $this->redirect('/users');
    }

    /**
     * Suspend a user (they cannot login until reactivated)
     */
    public function suspend($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_users');

        $user = Database::getInstance()->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
        if (!$user) {
            Flash::set('error', 'User not found.');
            $this->redirect('/users');
        }

        // Prevent self-suspension
        if ($user['id'] == Auth::id()) {
            Flash::set('error', 'You cannot suspend your own account.');
            $this->redirect('/users');
        }

        // Prevent suspending other super admins (only super admin can suspend managers)
        if ($user['role_slug'] === 'super_admin') {
            Flash::set('error', 'Super Admin accounts cannot be suspended.');
            $this->redirect('/users');
        }

        Database::getInstance()->update('users', [
            'status' => 'suspended',
            'remember_token' => null  // Invalidate any saved login sessions
        ], 'id = :id', ['id' => $id]);

        Auth::audit('suspend', 'users', "Suspended user: {$user['email']}");

        // Notify the user
        Database::getInstance()->insert('notifications', [
            'user_id' => $id,
            'type' => 'system',
            'title' => 'Account Suspended',
            'message' => "Hello {$user['name']}, your account has been suspended by an administrator. You cannot access the system until it is reactivated. Please contact HR if you believe this is an error.",
            'channel' => 'in_app',
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s')
        ]);

        Flash::set('success', "User '{$user['name']}' has been SUSPENDED. They can no longer access the system.");
        $this->redirect('/users');
    }

    /**
     * Reactivate a suspended/inactive user
     */
    public function reactivate($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_users');

        $user = Database::getInstance()->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
        if (!$user) {
            Flash::set('error', 'User not found.');
            $this->redirect('/users');
        }

        if ($user['status'] === 'active') {
            Flash::set('info', 'User is already active.');
            $this->redirect('/users');
        }

        Database::getInstance()->update('users', [
            'status' => 'active',
            'failed_login_attempts' => 0,
            'locked_until' => null
        ], 'id = :id', ['id' => $id]);

        Auth::audit('reactivate', 'users', "Reactivated user: {$user['email']}");

        // Notify the user
        Database::getInstance()->insert('notifications', [
            'user_id' => $id,
            'type' => 'system',
            'title' => 'Account Reactivated!',
            'message' => "Hello {$user['name']}! Your account has been reactivated by an administrator. You can now login to the system.",
            'channel' => 'in_app',
            'status' => 'sent',
            'sent_at' => date('Y-m-d H:i:s')
        ]);

        Flash::set('success', "User '{$user['name']}' has been REACTIVATED. They can now login.");
        $this->redirect('/users');
    }

    /**
     * Reject a pending registration (deletes the user record)
     */
    public function reject($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_users');

        $user = Database::getInstance()->fetch("SELECT * FROM users WHERE id = :id", ['id' => $id]);
        if (!$user) {
            Flash::set('error', 'User not found.');
            $this->redirect('/users');
        }

        if ($user['status'] !== 'pending') {
            Flash::set('error', 'Only pending registrations can be rejected. Use Suspend instead for active users.');
            $this->redirect('/users');
        }

        $userName = $user['name'];
        $userEmail = $user['email'];

        // Delete the user record entirely
        Database::getInstance()->delete('users', 'id = :id', ['id' => $id]);

        Auth::audit('reject', 'users', "Rejected and deleted registration: {$userEmail}");

        Flash::set('success', "Registration for '{$userName}' has been REJECTED and removed.");
        $this->redirect('/users');
    }
}
