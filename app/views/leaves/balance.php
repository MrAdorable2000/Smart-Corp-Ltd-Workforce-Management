<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">My Leave Balance - <?= date('Y') ?></h4>
    <a href="<?= BASE_URL ?>/leaves/apply" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Apply Leave</a>
</div>

<div class="row g-3">
    <?php foreach ($balances as $b):
        $remaining = $b['entitled_days'] + $b['carried_forward'] - $b['used_days'];
        $pct = $b['entitled_days'] > 0 ? ($b['used_days'] / $b['entitled_days']) * 100 : 0;
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h5 class="mb-0"><?= htmlspecialchars($b['leave_type']) ?></h5>
                        <small class="text-muted"><?= htmlspecialchars($b['code']) ?> · <?= $b['is_paid'] ? 'Paid' : 'Unpaid' ?></small>
                    </div>
                    <span class="badge bg-<?= $remaining > 5 ? 'success' : ($remaining > 0 ? 'warning' : 'danger') ?>"><?= $remaining ?>d left</span>
                </div>
                <div class="row text-center mb-3">
                    <div class="col-4 border-end">
                        <div class="h5 mb-0 text-primary"><?= $b['entitled_days'] ?></div>
                        <small class="text-muted">Entitled</small>
                    </div>
                    <div class="col-4 border-end">
                        <div class="h5 mb-0 text-warning"><?= $b['used_days'] ?></div>
                        <small class="text-muted">Used</small>
                    </div>
                    <div class="col-4">
                        <div class="h5 mb-0 text-success"><?= $remaining ?></div>
                        <small class="text-muted">Remaining</small>
                    </div>
                </div>
                <div class="progress" style="height:8px;">
                    <div class="progress-bar bg-<?= $pct < 70 ? 'success' : ($pct < 90 ? 'warning' : 'danger') ?>" style="width: <?= min(100, $pct) ?>%"></div>
                </div>
                <small class="text-muted mt-1 d-block"><?= round($pct, 1) ?>% used</small>
                <?php if ($b['carried_forward'] > 0): ?>
                <small class="text-info mt-1 d-block"><i class="bi bi-info-circle"></i> <?= $b['carried_forward'] ?> days carried forward</small>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($balances)): ?>
    <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-calendar2-week display-4 d-block mb-2 opacity-50"></i>
        No leave balances configured.
    </div>
    <?php endif; ?>
</div>
