<?php
/**
 * Database Audit Tracker
 * Automatically logs EVERY database modification (INSERT/UPDATE/DELETE)
 * to audit_logs with full before/after values.
 *
 * Usage: Called automatically by the Database class on write operations.
 * Admins can view all changes at /database-activity
 */
class DatabaseAudit
{
    /**
     * Log a database INSERT operation
     */
    public static function logInsert($table, $data, $newId = null)
    {
        self::log('create', $table, $newId, null, $data, "Inserted new record into {$table}" . ($newId ? " (ID: {$newId})" : ''));
    }

    /**
     * Log a database UPDATE operation (captures old values before update)
     */
    public static function logUpdate($table, $id, $oldData, $newData)
    {
        // Only log fields that actually changed
        $changes = [];
        if (is_array($oldData) && is_array($newData)) {
            foreach ($newData as $key => $newValue) {
                $oldValue = $oldData[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = ['old' => $oldValue, 'new' => $newValue];
                }
            }
        } else {
            $changes = $newData;
        }

        if (empty($changes)) return; // Don't log if nothing changed

        self::log('update', $table, $id, $oldData, $newData, "Updated record in {$table} (ID: {$id})");
    }

    /**
     * Log a database DELETE operation (captures old values before delete)
     */
    public static function logDelete($table, $id, $oldData)
    {
        self::log('delete', $table, $id, $oldData, null, "Deleted record from {$table} (ID: {$id})");
    }

    /**
     * Internal: write the audit log entry
     */
    private static function log($action, $table, $recordId, $oldValues, $newValues, $description)
    {
        // Get current user (safely — may be during logout or CLI)
        $userId = null;
        $employeeId = null;
        if (class_exists('Auth') && method_exists('Auth', 'id')) {
            try { $userId = Auth::id(); } catch (Exception $e) {}
            try { $employeeId = Auth::employeeId(); } catch (Exception $e) {}
        }

        // Verify user exists (prevents FK violation)
        if ($userId) {
            try {
                $exists = Database::getInstance()->fetchColumn(
                    "SELECT COUNT(*) FROM users WHERE id = :id",
                    ['id' => $userId]
                );
                if (!$exists) $userId = null;
            } catch (Exception $e) {
                $userId = null;
            }
        }

        // Get request info
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
        $httpMethod = $_SERVER['REQUEST_METHOD'] ?? 'CLI';
        $requestUrl = $_SERVER['REQUEST_URI'] ?? '';

        // Serialize values for storage
        $oldJson = null;
        $newJson = null;
        if ($oldValues !== null) {
            $oldJson = json_encode(self::sanitizeForJson($oldValues), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        if ($newValues !== null) {
            $newJson = json_encode(self::sanitizeForJson($newValues), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        // Build description with table + record ID + action
        $fullDescription = "[{$table}] " . $description;

        try {
            Database::getInstance()->insert('audit_logs', [
                'user_id'      => $userId,
                'employee_id'  => $employeeId,
                'action'       => $action,
                'module'       => $table,
                'description'  => $fullDescription,
                'ip_address'   => $ip,
                'user_agent'   => $userAgent,
                'http_method'  => $httpMethod,
                'request_url'  => $requestUrl,
                'old_values'   => $oldJson,
                'new_values'   => $newJson,
                'severity'     => $action === 'delete' ? 'warning' : 'info',
            ]);
        } catch (Exception $e) {
            error_log("DatabaseAudit log failed: " . $e->getMessage());
        }
    }

    /**
     * Sanitize values for JSON storage (handle binary, resources, etc.)
     */
    private static function sanitizeForJson($data)
    {
        if (!is_array($data)) return $data;
        $clean = [];
        foreach ($data as $key => $value) {
            // Skip sensitive fields (never log passwords)
            if (in_array($key, ['password_hash', 'password', 'two_fa_secret', 'remember_token', 'password_reset_token', 'otp_code'])) {
                $clean[$key] = '[REDACTED]';
                continue;
            }
            // Skip binary/large fields
            if (in_array($key, ['fingerprint_template', 'descriptor', 'metadata'])) {
                $clean[$key] = is_string($value) && strlen($value) > 200
                    ? '[' . strlen($value) . ' chars]'
                    : $value;
                continue;
            }
            // Handle resources
            if (is_resource($value)) {
                $clean[$key] = '[resource]';
                continue;
            }
            // Truncate very long strings
            if (is_string($value) && strlen($value) > 1000) {
                $clean[$key] = substr($value, 0, 1000) . '... [truncated]';
                continue;
            }
            $clean[$key] = $value;
        }
        return $clean;
    }

    /**
     * Capture old values BEFORE an update (for comparison)
     */
    public static function captureOldValues($table, $where, $whereParams)
    {
        try {
            $sql = "SELECT * FROM `{$table}` WHERE {$where}";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($whereParams);
            return $stmt->fetch();
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Capture old values BEFORE a delete
     */
    public static function captureOldValuesAll($table, $where, $whereParams)
    {
        try {
            $sql = "SELECT * FROM `{$table}` WHERE {$where}";
            $stmt = Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($whereParams);
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get recent database changes for display
     */
    public static function getRecentChanges($filters = [], $limit = 50, $offset = 0)
    {
        $db = Database::getInstance();
        $where = "1=1";
        $params = [];

        if (!empty($filters['action'])) {
            $where .= " AND action = :action";
            $params['action'] = $filters['action'];
        }
        if (!empty($filters['module'])) {
            $where .= " AND module = :module";
            $params['module'] = $filters['module'];
        }
        if (!empty($filters['user_id'])) {
            $where .= " AND user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $where .= " AND DATE(created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND DATE(created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        $sql = "SELECT al.*, u.name AS user_name, u.email AS user_email
                FROM audit_logs al
                LEFT JOIN users u ON u.id = al.user_id
                WHERE {$where}
                ORDER BY al.created_at DESC
                LIMIT {$limit} OFFSET {$offset}";

        return $db->fetchAll($sql, $params);
    }

    /**
     * Get count of changes (for pagination)
     */
    public static function getChangesCount($filters = [])
    {
        $db = Database::getInstance();
        $where = "1=1";
        $params = [];

        if (!empty($filters['action'])) {
            $where .= " AND action = :action";
            $params['action'] = $filters['action'];
        }
        if (!empty($filters['module'])) {
            $where .= " AND module = :module";
            $params['module'] = $filters['module'];
        }
        if (!empty($filters['user_id'])) {
            $where .= " AND user_id = :user_id";
            $params['user_id'] = $filters['user_id'];
        }
        if (!empty($filters['date_from'])) {
            $where .= " AND DATE(created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where .= " AND DATE(created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        return (int)$db->fetchColumn(
            "SELECT COUNT(*) FROM audit_logs WHERE {$where}",
            $params
        );
    }

    /**
     * Get summary stats (today's changes by type)
     */
    public static function getTodayStats()
    {
        $db = Database::getInstance();
        return [
            'inserts'   => (int)$db->fetchColumn("SELECT COUNT(*) FROM audit_logs WHERE action = 'create' AND DATE(created_at) = CURDATE()"),
            'updates'   => (int)$db->fetchColumn("SELECT COUNT(*) FROM audit_logs WHERE action = 'update' AND DATE(created_at) = CURDATE()"),
            'deletes'   => (int)$db->fetchColumn("SELECT COUNT(*) FROM audit_logs WHERE action = 'delete' AND DATE(created_at) = CURDATE()"),
            'approves'  => (int)$db->fetchColumn("SELECT COUNT(*) FROM audit_logs WHERE action = 'approve' AND DATE(created_at) = CURDATE()"),
            'total'     => (int)$db->fetchColumn("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()"),
            'all_time'  => (int)$db->fetchColumn("SELECT COUNT(*) FROM audit_logs"),
        ];
    }

    /**
     * Get list of all modules (tables) that have changes
     */
    public static function getModulesList()
    {
        $db = Database::getInstance();
        return $db->fetchAll(
            "SELECT DISTINCT module, COUNT(*) as count
             FROM audit_logs
             GROUP BY module
             ORDER BY count DESC"
        );
    }

    /**
     * Get a single change record with full details
     */
    public static function getChange($id)
    {
        $db = Database::getInstance();
        return $db->fetch(
            "SELECT al.*, u.name AS user_name, u.email AS user_email
             FROM audit_logs al
             LEFT JOIN users u ON u.id = al.user_id
             WHERE al.id = :id",
            ['id' => $id]
        );
    }
}
