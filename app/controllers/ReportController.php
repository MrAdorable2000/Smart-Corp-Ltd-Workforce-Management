<?php
class ReportController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('generate_reports');
        $this->view('reports/index', ['title' => 'Reports', 'pageTitle' => 'Reports & Analytics']);
    }

    public function attendance()
    {
        $this->requireAuth();
        $this->requirePermission('generate_reports');

        $startDate = $this->query('start_date', date('Y-m-01'));
        $endDate = $this->query('end_date', date('Y-m-d'));
        $deptId = $this->query('department_id', '');

        $sql = "SELECT e.employee_code, e.first_name, e.last_name, d.name AS department,
                       COUNT(a.id) as total_days,
                       SUM(CASE WHEN a.status = 'present' THEN 1 ELSE 0 END) as present,
                       SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late,
                       SUM(CASE WHEN a.status = 'absent' THEN 1 ELSE 0 END) as absent,
                       SUM(CASE WHEN a.status = 'leave' THEN 1 ELSE 0 END) as on_leave,
                       SUM(CASE WHEN a.status = 'half_day' THEN 1 ELSE 0 END) as half_day,
                       SUM(a.working_hours) as total_hours,
                       SUM(a.overtime_hours) as overtime,
                       SUM(a.late_minutes) as total_late
                FROM employees e
                LEFT JOIN attendance a ON a.employee_id = e.id AND a.attendance_date BETWEEN :s AND :e
                LEFT JOIN departments d ON d.id = e.department_id
                WHERE e.is_active = 1";
        $params = ['s' => $startDate, 'e' => $endDate];
        if ($deptId) { $sql .= " AND e.department_id = :d"; $params['d'] = $deptId; }
        $sql .= " GROUP BY e.id ORDER BY e.first_name";
        $report = Database::getInstance()->fetchAll($sql, $params);

        $departments = Database::getInstance()->fetchAll("SELECT * FROM departments WHERE is_active = 1");

        $this->view('reports/attendance', [
            'title' => 'Attendance Report',
            'pageTitle' => 'Attendance Report',
            'report' => $report,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'departments' => $departments,
            'deptId' => $deptId,
            'csrf' => CSRF::field()
        ]);
    }

    public function employees()
    {
        $this->requireAuth();
        $this->requirePermission('generate_reports');

        $employees = Database::getInstance()->fetchAll(
            "SELECT e.*, d.name AS department, b.name AS branch, s.name AS shift,
                    (SELECT COUNT(*) FROM attendance a WHERE a.employee_id = e.id AND a.status IN ('present','late')) as present_days,
                    (SELECT COUNT(*) FROM attendance a WHERE a.employee_id = e.id AND a.status = 'absent') as absent_days
             FROM employees e
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN branches b ON b.id = e.branch_id
             LEFT JOIN shifts s ON s.id = e.shift_id
             WHERE e.is_active = 1
             ORDER BY e.first_name"
        );

        $this->view('reports/employees', [
            'title' => 'Employee Report',
            'pageTitle' => 'Employee Directory Report',
            'employees' => $employees,
            'csrf' => CSRF::field()
        ]);
    }

    public function payroll()
    {
        $this->requireAuth();
        $this->requirePermission('generate_reports');

        $period = $this->query('period', date('Y-m'));
        $payrolls = Database::getInstance()->fetchAll(
            "SELECT p.*, e.employee_code, e.first_name, e.last_name, d.name AS department
             FROM payroll p
             INNER JOIN employees e ON e.id = p.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE p.payroll_period = :p
             ORDER BY e.first_name",
            ['p' => $period]
        );

        $this->view('reports/payroll', [
            'title' => 'Payroll Report',
            'pageTitle' => 'Payroll Report - ' . date('F Y', strtotime($period . '-01')),
            'payrolls' => $payrolls,
            'period' => $period,
            'csrf' => CSRF::field()
        ]);
    }

    public function leaves()
    {
        $this->requireAuth();
        $this->requirePermission('generate_reports');

        $startDate = $this->query('start_date', date('Y-m-01'));
        $endDate = $this->query('end_date', date('Y-m-d'));

        $leaves = Database::getInstance()->fetchAll(
            "SELECT lr.*, lt.name AS leave_type,
                    e.employee_code, e.first_name, e.last_name, d.name AS department,
                    u.name AS approved_by_name
             FROM leave_requests lr
             INNER JOIN leave_types lt ON lt.id = lr.leave_type_id
             INNER JOIN employees e ON e.id = lr.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN users u ON u.id = lr.approved_by
             WHERE lr.created_at BETWEEN :s AND :e
             ORDER BY lr.created_at DESC",
            ['s' => $startDate . ' 00:00:00', 'e' => $endDate . ' 23:59:59']
        );

        $this->view('reports/leaves', [
            'title' => 'Leave Report',
            'pageTitle' => 'Leave Report',
            'leaves' => $leaves,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'csrf' => CSRF::field()
        ]);
    }

    public function department()
    {
        $this->requireAuth();
        $this->requirePermission('generate_reports');

        $depts = Database::getInstance()->fetchAll(
            "SELECT d.*,
                    (SELECT COUNT(*) FROM employees e WHERE e.department_id = d.id AND e.is_active = 1) as employee_count,
                    (SELECT COUNT(*) FROM attendance a INNER JOIN employees e ON e.id = a.employee_id
                     WHERE e.department_id = d.id AND a.attendance_date = CURDATE() AND a.status IN ('present','late')) as present_today,
                    (SELECT COUNT(*) FROM attendance a INNER JOIN employees e ON e.id = a.employee_id
                     WHERE e.department_id = d.id AND a.attendance_date = CURDATE() AND a.status = 'absent') as absent_today
             FROM departments d WHERE d.is_active = 1 ORDER BY d.name"
        );

        $this->view('reports/department', [
            'title' => 'Department Report',
            'pageTitle' => 'Department Statistics',
            'departments' => $depts,
            'csrf' => CSRF::field()
        ]);
    }

    public function export()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('generate_reports');

        $type = $this->input('type', 'attendance');
        $format = $this->input('format', 'csv');

        // Generate data based on type
        switch ($type) {
            case 'attendance':
                $data = Database::getInstance()->fetchAll(
                    "SELECT a.attendance_date, e.employee_code, e.first_name, e.last_name,
                            a.check_in, a.check_out, a.working_hours, a.late_minutes, a.status, a.check_in_method
                     FROM attendance a INNER JOIN employees e ON e.id = a.employee_id
                     ORDER BY a.attendance_date DESC LIMIT 1000"
                );
                $filename = 'attendance_report';
                $headers = ['Date', 'Code', 'First Name', 'Last Name', 'Check In', 'Check Out', 'Hours', 'Late Min', 'Status', 'Method'];
                break;
            case 'employees':
                $data = Database::getInstance()->fetchAll(
                    "SELECT e.employee_code, e.first_name, e.last_name, e.email, e.phone,
                            d.name AS department, e.position, e.employment_status, e.salary
                     FROM employees e LEFT JOIN departments d ON d.id = e.department_id
                     WHERE e.is_active = 1"
                );
                $filename = 'employees_report';
                $headers = ['Code', 'First Name', 'Last Name', 'Email', 'Phone', 'Department', 'Position', 'Status', 'Salary'];
                break;
            case 'payroll':
                $period = $this->input('period', date('Y-m'));
                $data = Database::getInstance()->fetchAll(
                    "SELECT p.payroll_period, e.employee_code, e.first_name, e.last_name,
                            p.basic_salary, p.allowances, p.overtime_pay, p.gross_pay,
                            p.tax_deduction, p.total_deductions, p.net_pay, p.status
                     FROM payroll p INNER JOIN employees e ON e.id = p.employee_id
                     WHERE p.payroll_period = :p",
                    ['p' => $period]
                );
                $filename = 'payroll_report_' . $period;
                $headers = ['Period', 'Code', 'First', 'Last', 'Basic', 'Allowance', 'OT', 'Gross', 'Tax', 'Total Ded', 'Net', 'Status'];
                break;
            default:
                $data = []; $filename = 'report'; $headers = [];
        }

        if ($format === 'csv') {
            $this->exportCsv($data, $filename, $headers);
        } elseif ($format === 'excel') {
            $this->exportCsv($data, $filename, $headers, true);
        } else {
            $this->exportHtmlPdf($data, $filename, $headers);
        }
    }

    private function exportCsv($data, $filename, $headers, $excel = false)
    {
        header('Content-Type: ' . ($excel ? 'application/vnd.ms-excel' : 'text/csv'));
        header('Content-Disposition: attachment; filename="' . $filename . '.' . ($excel ? 'xls' : 'csv') . '"');

        $out = fopen('php://output', 'w');
        if ($excel) {
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM for Excel UTF-8
        }
        fputcsv($out, $headers);
        foreach ($data as $row) {
            fputcsv($out, array_values($row));
        }
        fclose($out);
        exit;
    }

    private function exportHtmlPdf($data, $filename, $headers)
    {
        // Simple HTML print view (production would use DomPDF)
        echo '<!DOCTYPE html><html><head><title>' . $filename . '</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '</head><body class="p-4"><div class="container">';
        echo '<h3>' . ucfirst(str_replace('_', ' ', $filename)) . '</h3>';
        echo '<p>Generated: ' . date('M d, Y H:i') . '</p>';
        echo '<table class="table table-bordered table-sm"><thead><tr>';
        foreach ($headers as $h) echo '<th>' . htmlspecialchars($h) . '</th>';
        echo '</tr></thead><tbody>';
        foreach ($data as $row) {
            echo '<tr>';
            foreach (array_values($row) as $v) echo '<td>' . htmlspecialchars($v ?? '') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '<script>window.print()</script>';
        echo '</div></body></html>';
        exit;
    }
}
