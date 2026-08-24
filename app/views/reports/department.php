<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">Department Statistics</h4>
</div>

<div class="row g-3">
    <?php foreach ($departments as $d): ?>
    <div class="col-md-6 col-xl-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($d['name']) ?> <small class="text-muted">(<?= htmlspecialchars($d['code']) ?>)</small></h5>
                <p class="text-muted-sm"><?= htmlspecialchars($d['description'] ?: '') ?></p>
                <hr>
                <div class="row text-center">
                    <div class="col-4 border-end">
                        <div class="h4 mb-0 text-primary"><?= $d['employee_count'] ?></div>
                        <small class="text-muted">Total</small>
                    </div>
                    <div class="col-4 border-end">
                        <div class="h4 mb-0 text-success"><?= $d['present_today'] ?></div>
                        <small class="text-muted">Present</small>
                    </div>
                    <div class="col-4">
                        <div class="h4 mb-0 text-danger"><?= $d['absent_today'] ?></div>
                        <small class="text-muted">Absent</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
