<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Reports & Analytics</h4>
</div>

<div class="row g-3">
    <div class="col-md-6 col-xl-4">
        <a href="<?= BASE_URL ?>/reports/attendance" class="card text-decoration-none text-dark h-100">
            <div class="card-body text-center">
                <i class="bi bi-calendar-check display-4 text-primary"></i>
                <h5 class="mt-2">Attendance Report</h5>
                <p class="text-muted small mb-0">Daily, weekly, monthly, yearly attendance with filters</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-4">
        <a href="<?= BASE_URL ?>/reports/employees" class="card text-decoration-none text-dark h-100">
            <div class="card-body text-center">
                <i class="bi bi-people display-4 text-success"></i>
                <h5 class="mt-2">Employee Report</h5>
                <p class="text-muted small mb-0">Complete employee directory with details</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-4">
        <a href="<?= BASE_URL ?>/reports/payroll" class="card text-decoration-none text-dark h-100">
            <div class="card-body text-center">
                <i class="bi bi-wallet2 display-4 text-info"></i>
                <h5 class="mt-2">Payroll Report</h5>
                <p class="text-muted small mb-0">Monthly payroll summary with breakdown</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-4">
        <a href="<?= BASE_URL ?>/reports/leaves" class="card text-decoration-none text-dark h-100">
            <div class="card-body text-center">
                <i class="bi bi-airplane display-4 text-warning"></i>
                <h5 class="mt-2">Leave Report</h5>
                <p class="text-muted small mb-0">Leave applications, approvals, balances</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-4">
        <a href="<?= BASE_URL ?>/reports/department" class="card text-decoration-none text-dark h-100">
            <div class="card-body text-center">
                <i class="bi bi-diagram-3 display-4 text-danger"></i>
                <h5 class="mt-2">Department Report</h5>
                <p class="text-muted small mb-0">Department-wise statistics and metrics</p>
            </div>
        </a>
    </div>
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body text-center">
                <i class="bi bi-file-earmark-arrow-down display-4 text-secondary"></i>
                <h5 class="mt-2">Export Options</h5>
                <p class="text-muted small">All reports support:</p>
                <div class="d-flex justify-content-center gap-2">
                    <span class="badge bg-danger">PDF</span>
                    <span class="badge bg-success">Excel</span>
                    <span class="badge bg-info">CSV</span>
                </div>
            </div>
        </div>
    </div>
</div>
