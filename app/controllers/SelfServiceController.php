<?php
/**
 * Employee Self-Service Attendance Dashboard
 * Shows today's status, history, working hours, overtime, corrections
 */
class SelfServiceController extends Controller
{
    public function dashboard()
    {
        $this->requireAuth();
        $empId = Auth::employeeId();
        if (!$empId) { Flash::set('error', 'No employee profile linked.'); $this->redirect('/dashboard'); }

        $today  = date('Y-m-d');
        $db     = Database::getInstance();

        // Employee info
        $employee = $db->fetch(
            "SELECT e.*, d.name AS dept_name, s.name AS shift_name,
                    s.start_time, s.end_time, s.grace_period_minutes,
                    b.name AS branch_name
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN shifts      s ON s.id = e.shift_id
             LEFT JOIN branches    b ON b.id = e.branch_id
             WHERE e.id = :id",
            ['id' => $empId]
        );

        // Today's attendance
        $todayRec = $db->fetch(
            "SELECT * FROM attendance WHERE employee_id = :eid AND attendance_date = :d",
            ['eid' => $empId, 'd' => $today]
        );

        // This month summary
        $month      = date('Y-m');
        $monthStart = date('Y-m-01');
        $monthEnd   = date('Y-m-t');

        $monthSummary = $db->fetch(
            "SELECT
                COUNT(*) as total_days,
                SUM(CASE WHEN status IN('present','late') THEN 1 ELSE 0 END) as present_days,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_days,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                SUM(CASE WHEN status = 'leave'  THEN 1 ELSE 0 END) as leave_days,
                SUM(CASE WHEN status = 'half_day' THEN 1 ELSE 0 END) as half_days,
                COALESCE(SUM(working_hours),0) as total_hours,
                COALESCE(SUM(overtime_hours),0) as overtime_hours,
                COALESCE(SUM(late_minutes),0) as total_late_min,
                COALESCE(SUM(early_leave_minutes),0) as total_early_min
             FROM attendance
             WHERE employee_id = :eid AND attendance_date BETWEEN :s AND :e",
            ['eid' => $empId, 's' => $monthStart, 'e' => $monthEnd]
        );

        // Last 10 attendance records
        $recent = $db->fetchAll(
            "SELECT * FROM attendance
             WHERE employee_id = :eid
             ORDER BY attendance_date DESC LIMIT 10",
            ['eid' => $empId]
        );

        // Pending corrections
        $pendingCorrections = $db->count(
            'attendance_corrections',
            'employee_id = :eid AND status = "pending"',
            ['eid' => $empId]
        );

        // Weekly data for chart (last 7 working days)
        $weekData = $db->fetchAll(
            "SELECT attendance_date, status, working_hours, check_in, check_out
             FROM attendance
             WHERE employee_id = :eid AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
             ORDER BY attendance_date DESC LIMIT 10",
            ['eid' => $empId]
        );

        // Attendance rate this month
        $workingDays    = $this->countWorkingDays($monthStart, $monthEnd);
        $attendanceRate = $workingDays > 0
            ? round((($monthSummary['present_days'] ?? 0) / $workingDays) * 100, 1)
            : 0;

        $this->view('attendance/self_service', [
            'title'              => 'My Attendance',
            'pageTitle'          => 'My Attendance Dashboard',
            'employee'           => $employee,
            'todayRec'           => $todayRec,
            'monthSummary'       => $monthSummary,
            'recent'             => $recent,
            'weekData'           => $weekData,
            'pendingCorrections' => $pendingCorrections,
            'attendanceRate'     => $attendanceRate,
            'workingDays'        => $workingDays,
            'month'              => $month,
            'csrf'               => CSRF::field(),
        ]);
    }

    private function countWorkingDays($start, $end)
    {
        $count = 0;
        $cur   = new DateTime($start);
        $last  = new DateTime($end);
        while ($cur <= $last) {
            $dow = (int)$cur->format('N'); // 1=Mon, 7=Sun
            if ($dow <= 5) $count++;
            $cur->modify('+1 day');
        }
        return $count;
    }
}
