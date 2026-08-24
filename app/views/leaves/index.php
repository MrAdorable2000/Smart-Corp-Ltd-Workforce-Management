<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Leave Requests</h4>
        <p class="text-muted mb-0">Manage employee leave applications</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/leaves/balance" class="btn btn-light"><i class="bi bi-calendar2-week"></i> My Balance</a>
        <?php if (Auth::can('apply_leave')): ?>
        <a href="<?= BASE_URL ?>/leaves/apply" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Apply Leave</a>
        <?php endif; ?>
    </div>
</div>

<!-- Filter -->
<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="d-flex gap-2 align-items-center">
            <span class="text-muted">Status:</span>
            <select name="status" class="form-select form-select-sm" style="width:150px;" onchange="this.form.submit()">
                <option value="">All</option>
                <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
            </select>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Duration</th>
                        <th>Days</th>
                        <th>Reason</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $r): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar">
                                    <?php if (!empty($r['photo']) && file_exists(UPLOAD_PATH . '/' . $r['photo'])): ?>
                                        <img src="<?= UPLOAD_URL . '/' . $r['photo'] ?>" alt="">
                                    <?php else: ?>
                                        <?= strtoupper(substr($r['first_name'], 0, 1) . substr($r['last_name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-600"><?= htmlspecialchars($r['first_name'] . ' ' . $r['last_name']) ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($r['employee_code']) ?> · <?= htmlspecialchars($r['department'] ?? '-') ?></small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($r['leave_type']) ?></span></td>
                        <td class="text-sm">
                            <?= date('M d', strtotime($r['start_date'])) ?> - <?= date('M d, Y', strtotime($r['end_date'])) ?>
                        </td>
                        <td><strong><?= $r['total_days'] ?></strong></td>
                        <td class="text-sm" style="max-width:200px;"><?= htmlspecialchars(substr($r['reason'], 0, 60)) ?><?= strlen($r['reason']) > 60 ? '...' : '' ?></td>
                        <td class="text-sm text-muted"><?= date('M d', strtotime($r['created_at'])) ?></td>
                        <td>
                            <?php
                            $statusClass = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'cancelled' => 'secondary'];
                            ?>
                            <span class="badge bg-<?= $statusClass[$r['status']] ?>"><?= ucfirst($r['status']) ?></span>
                        </td>
                        <td>
                            <a href="<?= BASE_URL ?>/leaves/<?= $r['id'] ?>" class="btn btn-sm btn-light btn-icon-sm"><i class="bi bi-eye"></i></a>
                            <?php if ($r['status'] === 'pending' && Auth::can('manage_leaves')): ?>
                            <form method="POST" action="<?= BASE_URL ?>/leaves/<?= $r['id'] ?>/approve" class="d-inline">
                                <?= $csrf ?>
                                <button class="btn btn-sm btn-success btn-icon-sm" title="Approve"><i class="bi bi-check"></i></button>
                            </form>
                            <form method="POST" action="<?= BASE_URL ?>/leaves/<?= $r['id'] ?>/reject" class="d-inline">
                                <?= $csrf ?>
                                <button class="btn btn-sm btn-danger btn-icon-sm" title="Reject"><i class="bi bi-x"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($requests)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-airplane display-4 d-block mb-2 opacity-50"></i>
                        No leave requests.
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
