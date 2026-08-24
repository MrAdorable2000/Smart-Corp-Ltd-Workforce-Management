<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Payroll Management</h4>
        <p class="text-muted mb-0">Process and manage employee payroll</p>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex gap-2">
            <input type="month" name="period" class="form-control" value="<?= $period ?>" style="width:180px;">
            <button class="btn btn-light"><i class="bi bi-calendar"></i> Filter</button>
        </form>
        <a href="<?= BASE_URL ?>/payroll/generate?period=<?= $period ?>" class="btn btn-primary">
            <i class="bi bi-gear"></i> Generate Payroll
        </a>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card"><div class="stat-icon"><i class="bi bi-people"></i></div>
        <div><div class="stat-value"><?= $summary['count'] ?></div><div class="stat-label">Employees Processed</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card success"><div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
        <div><div class="stat-value">$<?= number_format($summary['gross'], 0) ?></div><div class="stat-label">Total Gross</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card warning"><div class="stat-icon"><i class="bi bi-receipt"></i></div>
        <div><div class="stat-value">$<?= number_format($summary['tax'], 0) ?></div><div class="stat-label">Total Tax</div></div></div>
    </div>
    <div class="col-md-3">
        <div class="stat-card info"><div class="stat-icon"><i class="bi bi-wallet2"></i></div>
        <div><div class="stat-value">$<?= number_format($summary['net'], 0) ?></div><div class="stat-label">Total Net Pay</div></div></div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-list-ul me-2"></i>Payroll for <?= date('F Y', strtotime($period . '-01')) ?></div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Dept</th>
                        <th>Basic</th>
                        <th>Allowance</th>
                        <th>OT Pay</th>
                        <th>Gross</th>
                        <th>Deductions</th>
                        <th>Net Pay</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payrolls as $p): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar">
                                    <?php if (!empty($p['photo']) && file_exists(UPLOAD_PATH . '/' . $p['photo'])): ?>
                                        <img src="<?= UPLOAD_URL . '/' . $p['photo'] ?>" alt="">
                                    <?php else: ?>
                                        <?= strtoupper(substr($p['first_name'], 0, 1) . substr($p['last_name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-600"><?= htmlspecialchars($p['first_name'] . ' ' . $p['last_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($p['employee_code']) ?></small>
                                </div>
                            </div>
                        </td>
                        <td class="text-sm"><?= htmlspecialchars($p['department'] ?? '-') ?></td>
                        <td>$<?= number_format($p['basic_salary'], 2) ?></td>
                        <td>$<?= number_format($p['allowances'], 2) ?></td>
                        <td>$<?= number_format($p['overtime_pay'], 2) ?></td>
                        <td class="fw-600">$<?= number_format($p['gross_pay'], 2) ?></td>
                        <td class="text-danger">$<?= number_format($p['total_deductions'], 2) ?></td>
                        <td class="fw-600 text-success">$<?= number_format($p['net_pay'], 2) ?></td>
                        <td>
                            <?php
                            $cls = ['draft' => 'secondary', 'processed' => 'info', 'approved' => 'success', 'paid' => 'primary', 'cancelled' => 'danger'];
                            ?>
                            <span class="badge bg-<?= $cls[$p['status']] ?? 'secondary' ?>"><?= ucfirst($p['status']) ?></span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/payroll/<?= $p['id'] ?>" class="btn btn-sm btn-light btn-icon-sm"><i class="bi bi-eye"></i></a>
                            <a href="<?= BASE_URL ?>/payroll/<?= $p['id'] ?>/payslip" target="_blank" class="btn btn-sm btn-light btn-icon-sm"><i class="bi bi-printer"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($payrolls)): ?>
                    <tr><td colspan="10" class="text-center py-5 text-muted">
                        <i class="bi bi-wallet2 display-4 d-block mb-2 opacity-50"></i>
                        No payroll records for this period. Click "Generate Payroll" to process.
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
