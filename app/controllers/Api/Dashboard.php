<?php
/**
 * API Dashboard Controller
 */

namespace Api;

class Dashboard
{
    public function stats()
    {
        header('Content-Type: application/json');
        $db = \Database::getInstance();
        $today = date('Y-m-d');

        $stats = [
            'date' => $today,
            'employees' => [
                'total' => $db->count('employees', 'is_active = 1'),
                'present' => $db->count('attendance', 'attendance_date = :d AND status IN ("present","late")', ['d' => $today]),
                'absent' => $db->count('attendance', 'attendance_date = :d AND status = "absent"', ['d' => $today]),
                'on_leave' => $db->count('leave_requests', 'status = "approved" AND :d BETWEEN start_date AND end_date', ['d' => $today]),
            ],
            'departments' => $db->count('departments', 'is_active = 1'),
            'pending_leaves' => $db->count('leave_requests', 'status = "pending"'),
            'payroll_month' => $db->fetch("SELECT COUNT(*) as c, COALESCE(SUM(net_pay),0) as t FROM payroll WHERE payroll_period = :p", ['p' => date('Y-m')]),
        ];

        echo json_encode(['success' => true, 'data' => $stats]);
    }
}
