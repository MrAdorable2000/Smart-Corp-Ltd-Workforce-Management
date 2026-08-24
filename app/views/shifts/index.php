<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Shift Management</h4>
    <a href="<?= BASE_URL ?>/shifts/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Shift</a>
</div>

<div class="row g-3">
    <?php foreach ($shifts as $s):
        $color = $s['is_night_shift'] ? 'dark' : ($s['is_flexible'] ? 'info' : ($s['start_time'] < '12:00:00' ? 'warning' : 'primary'));
    ?>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100 border-start border-<?= $color ?> border-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title mb-0"><?= htmlspecialchars($s['name']) ?></h5>
                        <small class="text-muted"><?= htmlspecialchars($s['code'] ?: '-') ?></small>
                    </div>
                    <?php if ($s['is_night_shift']): ?><span class="badge bg-dark"><i class="bi bi-moon"></i> Night</span>
                    <?php elseif ($s['is_flexible']): ?><span class="badge bg-info"><i class="bi bi-arrow-left-right"></i> Flex</span>
                    <?php endif; ?>
                </div>
                <div class="my-3 text-center">
                    <div class="h3 mb-0"><?= date('g:i A', strtotime($s['start_time'])) ?></div>
                    <div class="text-muted">to</div>
                    <div class="h3 mb-0"><?= date('g:i A', strtotime($s['end_time'])) ?></div>
                </div>
                <div class="row text-center small">
                    <div class="col-4 border-end">
                        <div class="text-muted">Grace</div>
                        <div class="fw-600"><?= $s['grace_period_minutes'] ?>m</div>
                    </div>
                    <div class="col-4 border-end">
                        <div class="text-muted">OT Rate</div>
                        <div class="fw-600"><?= $s['overtime_rate'] ?>x</div>
                    </div>
                    <div class="col-4">
                        <div class="text-muted">Hours</div>
                        <div class="fw-600"><?= $s['working_hours_per_day'] ?>h</div>
                    </div>
                </div>
                <div class="mt-3 d-flex gap-2">
                    <a href="<?= BASE_URL ?>/shifts/<?= $s['id'] ?>/edit" class="btn btn-sm btn-light flex-grow-1"><i class="bi bi-pencil"></i> Edit</a>
                    <form method="POST" action="<?= BASE_URL ?>/shifts/<?= $s['id'] ?>/delete" class="d-inline" onsubmit="return confirm('PERMANENTLY DELETE this shift?\n\nShift: <?= htmlspecialchars($s['name']) ?>\n\nThis will remove the shift from the database entirely. Employees using this shift will be unlinked.\n\nAre you sure?')">
                        <?= $csrf ?>
                        <button class="btn btn-sm btn-light text-danger confirm-delete"><i class="bi bi-trash"></i></button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($shifts)): ?>
    <div class="col-12 text-center py-5 text-muted">
        <i class="bi bi-clock-history display-4 d-block mb-2 opacity-50"></i>
        No shifts defined yet.
    </div>
    <?php endif; ?>
</div>
