<?php /** @var array $data */
$total = ['gross' => 0, 'tax' => 0, 'deductions' => 0, 'net' => 0];
foreach ($payrolls as $p) {
    $total['gross'] += $p['gross_pay'];
    $total['tax'] += $p['tax_deduction'];
    $total['deductions'] += $p['total_deductions'];
    $total['net'] += $p['net_pay'];
}
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Payroll Report - <?= date('F Y', strtotime($period . '-01')) ?></h4>
    <form method="POST" action="<?= BASE_URL ?>/reports/export" class="d-flex gap-2">
        <?= $csrf ?>
        <input type="hidden" name="type" value="payroll">
        <input type="hidden" name="period" value="<?= $period ?>">
        <button name="format" value="csv" class="btn btn-sm btn-light"><i class="bi bi-filetype-csv"></i> CSV</button>
        <button name="format" value="excel" class="btn btn-sm btn-light"><i class="bi bi-filetype-xls"></i> Excel</button>
    </form>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3"><div class="stat-card success"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div><div><div class="stat-value">$<?= number_format($total['gross'], 0) ?></div><div class="stat-label">Total Gross</div></div></div></div>
    <div class="col-md-3"><div class="stat-card warning"><div class="stat-icon"><i class="bi bi-receipt"></i></div><div><div class="stat-value">$<?= number_format($total['tax'], 0) ?></div><div class="stat-label">Total Tax</div></div></div></div>
    <div class="col-md-3"><div class="stat-card danger"><div class="stat-icon"><i class="bi bi-dash-circle"></i></div><div><div class="stat-value">$<?= number_format($total['deductions'], 0) ?></div><div class="stat-label">Total Deductions</div></div></div></div>
    <div class="col-md-3"><div class="stat-card info"><div class="stat-icon"><i class="bi bi-wallet2"></i></div><div><div class="stat-value">$<?= number_format($total['net'], 0) ?></div><div class="stat-label">Total Net Pay</div></div></div></div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead><tr><th>Code</th><th>Employee</th><th>Dept</th><th>Basic</th><th>Allowance</th><th>OT</th><th>Gross</th><th>Tax</th><th>Deductions</th><th>Net</th><th>Status</th></tr></thead>
                <tbody>
                    <?php foreach ($payrolls as $p): ?>
                    <tr>
                        <td><?= htmlspecialchars($p['employee_code']) ?></td>
                        <td><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></td>
                        <td><?= htmlspecialchars($p['department'] ?? '-') ?></td>
                        <td>$<?= number_format($p['basic_salary'], 2) ?></td>
                        <td>$<?= number_format($p['allowances'], 2) ?></td>
                        <td>$<?= number_format($p['overtime_pay'], 2) ?></td>
                        <td>$<?= number_format($p['gross_pay'], 2) ?></td>
                        <td>$<?= number_format($p['tax_deduction'], 2) ?></td>
                        <td>$<?= number_format($p['total_deductions'], 2) ?></td>
                        <td class="fw-600 text-success">$<?= number_format($p['net_pay'], 2) ?></td>
                        <td><span class="badge bg-info"><?= ucfirst($p['status']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($payrolls)): ?>
                    <tr><td colspan="11" class="text-center py-4 text-muted">No payroll data for this period</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
