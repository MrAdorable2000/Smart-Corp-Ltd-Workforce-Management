<?php
/**
 * AI Analytics Engine
 * Generates smart insights, predictions, anomaly detection, and recommendations
 * based on attendance, employee, and payroll data.
 *
 * Uses rule-based heuristics + statistical analysis (no external API needed).
 */
class AIAnalytics
{
    private $db;
    private $cache = [];
    private $cacheTtl = 300; // 5 minutes

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Generate comprehensive dashboard insights
     * Wrapped in try/catch so any single failure won't break the dashboard
     */
    public function generateDashboardInsights()
    {
        $defaults = [
            'summary'           => ['status' => 'good', 'emoji' => '✅', 'message' => 'Dashboard loaded.', 'attendance_rate' => 0, 'present' => 0, 'late' => 0, 'absent' => 0, 'on_leave' => 0, 'trend_text' => 'stable', 'avg_hours' => 0],
            'predictions'       => ['predicted_rate' => 0, 'confidence' => 'low', 'confidence_score' => 0, 'expected_present' => 0, 'expected_late' => 0, 'tomorrow' => date('l', strtotime('+1 day')), 'std_deviation' => 0, 'advice' => 'Insufficient data.', 'trend_direction' => 'stable'],
            'anomalies'         => [],
            'recommendations'   => [],
            'top_performers'    => [],
            'at_risk_employees' => [],
            'department_health' => [],
            'trend_analysis'    => ['direction' => 'unknown', 'change_pct' => 0, 'best_day' => null, 'worst_day' => null, 'insight' => 'Need more historical data.'],
            'productivity_score'=> ['score' => 0, 'grade' => 'N/A', 'factors' => []],
            'smart_alerts'      => [],
        ];

        $result = $defaults;

        $methodMap = [
            'summary'           => 'getNarrativeSummary',
            'predictions'       => 'predictTomorrowAttendance',
            'anomalies'         => 'detectAnomalies',
            'recommendations'   => 'generateRecommendations',
            'top_performers'    => 'getTopPerformers',
            'at_risk_employees' => 'getAtRiskEmployees',
            'department_health' => 'getDepartmentHealth',
            'trend_analysis'    => 'analyzeTrends',
            'productivity_score'=> 'calculateProductivityScore',
            'smart_alerts'      => 'getSmartAlerts',
        ];
        foreach ($methodMap as $key => $method) {
            try {
                $result[$key] = $this->$method();
            } catch (Throwable $e) {
                // Keep default for this method, continue with others
                error_log("AIAnalytics[{$key}] error: " . $e->getMessage());
            }
        }

        return $result;
    }

    /**
     * Generate a natural-language summary of today's status
     */
    public function getNarrativeSummary()
    {
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        $todayStats = $this->db->fetch(
            "SELECT
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as on_leave,
                AVG(working_hours) as avg_hours
             FROM attendance WHERE attendance_date = :d",
            ['d' => $today]
        );

        $yesterdayStats = $this->db->fetch(
            "SELECT
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late
             FROM attendance WHERE attendance_date = :d",
            ['d' => $yesterday]
        );

        $totalEmployees = $this->db->count('employees', 'is_active = 1');
        $presentToday = (int)($todayStats['present'] ?? 0);
        $lateToday = (int)($todayStats['late'] ?? 0);
        $absentToday = (int)($todayStats['absent'] ?? 0);
        $onLeaveToday = (int)($todayStats['on_leave'] ?? 0);

        $attendanceRate = $totalEmployees > 0
            ? round((($presentToday + $lateToday) / $totalEmployees) * 100, 1)
            : 0;

        // Compare with yesterday
        $yesterdayPresent = (int)($yesterdayStats['present'] ?? 0) + (int)($yesterdayStats['late'] ?? 0);
        $change = $presentToday + $lateToday - $yesterdayPresent;
        $trendText = $change > 0 ? "up {$change} from yesterday" : ($change < 0 ? "down " . abs($change) . " from yesterday" : "stable compared to yesterday");

        // Determine health status
        if ($attendanceRate >= 90) {
            $status = 'excellent';
            $emoji = '🌟';
            $message = "Outstanding attendance today! {$attendanceRate}% of your workforce is present.";
        } elseif ($attendanceRate >= 75) {
            $status = 'good';
            $emoji = '✅';
            $message = "Healthy attendance at {$attendanceRate}%. Things are running smoothly.";
        } elseif ($attendanceRate >= 50) {
            $status = 'warning';
            $emoji = '⚠️';
            $message = "Attendance is at {$attendanceRate}%. Consider following up with absent employees.";
        } else {
            $status = 'critical';
            $emoji = '🚨';
            $message = "Low attendance ({$attendanceRate}%). Immediate attention required.";
        }

        return [
            'status'          => $status,
            'emoji'           => $emoji,
            'message'         => $message,
            'attendance_rate' => $attendanceRate,
            'present'         => $presentToday,
            'late'            => $lateToday,
            'absent'          => $absentToday,
            'on_leave'        => $onLeaveToday,
            'trend_text'      => $trendText,
            'avg_hours'       => round($todayStats['avg_hours'] ?? 0, 1),
        ];
    }

    /**
     * Predict tomorrow's attendance using 7-day weighted moving average
     */
    public function predictTomorrowAttendance()
    {
        $history = $this->db->fetchAll(
            "SELECT attendance_date,
                    COUNT(*) as total,
                    SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as attended,
                    SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
             FROM attendance
             WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 13 DAY)
             GROUP BY attendance_date
             ORDER BY attendance_date DESC
             LIMIT 14"
        );

        if (count($history) < 3) {
            return [
                'predicted_rate'    => 0,
                'confidence'        => 'low',
                'confidence_score'  => 0,
                'expected_present'  => 0,
                'expected_late'     => 0,
                'tomorrow'          => date('l', strtotime('+1 day')),
                'std_deviation'     => 0,
                'advice'            => 'Insufficient historical data for accurate prediction. Need at least 3 days of attendance records.',
                'trend_direction'   => 'stable',
                'message'           => 'Insufficient historical data for accurate prediction.'
            ];
        }

        // Weighted moving average (recent days weigh more)
        $weights = [0.25, 0.20, 0.15, 0.12, 0.10, 0.08, 0.05, 0.05];
        $weightedSum = 0;
        $weightTotal = 0;
        $rates = [];
        $lateRates = [];

        for ($i = 0; $i < min(count($history), count($weights)); $i++) {
            $row = $history[$i];
            $total = max((int)$row['total'], 1);
            $rate = ((int)$row['attended'] / $total) * 100;
            $rates[] = $rate;
            $lateRates[] = ((int)$row['late'] / $total) * 100;
            $weightedSum += $rate * $weights[$i];
            $weightTotal += $weights[$i];
        }

        $predictedRate = round($weightedSum / $weightTotal, 1);

        // Calculate variance for confidence
        $avg = array_sum($rates) / count($rates);
        $variance = 0;
        foreach ($rates as $r) $variance += pow($r - $avg, 2);
        $stdDev = sqrt($variance / count($rates));

        $confidence = $stdDev < 5 ? 'high' : ($stdDev < 12 ? 'medium' : 'low');

        // Predict day of week pattern
        $tomorrow = date('l', strtotime('+1 day'));
        $totalEmployees = $this->db->count('employees', 'is_active = 1');
        $expectedPresent = round(($predictedRate / 100) * $totalEmployees);

        // Average late rate prediction
        $avgLateRate = array_sum($lateRates) / count($lateRates);
        $expectedLate = round(($avgLateRate / 100) * $totalEmployees);

        if ($predictedRate >= 85) {
            $advice = "Tomorrow looks strong. Keep up the good momentum!";
        } elseif ($predictedRate >= 70) {
            $advice = "Tomorrow should be a normal day. Monitor late arrivals.";
        } elseif ($predictedRate >= 50) {
            $advice = "Tomorrow may have lower attendance. Consider sending reminders tonight.";
        } else {
            $advice = "Tomorrow is predicted to have low attendance. Take proactive measures.";
        }

        return [
            'predicted_rate'    => $predictedRate,
            'confidence'        => $confidence,
            'confidence_score'  => max(0, round(100 - $stdDev * 3, 1)),
            'expected_present'  => $expectedPresent,
            'expected_late'     => $expectedLate,
            'tomorrow'          => $tomorrow,
            'std_deviation'     => round($stdDev, 2),
            'advice'            => $advice,
            'trend_direction'   => end($rates) > reset($rates) ? 'improving' : (end($rates) < reset($rates) ? 'declining' : 'stable'),
        ];
    }

    /**
     * Detect anomalies in attendance patterns
     */
    public function detectAnomalies()
    {
        $anomalies = [];

        // Check for unusual absence spike today
        $today = date('Y-m-d');
        $todayAbsent = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM attendance WHERE attendance_date = :d AND status = 'absent'",
            ['d' => $today]
        );

        $avgAbsent = (float)$this->db->fetchColumn(
            "SELECT AVG(absent_count) FROM (
                SELECT attendance_date, COUNT(*) as absent_count
                FROM attendance
                WHERE status = 'absent' AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY attendance_date
            ) t"
        );

        if ($avgAbsent > 0 && $todayAbsent > $avgAbsent * 1.5) {
            $anomalies[] = [
                'type' => 'absence_spike',
                'severity' => $todayAbsent > $avgAbsent * 2 ? 'critical' : 'warning',
                'title' => 'Unusual Absence Spike Detected',
                'description' => "Today has {$todayAbsent} absences vs 30-day average of " . round($avgAbsent, 1) . ". This is " . round(($todayAbsent / $avgAbsent - 1) * 100) . "% higher than normal.",
                'icon' => 'bi-exclamation-triangle',
                'action' => 'Review absent employees and send follow-up notifications.'
            ];
        }

        // Check for late arrival pattern by department
        $deptLate = $this->db->fetchAll(
            "SELECT d.name as dept, COUNT(*) as late_count
             FROM attendance a
             INNER JOIN employees e ON e.id = a.employee_id
             INNER JOIN departments d ON d.id = e.department_id
             WHERE a.status = 'late' AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY d.id
             HAVING late_count > 3
             ORDER BY late_count DESC"
        );

        foreach ($deptLate as $dl) {
            $anomalies[] = [
                'type' => 'late_pattern',
                'severity' => 'warning',
                'title' => "Repeated Lateness in {$dl['dept']}",
                'description' => "{$dl['dept']} department has {$dl['late_count']} late arrivals in the last 7 days. This may indicate a scheduling or shift issue.",
                'icon' => 'bi-clock-history',
                'action' => "Review shift timing for {$dl['dept']} department."
            ];
        }

        // Check for employees with consistently low hours
        $lowHours = $this->db->fetchAll(
            "SELECT e.id, e.employee_code, e.first_name, e.last_name,
                    AVG(a.working_hours) as avg_hours, COUNT(*) as days
             FROM employees e
             INNER JOIN attendance a ON a.employee_id = e.id
             WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
               AND a.working_hours IS NOT NULL
             GROUP BY e.id
             HAVING avg_hours < 6 AND days >= 3"
        );

        foreach ($lowHours as $lh) {
            $anomalies[] = [
                'type' => 'low_hours',
                'severity' => 'warning',
                'title' => 'Low Working Hours Pattern',
                'description' => "{$lh['first_name']} {$lh['last_name']} ({$lh['employee_code']}) averages only " . round($lh['avg_hours'], 1) . " hours/day over {$lh['days']} days. This is below the 8-hour standard.",
                'icon' => 'bi-hourglass-bottom',
                'action' => 'Schedule a check-in meeting with this employee.'
            ];
        }

        // Check for unusual overtime
        $otAlerts = $this->db->fetchAll(
            "SELECT e.employee_code, e.first_name, e.last_name,
                    SUM(a.overtime_hours) as total_ot
             FROM attendance a
             INNER JOIN employees e ON e.id = a.employee_id
             WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
               AND a.overtime_hours > 0
             GROUP BY e.id
             HAVING total_ot > 15"
        );

        foreach ($otAlerts as $ot) {
            $anomalies[] = [
                'type' => 'high_overtime',
                'severity' => 'info',
                'title' => 'High Overtime Alert',
                'description' => "{$ot['first_name']} {$ot['last_name']} has logged " . round($ot['total_ot'], 1) . " hours of overtime in 7 days. Verify workload distribution.",
                'icon' => 'bi-clock-fill',
                'action' => 'Review workload assignment for this employee.'
            ];
        }

        return $anomalies;
    }

    /**
     * Generate smart recommendations
     */
    public function generateRecommendations()
    {
        $recs = [];

        // Late arrivals recommendation
        $lateToday = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM attendance WHERE attendance_date = CURDATE() AND status = 'late'"
        );

        $weeklyLate = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM attendance WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND status = 'late'"
        );

        if ($lateToday > 3) {
            $recs[] = [
                'priority' => 'high',
                'category' => 'Attendance',
                'icon' => 'bi-clock-alert',
                'title' => 'Address Late Arrivals',
                'description' => "{$lateToday} employees arrived late today and {$weeklyLate} in the past week. Consider reviewing shift start times or sending pre-shift reminders.",
                'impact' => 'Could improve attendance rate by 5-10%',
                'action_url' => BASE_URL . '/attendance?date=' . date('Y-m-d'),
                'action_label' => 'View Late Employees'
            ];
        }

        // Pending leave requests
        $pendingLeaves = (int)$this->db->fetchColumn(
            "SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'"
        );

        if ($pendingLeaves > 0) {
            $recs[] = [
                'priority' => 'medium',
                'category' => 'Leave Management',
                'icon' => 'bi-airplane-engines',
                'title' => 'Review Pending Leave Requests',
                'description' => "{$pendingLeaves} leave request(s) are awaiting approval. Timely approval improves employee satisfaction and helps with workforce planning.",
                'impact' => 'Improves HR response time and employee morale',
                'action_url' => BASE_URL . '/leaves?status=pending',
                'action_label' => 'Review Leave Requests'
            ];
        }

        // Face enrollment recommendation
        $totalEmployees = (int)$this->db->count('employees', 'is_active = 1');
        $enrolledEmployees = (int)$this->db->count('employees', 'is_active = 1 AND face_enrolled = 1');
        $unenrolled = max(0, $totalEmployees - $enrolledEmployees);

        if ($unenrolled > 0 && $totalEmployees > 0) {
            $enrollmentRate = round(($enrolledEmployees / $totalEmployees) * 100, 1);
            $recs[] = [
                'priority' => $enrollmentRate < 50 ? 'high' : 'medium',
                'category' => 'Security',
                'icon' => 'bi-camera-video',
                'title' => 'Complete Face Enrollment',
                'description' => "{$unenrolled} of {$totalEmployees} employees haven't enrolled for face recognition ({$enrollmentRate}% enrolled). This affects automated attendance accuracy.",
                'impact' => 'Enables 100% automated attendance tracking',
                'action_url' => BASE_URL . '/employees',
                'action_label' => 'Enroll Employees'
            ];
        }

        // Payroll processing recommendation
        $dayOfMonth = (int)date('j');
        $currentMonth = date('Y-m');
        $payrollProcessed = $this->db->count('payroll', 'payroll_period = :p', ['p' => $currentMonth]);

        if ($dayOfMonth >= 25 && $payrollProcessed == 0) {
            $recs[] = [
                'priority' => 'high',
                'category' => 'Payroll',
                'icon' => 'bi-wallet2',
                'title' => 'Process Monthly Payroll',
                'description' => "It's day {$dayOfMonth} of the month and payroll for " . date('F Y') . " hasn't been processed yet. Generate payroll to ensure timely payment.",
                'impact' => 'Ensures on-time salary disbursement',
                'action_url' => BASE_URL . '/payroll/generate?period=' . $currentMonth,
                'action_label' => 'Generate Payroll'
            ];
        }

        // Department balance recommendation
        $deptBalance = $this->db->fetchAll(
            "SELECT d.name, COUNT(e.id) as count
             FROM departments d
             LEFT JOIN employees e ON e.department_id = d.id AND e.is_active = 1
             WHERE d.is_active = 1
             GROUP BY d.id
             ORDER BY count DESC"
        );

        if (!empty($deptBalance)) {
            $max = $deptBalance[0];
            $min = end($deptBalance);
            if ($max['count'] > 0 && $min['count'] == 0) {
                $recs[] = [
                    'priority' => 'low',
                    'category' => 'Workforce Planning',
                    'icon' => 'bi-people',
                    'title' => 'Review Department Staffing',
                    'description' => "{$max['name']} has {$max['count']} employees while {$min['name']} has none. Consider workforce redistribution if appropriate.",
                    'impact' => 'Better workload distribution',
                    'action_url' => BASE_URL . '/departments',
                    'action_label' => 'View Departments'
                ];
            }
        }

        return $recs;
    }

    /**
     * Identify top performers based on attendance and punctuality
     */
    public function getTopPerformers($limit = 5)
    {
        return $this->db->fetchAll(
            "SELECT e.id, e.employee_code, e.first_name, e.last_name, e.photo,
                    d.name as department,
                    COUNT(a.id) as total_days,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present_days,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days,
                    AVG(a.working_hours) as avg_hours,
                    SUM(a.overtime_hours) as total_ot,
                    ROUND((SUM(CASE WHEN a.status IN ('present','late') THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as attendance_rate
             FROM employees e
             INNER JOIN attendance a ON a.employee_id = e.id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
               AND e.is_active = 1
             GROUP BY e.id
             HAVING total_days >= 2
             ORDER BY attendance_rate DESC, late_days ASC, total_ot DESC
             LIMIT {$limit}"
        );
    }

    /**
     * Identify employees at risk (frequent absence/lateness)
     */
    public function getAtRiskEmployees($limit = 5)
    {
        return $this->db->fetchAll(
            "SELECT e.id, e.employee_code, e.first_name, e.last_name, e.photo,
                    d.name as department,
                    COUNT(a.id) as total_days,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent_days,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_days,
                    ROUND((SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as absence_rate,
                    ROUND((SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 1) as late_rate
             FROM employees e
             INNER JOIN attendance a ON a.employee_id = e.id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
               AND e.is_active = 1
             GROUP BY e.id
             HAVING total_days >= 2 AND (absence_rate > 20 OR late_rate > 30)
             ORDER BY absence_rate DESC, late_rate DESC
             LIMIT {$limit}"
        );
    }

    /**
     * Calculate department health scores
     */
    public function getDepartmentHealth()
    {
        return $this->db->fetchAll(
            "SELECT d.id, d.name,
                    COUNT(DISTINCT e.id) as employee_count,
                    COUNT(a.id) as total_records,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
                    AVG(a.working_hours) as avg_hours
             FROM departments d
             LEFT JOIN employees e ON e.department_id = d.id AND e.is_active = 1
             LEFT JOIN attendance a ON a.employee_id = e.id AND a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             WHERE d.is_active = 1
             GROUP BY d.id, d.name
             ORDER BY d.name"
        );
    }

    /**
     * Analyze attendance trends over 30 days
     */
    public function analyzeTrends()
    {
        $trend = $this->db->fetchAll(
            "SELECT DATE(a.attendance_date) as date,
                    DAYNAME(a.attendance_date) as day_name,
                    COUNT(*) as total,
                    SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                    SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                    SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
                    AVG(a.working_hours) as avg_hours
             FROM attendance a
             WHERE a.attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
             GROUP BY DATE(a.attendance_date), DAYNAME(a.attendance_date)
             ORDER BY date ASC"
        );

        if (count($trend) < 2) {
            return [
                'direction' => 'unknown',
                'change_pct' => 0,
                'best_day' => null,
                'worst_day' => null,
                'insight' => 'Need more historical data for trend analysis.'
            ];
        }

        $firstHalf = array_slice($trend, 0, floor(count($trend) / 2));
        $secondHalf = array_slice($trend, floor(count($trend) / 2));

        $avgFirst = $this->avgAttendanceRate($firstHalf);
        $avgSecond = $this->avgAttendanceRate($secondHalf);

        $changePct = $avgFirst > 0 ? round((($avgSecond - $avgFirst) / $avgFirst) * 100, 1) : 0;

        // Find best and worst day
        $best = null; $worst = null;
        foreach ($trend as $t) {
            $rate = $t['total'] > 0 ? ($t['present'] / $t['total']) * 100 : 0;
            if (!$best || $rate > $best['rate']) {
                $best = ['date' => $t['date'], 'day' => $t['day_name'], 'rate' => round($rate, 1)];
            }
            if (!$worst || $rate < $worst['rate']) {
                $worst = ['date' => $t['date'], 'day' => $t['day_name'], 'rate' => round($rate, 1)];
            }
        }

        $direction = $changePct > 2 ? 'improving' : ($changePct < -2 ? 'declining' : 'stable');

        $insights = [
            'improving' => "Attendance is trending upward ({$changePct}% improvement). Keep up the good work!",
            'declining' => "Attendance has declined {$changePct}% over the period. Investigate the cause.",
            'stable' => "Attendance is stable with no significant changes ({$changePct}% variance)."
        ];

        return [
            'direction' => $direction,
            'change_pct' => $changePct,
            'best_day' => $best,
            'worst_day' => $worst,
            'insight' => $insights[$direction],
            'avg_rate_first_half' => round($avgFirst, 1),
            'avg_rate_second_half' => round($avgSecond, 1),
        ];
    }

    /**
     * Calculate overall productivity score (0-100)
     */
    public function calculateProductivityScore()
    {
        $stats = $this->db->fetch(
            "SELECT
                COUNT(*) as total,
                SUM(CASE WHEN status IN ('present','late') THEN 1 ELSE 0 END) as attended,
                SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as on_time,
                AVG(working_hours) as avg_hours,
                AVG(overtime_hours) as avg_ot
             FROM attendance
             WHERE attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)"
        );

        if (!$stats || $stats['total'] == 0) {
            return ['score' => 0, 'grade' => 'N/A', 'factors' => []];
        }

        $attendanceRate = ($stats['attended'] / $stats['total']) * 100;
        $punctualityRate = $stats['attended'] > 0 ? ($stats['on_time'] / $stats['attended']) * 100 : 0;
        $hoursScore = min(100, (($stats['avg_hours'] ?? 0) / 8) * 100);
        $otScore = min(100, (($stats['avg_ot'] ?? 0) / 2) * 100);

        $score = round(
            ($attendanceRate * 0.35) +
            ($punctualityRate * 0.30) +
            ($hoursScore * 0.25) +
            ($otScore * 0.10)
        );

        $grade = $score >= 90 ? 'A+' : ($score >= 80 ? 'A' : ($score >= 70 ? 'B' : ($score >= 60 ? 'C' : ($score >= 50 ? 'D' : 'F'))));

        return [
            'score' => $score,
            'grade' => $grade,
            'factors' => [
                ['name' => 'Attendance Rate', 'value' => round($attendanceRate, 1), 'weight' => '35%'],
                ['name' => 'Punctuality', 'value' => round($punctualityRate, 1), 'weight' => '30%'],
                ['name' => 'Working Hours', 'value' => round($hoursScore, 1), 'weight' => '25%'],
                ['name' => 'Overtime Engagement', 'value' => round($otScore, 1), 'weight' => '10%'],
            ]
        ];
    }

    /**
     * Generate smart alerts (urgent items)
     */
    public function getSmartAlerts()
    {
        $alerts = [];

        // Critical: More than 30% absent today
        $today = date('Y-m-d');
        $totalEmps = $this->db->count('employees', 'is_active = 1');
        $absentToday = $this->db->count('attendance', 'attendance_date = :d AND status = "absent"', ['d' => $today]);

        if ($totalEmps > 0 && ($absentToday / $totalEmps) > 0.30) {
            $alerts[] = [
                'level' => 'critical',
                'icon' => 'bi-exclamation-octagon-fill',
                'title' => 'High Absence Rate',
                'message' => "{$absentToday} employees absent today (" . round(($absentToday / $totalEmps) * 100) . "% of workforce)"
            ];
        }

        // Info: New enrollments
        $newEmps = $this->db->count('employees', 'date_joined >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND is_active = 1');
        if ($newEmps > 0) {
            $alerts[] = [
                'level' => 'info',
                'icon' => 'bi-person-plus-fill',
                'title' => 'New Employees',
                'message' => "{$newEmps} new employee(s) joined this week. Don't forget to enroll their faces!"
            ];
        }

        // Warning: Pending approvals
        $pendingLeaves = $this->db->count('leave_requests', 'status = "pending"');
        if ($pendingLeaves >= 3) {
            $alerts[] = [
                'level' => 'warning',
                'icon' => 'bi-hourglass-split',
                'title' => 'Pending Approvals',
                'message' => "{$pendingLeaves} leave requests need your attention"
            ];
        }

        return $alerts;
    }

    /**
     * Helper: Calculate average attendance rate from trend data
     */
    private function avgAttendanceRate($data)
    {
        if (empty($data)) return 0;
        $sum = 0;
        foreach ($data as $row) {
            if ($row['total'] > 0) {
                $sum += ($row['present'] / $row['total']) * 100;
            }
        }
        return $sum / count($data);
    }
}
