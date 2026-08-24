<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4 no-print">
    <h4 class="mb-0">Payslip Details</h4>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary"><i class="bi bi-printer"></i> Print</button>
        <a href="<?= BASE_URL ?>/payroll/<?= $payroll['id'] ?>/payslip" target="_blank" class="btn btn-light"><i class="bi bi-file-earmark-pdf"></i> PDF View</a>
        <?php if (Auth::can('manage_payroll') && $payroll['status'] !== 'approved'): ?>
        <form method="POST" action="<?= BASE_URL ?>/payroll/<?= $payroll['id'] ?>/approve">
            <?= $csrf ?>
            <button class="btn btn-success"><i class="bi bi-check-lg"></i> Approve</button>
        </form>
        <?php endif; ?>
        <a href="<?= BASE_URL ?>/payroll" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-md-6">
                <h4 class="mb-0"><?= htmlspecialchars($payroll['company_name']) ?></h4>
                <p class="text-muted-sm mb-0"><?= htmlspecialchars($payroll['company_address'] ?? '') ?></p>
                <p class="text-muted-sm mb-0"><?= htmlspecialchars($payroll['company_email'] ?? '') ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <h5 class="mb-0">PAYSLIP</h5>
                <p class="mb-0">Period: <strong><?= date('F Y', strtotime($payroll['payroll_period'] . '-01')) ?></strong></p>
                <p class="mb-0">Status: <span class="badge bg-success"><?= ucfirst($payroll['status']) ?></span></p>
            </div>
        </div>

        <hr>

        <!-- Employee Info -->
        <div class="row mb-4">
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr><th style="width:140px">Employee Name</th><td><?= htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']) ?></td></tr>
                    <tr><th>Employee Code</th><td><?= htmlspecialchars($payroll['employee_code']) ?></td></tr>
                    <tr><th>Department</th><td><?= htmlspecialchars($payroll['department'] ?? '-') ?></td></tr>
                    <tr><th>Position</th><td><?= htmlspecialchars($payroll['position'] ?? '-') ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless">
                    <tr><th style="width:140px">Pay Period</th><td><?= date('M d', strtotime($payroll['start_date'])) ?> - <?= date('M d, Y', strtotime($payroll['end_date'])) ?></td></tr>
                    <tr><th>Working Days</th><td><?= $payroll['working_days'] ?></td></tr>
                    <tr><th>Present Days</th><td><?= $payroll['present_days'] ?></td></tr>
                    <tr><th>Absent Days</th><td><?= $payroll['absent_days'] ?></td></tr>
                    <tr><th>Leave Days</th><td><?= $payroll['leave_days'] ?></td></tr>
                    <tr><th>Overtime Hours</th><td><?= $payroll['overtime_hours'] ?></td></tr>
                    <tr><th>Late Count</th><td><?= $payroll['late_count'] ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Earnings & Deductions -->
        <div class="row">
            <div class="col-md-6">
                <h6 class="bg-light p-2 mb-2"><i class="bi bi-plus-circle text-success"></i> EARNINGS</h6>
                <table class="table table-sm">
                    <tr><td>Basic Salary</td><td class="text-end">$<?= number_format($payroll['basic_salary'], 2) ?></td></tr>
                    <tr><td>Allowances</td><td class="text-end">$<?= number_format($payroll['allowances'], 2) ?></td></tr>
                    <tr><td>Overtime Pay</td><td class="text-end">$<?= number_format($payroll['overtime_pay'], 2) ?></td></tr>
                    <tr><td>Bonus</td><td class="text-end">$<?= number_format($payroll['bonus'], 2) ?></td></tr>
                    <tr class="table-success fw-600"><td>Gross Pay</td><td class="text-end">$<?= number_format($payroll['gross_pay'], 2) ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="bg-light p-2 mb-2"><i class="bi bi-dash-circle text-danger"></i> DEDUCTIONS</h6>
                <table class="table table-sm">
                    <tr><td>Tax (<?= $payroll['employee_id'] ? '' : '' ?>)</td><td class="text-end">$<?= number_format($payroll['tax_deduction'], 2) ?></td></tr>
                    <tr><td>Insurance</td><td class="text-end">$<?= number_format($payroll['insurance_deduction'], 2) ?></td></tr>
                    <tr><td>Penalty (Late)</td><td class="text-end">$<?= number_format($payroll['penalty_deduction'], 2) ?></td></tr>
                    <tr><td>Other</td><td class="text-end">$<?= number_format($payroll['other_deductions'], 2) ?></td></tr>
                    <tr class="table-danger fw-600"><td>Total Deductions</td><td class="text-end">$<?= number_format($payroll['total_deductions'], 2) ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Net Pay -->
        <div class="row mt-4">
            <div class="col-md-6 offset-md-6">
                <div class="card bg-primary text-white">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0">NET PAY</h5>
                            <small><?= date('F Y', strtotime($payroll['payroll_period'] . '-01')) ?></small>
                        </div>
                        <h3 class="mb-0">$<?= number_format($payroll['net_pay'], 2) ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <hr class="mt-4">
        <p class="text-muted-sm text-center mb-0">
            This is a computer-generated payslip and does not require a signature. Generated on <?= date('M d, Y H:i') ?>
        </p>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    .card { border: none !important; box-shadow: none !important; }
}
</style>
