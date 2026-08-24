<?php
/**
 * Dashboard Controller
 */
class DashboardController extends Controller
{
    public function index()
    {
        $this->requireAuth();

        $db = Database::getInstance();
        $today = date('Y-m-d');
        $monthStart = date('Y-m-01');
        $monthEnd = date('Y-m-t');

        // KPIs
        $totalEmployees = $db->count('employees', 'is_active = 1');
        $presentToday = $db->count('attendance',
            'attendance_date = :d AND status IN ("present","late","half_day","remote")',
            ['d' => $today]);
        $absentToday = $db->count('attendance',
            'attendance_date = :d AND status = "absent"',
            ['d' => $today]);
        $lateToday = $db->count('attendance',
            'attendance_date = :d AND status = "late"',
            ['d' => $today]);
        $onLeaveToday = $db->count('leave_requests',
            'status = "approved" AND :d BETWEEN start_date AND end_date',
            ['d' => $today]);

        // Monthly attendance rate
        $monthlyTotal = $db->count('attendance',
            'attendance_date BETWEEN :s AND :e AND status IN ("present","late","half_day","remote")',
            ['s' => $monthStart, 'e' => $monthEnd]);
        $monthlyPossible = $db->count('attendance',
            'attendance_date BETWEEN :s AND :e',
            ['s' => $monthStart, 'e' => $monthEnd]);
        $attendanceRate = $monthlyPossible > 0 ? round(($monthlyTotal / $monthlyPossible) * 100, 1) : 0;

        // Payroll summary (current month)
        $payrollSummary = $db->fetch(
            "SELECT COUNT(*) as count, COALESCE(SUM(net_pay), 0) as total
             FROM payroll
             WHERE payroll_period = :p",
            ['p' => date('Y-m')]
        );

        // Attendance trend (last 7 days)
        $trend = $db->fetchAll(
            "SELECT attendance_date,
                    SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                    SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as on_leave
             FROM attendance
             WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY attendance_date
             ORDER BY attendance_date ASC"
        );

        // Department distribution
        $deptStats = $db->fetchAll(
            "SELECT d.name, COUNT(e.id) as employee_count
             FROM departments d
             LEFT JOIN employees e ON e.department_id = d.id AND e.is_active = 1
             WHERE d.is_active = 1
             GROUP BY d.id, d.name
             ORDER BY employee_count DESC"
        );

        // Recent attendance
        $recentAttendance = $db->fetchAll(
            "SELECT a.*, e.first_name, e.last_name, e.employee_code, e.photo, d.name as department
             FROM attendance a
             INNER JOIN employees e ON e.id = a.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE a.attendance_date = :today
             ORDER BY a.check_in DESC
             LIMIT 8",
            ['today' => $today]
        );

        // Checked-in / checked-out today
        $checkedIn  = $db->count('attendance', 'attendance_date = :d AND check_in IS NOT NULL',  ['d' => $today]);
        $checkedOut = $db->count('attendance', 'attendance_date = :d AND check_out IS NOT NULL', ['d' => $today]);

        // Overtime today
        $overtimeSummary = $db->fetch(
            "SELECT COALESCE(SUM(overtime_hours),0) as total_overtime,
                    COUNT(CASE WHEN overtime_hours > 0 THEN 1 END) as overtime_employees
             FROM attendance WHERE attendance_date = :d",
            ['d' => $today]
        );

        // Pending leave requests
        $pendingLeaves = $db->count('leave_requests', 'status = "pending"');

        // Today's check-ins count by method
        $methodStats = $db->fetchAll(
            "SELECT check_in_method, COUNT(*) as count
             FROM attendance
             WHERE attendance_date = :today AND check_in_method IS NOT NULL
             GROUP BY check_in_method",
            ['today' => $today]
        );

        // Gender distribution
        $genderStats = $db->fetchAll(
            "SELECT gender, COUNT(*) as count FROM employees WHERE is_active = 1 GROUP BY gender"
        );

        // ===========================
        // AI ANALYTICS (cached 5 min)
        // ===========================
        $ai = new AIAnalytics();
        $aiInsights = Performance::remember('dashboard_ai_insights', 300, function() use ($ai) {
            return $ai->generateDashboardInsights();
        });

        // ===========================
        // LIVE DATABASE ACTIVITY (never cached — always fresh)
        // ===========================
        $dbChanges    = DBActivity::getRecentChanges(15);
        $dbStats      = DBActivity::getTodayChangeStats();
        $tableCounts  = DBActivity::getTableCounts();

        $this->view('dashboard/index', [
            'title' => 'Dashboard',
            'pageTitle' => 'Dashboard Overview',
            'stats' => [
                'total_employees' => $totalEmployees,
                'present_today'   => $presentToday,
                'absent_today'    => $absentToday,
                'late_today'      => $lateToday,
                'on_leave'        => $onLeaveToday,
                'checked_in'      => $checkedIn,
                'checked_out'     => $checkedOut,
                'total_overtime'  => $overtimeSummary['total_overtime'] ?? 0,
                'overtime_employees' => $overtimeSummary['overtime_employees'] ?? 0,
                'attendance_rate' => $attendanceRate,
                'payroll_count'   => $payrollSummary['count'] ?? 0,
                'payroll_total'   => $payrollSummary['total'] ?? 0,
                'pending_leaves'  => $pendingLeaves,
            ],
            'trend' => $trend,
            'deptStats' => $deptStats,
            'recentAttendance' => $recentAttendance,
            'methodStats' => $methodStats,
            'genderStats' => $genderStats,
            'ai' => $aiInsights,
            'dbChanges'   => $dbChanges,
            'dbStats'     => $dbStats,
            'tableCounts' => $tableCounts,
        ]);
    }

    // ─── Real-time stats API (called every 30s by dashboard JS) ───────
    public function realtime()
    {
        $this->requireAuth();
        $today = date('Y-m-d');
        $db    = Database::getInstance();

        $total      = $db->count('employees', 'is_active = 1');
        $present    = $db->count('attendance', 'attendance_date = :d AND status IN("present","late","half_day","remote")', ['d' => $today]);
        $late       = $db->count('attendance', 'attendance_date = :d AND status = "late"', ['d' => $today]);
        $absent     = $total - $present;
        $checkedIn  = $db->count('attendance', 'attendance_date = :d AND check_in IS NOT NULL', ['d' => $today]);
        $checkedOut = $db->count('attendance', 'attendance_date = :d AND check_out IS NOT NULL', ['d' => $today]);
        $working    = $checkedIn - $checkedOut;
        $onLeave    = $db->count('leave_requests', 'status = "approved" AND :d BETWEEN start_date AND end_date', ['d' => $today]);
        $pendLeaves = $db->count('leave_requests', 'status = "pending"');
        $pendCorr   = 0;
        try { $pendCorr = $db->count('attendance_corrections', 'status = "pending"'); } catch (\Exception $e) {}

        $ot = $db->fetch("SELECT COALESCE(SUM(overtime_hours),0) as ot, COUNT(CASE WHEN overtime_hours > 0 THEN 1 END) as emp FROM attendance WHERE attendance_date = :d", ['d' => $today]);

        $this->json([
            'success'     => true,
            'timestamp'   => date('H:i:s'),
            'data'        => [
                'total'              => $total,
                'present'            => $present,
                'late'               => $late,
                'absent'             => $absent,
                'checked_in'         => $checkedIn,
                'checked_out'        => $checkedOut,
                'working_now'        => $working,
                'on_leave'           => $onLeave,
                'total_overtime'     => round($ot['ot'] ?? 0, 1),
                'overtime_employees' => (int)($ot['emp'] ?? 0),
                'attendance_pct'     => $total > 0 ? round(($present / $total) * 100, 1) : 0,
                'pending_leaves'     => $pendLeaves,
                'pending_corrections'=> $pendCorr,
            ],
        ]);
    }

    // ─── Live activity feed (last 10 attendance events) ───────────────
    public function activityFeed()
    {
        $this->requireAuth();
        $feed = Database::getInstance()->fetchAll(
            "SELECT a.id, a.check_in, a.check_out, a.status, a.check_in_method,
                    a.verified_by_face, a.working_hours,
                    e.first_name, e.last_name, e.employee_code, e.photo,
                    d.name AS department
             FROM attendance a
             INNER JOIN employees e ON e.id = a.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE a.attendance_date = :today
             ORDER BY GREATEST(COALESCE(a.check_out, '1970-01-01'), COALESCE(a.check_in, '1970-01-01')) DESC
             LIMIT 15",
            ['today' => date('Y-m-d')]
        );
        $this->json(['success' => true, 'data' => $feed]);
    }
}
