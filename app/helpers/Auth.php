<?php
/**
 * Authentication & Authorization Helper
 */

class Auth
{
    private static $user = null;
    private static $permissions = null;

    /**
     * Attempt login with credentials
     * Returns: true on success, false on bad credentials, array for special states
     */
    public static function attempt($email, $password, $remember = false)
    {
        $db = Database::getInstance();

        // First, find user by email regardless of status (so we can give specific errors)
        $user = $db->fetch(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email
             LIMIT 1",
            ['email' => $email]
        );

        if (!$user) return false;

        // Check if account is pending (not yet approved by admin)
        if ($user['status'] === 'pending') {
            return ['pending' => true];
        }
        // Check if account is suspended/inactive
        if ($user['status'] !== 'active') {
            return ['inactive' => true, 'status' => $user['status']];
        }

        // Check lockout
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            return ['locked' => true, 'until' => $user['locked_until']];
        }

        if (!password_verify($password, $user['password_hash'])) {
            // Increment failed attempts
            $attempts = $user['failed_login_attempts'] + 1;
            $lockUntil = null;
            if ($attempts >= MAX_LOGIN_ATTEMPTS) {
                $lockUntil = date('Y-m-d H:i:s', time() + LOCKOUT_DURATION);
            }
            $db->update('users', [
                'failed_login_attempts' => $attempts,
                'locked_until' => $lockUntil
            ], 'id = :id', ['id' => $user['id']]);
            return false;
        }

        // Success - reset failed attempts and update login info
        $db->update('users', [
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'last_login_at' => date('Y-m-d H:i:s'),
            'last_login_ip' => self::clientIp()
        ], 'id = :id', ['id' => $user['id']]);

        self::login($user, $remember);

        // Log activity
        self::logActivity('login', 'User logged in');

        return true;
    }

    /**
     * Log user in (set session)
     */
    public static function login($user, $remember = false)
    {
        $session = Session::getInstance();
        $session->regenerate();
        $session->set('_user_id', $user['id']);
        $session->set('_user', [
            'id'        => $user['id'],
            'name'      => $user['name'],
            'email'     => $user['email'],
            'role_id'   => $user['role_id'],
            'role_slug' => $user['role_slug'],
            'role_name' => $user['role_name'],
            'avatar'    => $user['avatar'] ?? null,
            'employee_id' => $user['employee_id'] ?? null,
        ]);

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            $hash = hash('sha256', $token);
            Database::getInstance()->update('users', [
                'remember_token' => $hash
            ], 'id = :id', ['id' => $user['id']]);
            setcookie('remember_me', $user['id'] . ':' . $token, [
                'expires'  => time() + 30 * 86400,
                'path'     => '/',
                'secure'   => isset($_SERVER['HTTPS']),
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
    }

    /**
     * Check if user is logged in (also checks remember-me cookie)
     * IMPORTANT: Also verifies the user is still 'active' in the database.
     * If the user was suspended/inactivated by an admin AFTER login, they
     * will be automatically logged out on the next request.
     */
    public static function check()
    {
        $session = Session::getInstance();
        if ($session->has('_user_id')) {
            // Verify the user is STILL active in the database
            // (admin may have suspended/deleted them since login)
            $userId = $session->get('_user_id');
            try {
                $user = Database::getInstance()->fetch(
                    "SELECT status FROM users WHERE id = :id",
                    ['id' => $userId]
                );
                if (!$user || $user['status'] !== 'active') {
                    // User was suspended/inactivated/deleted — force logout
                    self::forceLogout();
                    return false;
                }
            } catch (Exception $e) {
                // Database error — fail safe, keep session
            }
            return true;
        }
        // Try remember-me cookie
        if (isset($_COOKIE['remember_me'])) {
            list($userId, $token) = explode(':', $_COOKIE['remember_me'], 2);
            if ($userId && $token) {
                $hash = hash('sha256', $token);
                $user = Database::getInstance()->fetch(
                    "SELECT u.*, r.slug AS role_slug, r.name AS role_name
                     FROM users u INNER JOIN roles r ON r.id = u.role_id
                     WHERE u.id = :id AND u.remember_token = :token AND u.status = 'active'",
                    ['id' => $userId, 'token' => $hash]
                );
                if ($user) {
                    self::login($user);
                    return true;
                }
            }
            setcookie('remember_me', '', time() - 3600, '/');
        }
        return false;
    }

    /**
     * Force logout (used when user is suspended mid-session)
     * Does NOT log activity (to prevent recursive errors)
     * Sets a cookie so the middleware can show a "you were suspended" message
     */
    public static function forceLogout()
    {
        Session::getInstance()->destroy();
        setcookie('remember_me', '', time() - 3600, '/');
        // Set flag cookie so middleware knows it was a suspension (not normal logout)
        setcookie('was_suspended', '1', [
            'expires'  => time() + 60,  // 1 minute — just long enough to redirect
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        self::$user = null;
        self::$permissions = null;
    }

    /**
     * Logout current user
     * Captures user ID FIRST (before session destroy) for activity logging
     */
    public static function logout()
    {
        // Capture user ID BEFORE destroying session
        $userId = self::id();
        $userName = self::name();

        if ($userId) {
            // Update remember_token to null (invalidate any saved login)
            try {
                Database::getInstance()->update('users', ['remember_token' => null],
                    'id = :id', ['id' => $userId]);
            } catch (Exception $e) {}

            // Log activity using the captured user ID (not from session)
            self::logActivityForUser($userId, 'logout', 'User logged out');
        }

        // Now destroy the session
        Session::getInstance()->destroy();
        setcookie('remember_me', '', time() - 3600, '/');

        // Clear cached user data
        self::$user = null;
        self::$permissions = null;
    }

    /**
     * Log user activity for a SPECIFIC user ID (used during logout
     * when session is already destroyed)
     */
    public static function logActivityForUser($userId, $activity, $description = '')
    {
        if (!$userId) return;

        // Verify the user actually exists (prevents FK constraint violation)
        try {
            $exists = Database::getInstance()->fetchColumn(
                "SELECT COUNT(*) FROM users WHERE id = :id",
                ['id' => $userId]
            );
            if (!$exists) {
                // User doesn't exist anymore — log with NULL user_id instead
                error_log("logActivityForUser: user_id {$userId} not found, logging as anonymous");
                $userId = null;
            }
        } catch (Exception $e) {
            $userId = null;
        }

        try {
            Database::getInstance()->insert('activity_logs', [
                'user_id'    => $userId,
                'activity'   => $activity,
                'ip_address' => self::clientIp(),
                'user_agent' => self::userAgent(),
            ]);
        } catch (Exception $e) {
            // Last resort: insert without user_id
            error_log("logActivityForUser failed: " . $e->getMessage());
        }
    }

    /**
     * Get current user data
     */
    public static function user()
    {
        if (self::$user === null) {
            self::$user = Session::getInstance()->get('_user');
        }
        return self::$user;
    }

    public static function id()
    {
        return self::user()['id'] ?? null;
    }

    public static function role()
    {
        return self::user()['role_slug'] ?? null;
    }

    public static function roleName()
    {
        return self::user()['role_name'] ?? null;
    }

    public static function name()
    {
        return self::user()['name'] ?? 'Guest';
    }

    public static function email()
    {
        return self::user()['email'] ?? null;
    }

    public static function employeeId()
    {
        return self::user()['employee_id'] ?? null;
    }

    /**
     * Check if current user has a permission
     */
    public static function can($permission)
    {
        // Super admin has all permissions
        if (self::role() === 'super_admin') return true;

        if (self::$permissions === null) {
            $roleId = self::user()['role_id'] ?? null;
            if (!$roleId) {
                self::$permissions = [];
                return false;
            }
            $perms = Database::getInstance()->fetchAll(
                "SELECT p.slug FROM permissions p
                 INNER JOIN role_permissions rp ON rp.permission_id = p.id
                 WHERE rp.role_id = :rid",
                ['rid' => $roleId]
            );
            self::$permissions = array_column($perms, 'slug');
        }
        return in_array($permission, self::$permissions);
    }

    /**
     * Check if user has any of given roles
     */
    public static function hasRole(...$roles)
    {
        return in_array(self::role(), $roles);
    }

    /**
     * Get client IP address
     */
    public static function clientIp()
    {
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Get user agent
     */
    public static function userAgent()
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Log user activity (uses current session user)
     * Verifies user exists before logging to prevent FK constraint violations
     */
    public static function logActivity($activity, $description = '')
    {
        $userId = self::id();
        if (!$userId) return;

        try {
            // Verify user exists (prevents FK violation if user was deleted mid-session)
            $exists = Database::getInstance()->fetchColumn(
                "SELECT COUNT(*) FROM users WHERE id = :id",
                ['id' => $userId]
            );
            if (!$exists) return;

            Database::getInstance()->insert('activity_logs', [
                'user_id'    => $userId,
                'activity'   => $activity,
                'ip_address' => self::clientIp(),
                'user_agent' => self::userAgent(),
            ]);
        } catch (Exception $e) {
            error_log("logActivity failed: " . $e->getMessage());
        }
    }

    /**
     * Log audit event
     * This is called by EVERY controller after a write operation,
     * so it's the perfect hook to bust the dashboard cache automatically.
     * Verifies user exists before logging to prevent FK constraint violations.
     */
    public static function audit($action, $module, $description = '', $oldValues = null, $newValue = null, $severity = 'info')
    {
        $userId = self::id();
        $employeeId = self::employeeId();

        // Verify user exists (prevents FK violation if user was deleted mid-session)
        if ($userId) {
            try {
                $userExists = Database::getInstance()->fetchColumn(
                    "SELECT COUNT(*) FROM users WHERE id = :id",
                    ['id' => $userId]
                );
                if (!$userExists) $userId = null;
            } catch (Exception $e) {
                $userId = null;
            }
        }

        // Verify employee exists if set
        if ($employeeId) {
            try {
                $empExists = Database::getInstance()->fetchColumn(
                    "SELECT COUNT(*) FROM employees WHERE id = :id",
                    ['id' => $employeeId]
                );
                if (!$empExists) $employeeId = null;
            } catch (Exception $e) {
                $employeeId = null;
            }
        }

        $data = [
            'user_id'      => $userId,
            'employee_id'  => $employeeId,
            'action'       => $action,
            'module'       => $module,
            'description'  => $description,
            'ip_address'   => self::clientIp(),
            'user_agent'   => self::userAgent(),
            'http_method'  => $_SERVER['REQUEST_METHOD'] ?? null,
            'request_url'  => $_SERVER['REQUEST_URI'] ?? null,
            'severity'     => $severity,
        ];
        if ($oldValues !== null) $data['old_values'] = json_encode($oldValues);
        if ($newValue !== null)  $data['new_values'] = json_encode($newValue);

        try {
            Database::getInstance()->insert('audit_logs', $data);
        } catch (Exception $e) {
            error_log("audit() failed: " . $e->getMessage());
        }

        // Bust dashboard cache so the change shows immediately on next dashboard load
        if (class_exists('Performance')) {
            try { Performance::bustDashboardCache(); } catch (Exception $e) {}
        }
    }
}
