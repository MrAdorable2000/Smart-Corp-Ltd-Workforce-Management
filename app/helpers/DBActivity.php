<?php
/**
 * Real-time Database Activity Tracker
 * Shows recent changes happening in the database so the user can verify
 * that their actions (inserts/updates/deletes) are being persisted.
 */
class DBActivity
{
    /**
     * Get recent database changes from audit_logs and activity_logs
     */
    public static function getRecentChanges($limit = 15)
    {
        $db = Database::getInstance();

        // Combine audit_logs (structured) and activity_logs (session-based)
        $sql = "SELECT 'audit' as source, id, action, module, description,
                       ip_address, user_agent, severity, created_at,
                       NULL as activity
                FROM audit_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)

                UNION ALL

                SELECT 'activity' as source, id, NULL as action, NULL as module,
                       activity as description, ip_address, user_agent,
                       'info' as severity, created_at, activity
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)

                ORDER BY created_at DESC
                LIMIT {$limit}";

        try {
            return $db->fetchAll($sql);
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get quick counts of today's DB changes
     */
    public static function getTodayChangeStats()
    {
        $db = Database::getInstance();
        $stats = [
            'inserts'  => 0,
            'updates'  => 0,
            'deletes'  => 0,
            'logins'   => 0,
            'total'    => 0,
        ];

        try {
            $stats['inserts'] = (int)$db->fetchColumn(
                "SELECT COUNT(*) FROM audit_logs WHERE action = 'create' AND DATE(created_at) = CURDATE()"
            );
            $stats['updates'] = (int)$db->fetchColumn(
                "SELECT COUNT(*) FROM audit_logs WHERE action = 'update' AND DATE(created_at) = CURDATE()"
            );
            $stats['deletes'] = (int)$db->fetchColumn(
                "SELECT COUNT(*) FROM audit_logs WHERE action = 'delete' AND DATE(created_at) = CURDATE()"
            );
            $stats['logins']  = (int)$db->fetchColumn(
                "SELECT COUNT(*) FROM activity_logs WHERE activity = 'login' AND DATE(created_at) = CURDATE()"
            );
            $stats['total']   = $stats['inserts'] + $stats['updates'] + $stats['deletes'] + $stats['logins'];
        } catch (Exception $e) {}

        return $stats;
    }

    /**
     * Get per-table record counts (so user can verify data is being stored)
     */
    public static function getTableCounts()
    {
        $db = Database::getInstance();
        $tables = [
            'users', 'employees', 'departments', 'attendance', 'leave_requests',
            'payroll', 'notifications', 'audit_logs', 'employee_faces',
            'shifts', 'holidays', 'branches', 'activity_logs'
        ];
        $counts = [];
        foreach ($tables as $t) {
            try {
                $counts[$t] = (int)$db->fetchColumn("SELECT COUNT(*) FROM `{$t}`");
            } catch (Exception $e) {
                $counts[$t] = 0;
            }
        }
        return $counts;
    }
}
