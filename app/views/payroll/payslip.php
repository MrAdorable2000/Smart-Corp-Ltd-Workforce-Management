<?php /** @var array $data */ ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payslip - <?= htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Helvetica', sans-serif; padding: 20px; font-size: 13px; }
        .payslip { max-width: 700px; margin: 0 auto; }
        @media print { body { padding: 0; } }
    </style>
</head>
<body>
<div class="payslip">
    <div class="text-center mb-4">
        <h4><?= htmlspecialchars($payroll['company_name']) ?></h4>
        <p class="mb-0"><?= htmlspecialchars($payroll['company_address'] ?? '') ?></p>
        <h5 class="mt-3">PAYSLIP</h5>
        <p>Period: <?= date('F Y', strtotime($payroll['payroll_period'] . '-01')) ?></p>
    </div>

    <table class="table table-sm table-borderless mb-3">
        <tr><td><strong>Employee:</strong> <?= htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']) ?></td><td><strong>Code:</strong> <?= htmlspecialchars($payroll['employee_code']) ?></td></tr>
        <tr><td><strong>Department:</strong> <?= htmlspecialchars($payroll['department'] ?? '-') ?></td><td><strong>Position:</strong> <?= htmlspecialchars($payroll['position'] ?? '-') ?></td></tr>
    </table>

    <table class="table table-bordered">
        <thead class="table-light">
            <tr><th>Earnings</th><th class="text-end">Amount</th><th>Deductions</th><th class="text-end">Amount</th></tr>
        </thead>
        <tbody>
            <tr><td>Basic Salary</td><td class="text-end">$<?= number_format($payroll['basic_salary'], 2) ?></td><td>Tax</td><td class="text-end">$<?= number_format($payroll['tax_deduction'], 2) ?></td></tr>
            <tr><td>Allowances</td><td class="text-end">$<?= number_format($payroll['allowances'], 2) ?></td><td>Insurance</td><td class="text-end">$<?= number_format($payroll['insurance_deduction'], 2) ?></td></tr>
            <tr><td>Overtime</td><td class="text-end">$<?= number_format($payroll['overtime_pay'], 2) ?></td><td>Penalty</td><td class="text-end">$<?= number_format($payroll['penalty_deduction'], 2) ?></td></tr>
            <tr><td>Bonus</td><td class="text-end">$<?= number_format($payroll['bonus'], 2) ?></td><td>Other</td><td class="text-end">$<?= number_format($payroll['other_deductions'], 2) ?></td></tr>
            <tr class="table-light"><th>Gross Pay</th><th class="text-end">$<?= number_format($payroll['gross_pay'], 2) ?></th><th>Total Deductions</th><th class="text-end">$<?= number_format($payroll['total_deductions'], 2) ?></th></tr>
        </tbody>
    </table>

    <div class="text-center mt-4 p-3 bg-light">
        <h4>NET PAY: $<?= number_format($payroll['net_pay'], 2) ?></h4>
    </div>

    <p class="text-center text-muted mt-4"><small>This is a computer-generated payslip. Generated on <?= date('M d, Y H:i') ?></small></p>
</div>
<script>window.print();</script>
</body>
</html>
