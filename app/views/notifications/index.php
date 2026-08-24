<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">My Notifications</h4>
    <button class="btn btn-light btn-sm" id="markAllRead"><i class="bi bi-check2-all"></i> Mark All Read</button>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            <?php foreach ($notifications as $n):
                $iconMap = ['attendance' => 'bi-calendar-check', 'leave' => 'bi-airplane', 'payroll' => 'bi-wallet2', 'system' => 'bi-info-circle'];
                $colorMap = ['attendance' => 'primary', 'leave' => 'warning', 'payroll' => 'success', 'system' => 'info'];
            ?>
            <div class="list-group-item <?= $n['status'] !== 'read' ? 'list-group-item-light' : '' ?>">
                <div class="d-flex align-items-start gap-3">
                    <div class="n-icon n-<?= htmlspecialchars($n['type']) ?>">
                        <i class="bi <?= $iconMap[$n['type']] ?? 'bi-bell' ?>"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex justify-content-between align-items-start">
                            <h6 class="mb-1"><?= htmlspecialchars($n['title']) ?> <?= $n['status'] !== 'read' ? '<span class="badge bg-danger">New</span>' : '' ?></h6>
                            <small class="text-muted"><?= date('M d, H:i', strtotime($n['created_at'])) ?></small>
                        </div>
                        <p class="mb-1 text-sm"><?= htmlspecialchars($n['message']) ?></p>
                        <small class="text-muted"><i class="bi bi-<?= $n['channel'] === 'email' ? 'envelope' : ($n['channel'] === 'sms' ? 'phone' : 'bell') ?>"></i> <?= ucfirst($n['channel']) ?></small>
                        <?php if ($n['status'] !== 'read'): ?>
                        <form method="POST" action="<?= BASE_URL ?>/notifications/<?= $n['id'] ?>/read" class="d-inline float-end">
                            <?= $csrf ?>
                            <button class="btn btn-sm btn-link p-0">Mark as read</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if (empty($notifications)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-bell-slash display-4 d-block mb-2 opacity-50"></i>
                <p>You're all caught up! No notifications.</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.n-icon { width: 40px; height: 40px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: var(--primary-light); color: var(--primary); font-size: 18px; }
.n-icon.n-attendance { background: #DBEAFE; color: #2563EB; }
.n-icon.n-leave { background: #FEF3C7; color: #D97706; }
.n-icon.n-payroll { background: #D1FAE5; color: #059669; }
.n-icon.n-system { background: #E0E7FF; color: #4F46E5; }
</style>
