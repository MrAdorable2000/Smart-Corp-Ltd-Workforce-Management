<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Audit Logs</h4>
        <p class="text-muted mb-0">System activity tracking (<?= number_format($total) ?> total records)</p>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small">Module</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= $m['module'] ?>" <?= $currentModule === $m['module'] ? 'selected' : '' ?>><?= ucfirst($m['module']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All</option>
                    <?php foreach ($actions as $a): ?>
                        <option value="<?= $a['action'] ?>" <?= $currentAction === $a['action'] ? 'selected' : '' ?>><?= ucfirst($a['action']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Module</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>IP</th>
                        <th>Severity</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log):
                        $sevColors = ['info' => 'info', 'warning' => 'warning', 'critical' => 'danger'];
                    ?>
                    <tr>
                        <td class="text-sm"><?= date('M d, Y H:i:s', strtotime($log['created_at'])) ?></td>
                        <td class="text-sm">
                            <?php if ($log['user_name']): ?>
                                <?= htmlspecialchars($log['user_name']) ?><br><small class="text-muted"><?= htmlspecialchars($log['user_email']) ?></small>
                            <?php else: ?> System <?php endif; ?>
                        </td>
                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($log['module']) ?></span></td>
                        <td><span class="badge bg-primary"><?= htmlspecialchars($log['action']) ?></span></td>
                        <td class="text-sm"><?= htmlspecialchars($log['description']) ?></td>
                        <td class="text-sm text-muted"><?= htmlspecialchars($log['ip_address']) ?></td>
                        <td><span class="badge bg-<?= $sevColors[$log['severity']] ?? 'secondary' ?>"><?= ucfirst($log['severity']) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($logs)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No audit logs found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <nav>
            <ul class="pagination pagination-sm justify-content-center mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page - 1 ?>&module=<?= $currentModule ?>&action=<?= $currentAction ?>">Previous</a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&module=<?= $currentModule ?>&action=<?= $currentAction ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?page=<?= $page + 1 ?>&module=<?= $currentModule ?>&action=<?= $currentAction ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
