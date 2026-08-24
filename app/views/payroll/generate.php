<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= $pageTitle ?></h4>
    <a href="<?= BASE_URL ?>/payroll" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-body">
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            This will process payroll for all active employees for <strong><?= date('F Y', strtotime($period . '-01')) ?></strong>.
            The system will automatically calculate:
            <ul class="mb-0 mt-2">
                <li>Basic salary (prorated based on present days)</li>
                <li>Allowances</li>
                <li>Overtime pay (1.5x hourly rate)</li>
                <li>Tax deduction (based on employee tax rate)</li>
                <li>Insurance (2% of gross)</li>
                <li>Penalties ($5 per late arrival)</li>
            </ul>
        </div>

        <form method="POST" action="<?= BASE_URL ?>/payroll/process">
            <?= $csrf ?>
            <input type="hidden" name="period" value="<?= $period ?>">
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-gear"></i> Process Payroll for <?= date('F Y', strtotime($period . '-01')) ?>
                </button>
                <a href="<?= BASE_URL ?>/payroll?period=<?= $period ?>" class="btn btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
