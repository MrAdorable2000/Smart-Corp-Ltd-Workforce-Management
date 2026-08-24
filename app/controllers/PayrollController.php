<?php
class PayrollController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $this->requirePermission('manage_payroll');

        $period = $this->query('period', date('Y-m'));
        $payrolls = Database::getInstance()->fetchAll(
            "SELECT p.*, e.employee_code, e.first_name, e.last_name, e.photo, d.name AS department
             FROM payroll p
             INNER JOIN employees e ON e.id = p.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             WHERE p.payroll_period = :p
             ORDER BY e.first_name",
            ['p' => $period]
        );

        $summary = [
            'count' => count($payrolls),
            'gross' => array_sum(array_column($payrolls, 'gross_pay')),
            'net'   => array_sum(array_column($payrolls, 'net_pay')),
            'tax'   => array_sum(array_column($payrolls, 'tax_deduction')),
        ];

        $this->view('payroll/index', [
            'title' => 'Payroll',
            'pageTitle' => 'Payroll Management',
            'payrolls' => $payrolls,
            'period' => $period,
            'summary' => $summary,
            'csrf' => CSRF::field()
        ]);
    }

    public function generate()
    {
        $this->requireAuth();
        $this->requirePermission('manage_payroll');

        $period = $this->query('period', date('Y-m'));
        $this->view('payroll/generate', [
            'title' => 'Generate Payroll',
            'pageTitle' => 'Generate Payroll for ' . date('F Y', strtotime($period . '-01')),
            'period' => $period,
            'csrf' => CSRF::field()
        ]);
    }

    public function process()
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_payroll');

        $period = $this->input('period', date('Y-m'));
        $startDate = date('Y-m-01', strtotime($period . '-01'));
        $endDate = date('Y-m-t', strtotime($period . '-01'));

        $employees = Database::getInstance()->fetchAll(
            "SELECT * FROM employees WHERE is_active = 1 AND employment_status IN ('permanent','contract','probation')"
        );

        $processed = 0; $totalNet = 0;
        foreach ($employees as $emp) {
            // Skip if payroll already exists
            $existing = Database::getInstance()->fetch(
                "SELECT id FROM payroll WHERE employee_id = :eid AND payroll_period = :p",
                ['eid' => $emp['id'], 'p' => $period]
            );
            if ($existing) continue;

            // Get attendance stats
            $stats = Database::getInstance()->fetch(
                "SELECT COUNT(*) as total,
                        SUM(CASE WHEN status IN ('present','late','half_day','remote') THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as on_leave,
                        SUM(working_hours) as hours,
                        SUM(overtime_hours) as ot_hours,
                        SUM(CASE WHEN status = 'late' THEN 1 ELSE 0 END) as late_count
                 FROM attendance
                 WHERE employee_id = :eid AND attendance_date BETWEEN :s AND :e",
                ['eid' => $emp['id'], 's' => $startDate, 'e' => $endDate]
            );

            $workingDays = $stats['total'] ?: 22;
            $presentDays = $stats['present'] ?: 0;
            $absentDays = $stats['absent'] ?: 0;
            $leaveDays = $stats['on_leave'] ?: 0;
            $otHours = $stats['ot_hours'] ?: 0;
            $lateCount = $stats['late_count'] ?: 0;

            // Calculate salary components
            $basicSalary = (float)$emp['salary'];
            $dailyRate = $workingDays > 0 ? $basicSalary / $workingDays : 0;
            $proratedSalary = $dailyRate * $presentDays;
            $allowances = (float)$emp['allowance'];
            $overtimePay = $otHours * ($basicSalary / 22 / 8) * 1.5; // OT rate 1.5x
            $bonus = 0;

            $grossPay = $proratedSalary + $allowances + $overtimePay + $bonus;

            $taxDeduction = $grossPay * ($emp['tax_rate'] / 100);
            $insuranceDeduction = $grossPay * 0.02; // 2% insurance
            $penaltyDeduction = $lateCount * 5; // $5 per late
            $otherDeductions = 0;
            $totalDeductions = $taxDeduction + $insuranceDeduction + $penaltyDeduction + $otherDeductions;

            $netPay = $grossPay - $totalDeductions;

            $payrollId = Database::getInstance()->insert('payroll', [
                'employee_id' => $emp['id'],
                'payroll_period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'basic_salary' => $proratedSalary,
                'allowances' => $allowances,
                'overtime_pay' => $overtimePay,
                'bonus' => $bonus,
                'gross_pay' => $grossPay,
                'tax_deduction' => $taxDeduction,
                'insurance_deduction' => $insuranceDeduction,
                'penalty_deduction' => $penaltyDeduction,
                'other_deductions' => $otherDeductions,
                'total_deductions' => $totalDeductions,
                'net_pay' => $netPay,
                'working_days' => $workingDays,
                'present_days' => $presentDays,
                'absent_days' => $absentDays,
                'leave_days' => $leaveDays,
                'overtime_hours' => $otHours,
                'late_count' => $lateCount,
                'status' => 'processed',
                'processed_by' => Auth::id()
            ]);

            $processed++;
            $totalNet += $netPay;
        }

        Auth::audit('process', 'payroll', "Processed payroll for {$period}: {$processed} employees, \${$totalNet} total");

        Flash::set('success', "Payroll processed: {$processed} employees, total net $" . number_format($totalNet, 2));
        $this->redirect('/payroll?period=' . $period);
    }

    public function show($id)
    {
        $this->requireAuth();
        $this->requirePermission('manage_payroll');

        $payroll = Database::getInstance()->fetch(
            "SELECT p.*, e.employee_code, e.first_name, e.last_name, e.photo, e.email, e.phone,
                    e.position, e.job_title, d.name AS department,
                    c.name AS company_name, c.address AS company_address, c.email AS company_email
             FROM payroll p
             INNER JOIN employees e ON e.id = p.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN companies c ON c.id = e.company_id
             WHERE p.id = :id",
            ['id' => $id]
        );

        if (!$payroll) { Flash::set('error', 'Payroll not found'); $this->redirect('/payroll'); }

        $items = Database::getInstance()->fetchAll(
            "SELECT * FROM payroll_items WHERE payroll_id = :id",
            ['id' => $id]
        );

        $this->view('payroll/show', [
            'title' => 'Payslip',
            'pageTitle' => 'Payslip - ' . $payroll['first_name'] . ' ' . $payroll['last_name'],
            'payroll' => $payroll,
            'items' => $items,
            'csrf' => CSRF::field()
        ]);
    }

    public function payslip($id)
    {
        $this->requireAuth();
        // Allow employee to view own payslip
        $payroll = Database::getInstance()->fetch(
            "SELECT p.*, e.employee_code, e.first_name, e.last_name, e.photo,
                    e.position, d.name AS department,
                    c.name AS company_name, c.address AS company_address
             FROM payroll p
             INNER JOIN employees e ON e.id = p.employee_id
             LEFT JOIN departments d ON d.id = e.department_id
             LEFT JOIN companies c ON c.id = e.company_id
             WHERE p.id = :id",
            ['id' => $id]
        );

        if (!$payroll) { Flash::set('error', 'Not found'); $this->redirect('/payroll'); }

        if (Auth::employeeId() && Auth::employeeId() != $payroll['employee_id'] && !Auth::can('manage_payroll')) {
            Flash::set('error', 'Access denied');
            $this->redirect('/dashboard');
        }

        // Generate HTML payslip for print
        $this->layout = 'print';
        $this->view('payroll/payslip', [
            'title' => 'Payslip',
            'payroll' => $payroll
        ]);
    }

    public function approve($id)
    {
        $this->requireAuth();
        $this->validateCsrf();
        $this->requirePermission('manage_payroll');

        Database::getInstance()->update('payroll', [
            'status' => 'approved',
            'approved_by' => Auth::id()
        ], 'id = :id', ['id' => $id]);

        Auth::audit('approve', 'payroll', "Approved payroll ID {$id}");
        Flash::set('success', 'Payroll approved.');
        $this->redirect('/payroll/' . $id);
    }
}
