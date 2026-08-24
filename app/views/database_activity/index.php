<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Database Change Tracker</h4>
        <p class="text-muted mb-0">Every INSERT, UPDATE, and DELETE is automatically logged here in real-time</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/database-activity/export" class="btn btn-light">
            <i class="bi bi-download"></i> Export CSV
        </a>
    </div>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card stat-emerald">
            <div class="stat-icon"><i class="bi bi-plus-circle-fill"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['inserts'] ?></div>
                <div class="stat-label">Inserts Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-indigo">
            <div class="stat-icon"><i class="bi bi-pencil-fill"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['updates'] ?></div>
                <div class="stat-label">Updates Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-rose">
            <div class="stat-icon"><i class="bi bi-trash-fill"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= $stats['deletes'] ?></div>
                <div class="stat-label">Deletes Today</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card stat-sky">
            <div class="stat-icon"><i class="bi bi-database-fill"></i></div>
            <div class="stat-content">
                <div class="stat-value"><?= number_format($stats['all_time']) ?></div>
                <div class="stat-label">Total Changes (All Time)</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-2">
                <label class="form-label small">Action</label>
                <select name="action" class="form-select form-select-sm">
                    <option value="">All Actions</option>
                    <option value="create" <?= $filters['action'] === 'create' ? 'selected' : '' ?>>Insert (Create)</option>
                    <option value="update" <?= $filters['action'] === 'update' ? 'selected' : '' ?>>Update</option>
                    <option value="delete" <?= $filters['action'] === 'delete' ? 'selected' : '' ?>>Delete</option>
                    <option value="approve" <?= $filters['action'] === 'approve' ? 'selected' : '' ?>>Approve</option>
                    <option value="check_in" <?= $filters['action'] === 'check_in' ? 'selected' : '' ?>>Check In</option>
                    <option value="check_out" <?= $filters['action'] === 'check_out' ? 'selected' : '' ?>>Check Out</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Table</label>
                <select name="module" class="form-select form-select-sm">
                    <option value="">All Tables</option>
                    <?php foreach ($modules as $m): ?>
                        <option value="<?= htmlspecialchars($m['module']) ?>" <?= $filters['module'] === $m['module'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($m['module']) ?> (<?= $m['count'] ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">User</label>
                <select name="user_id" class="form-select form-select-sm">
                    <option value="">All Users</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= $filters['user_id'] == $u['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date From</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="<?= $filters['date_from'] ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">Date To</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="<?= $filters['date_to'] ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Changes Table -->
<div class="card">
    <div class="card-header">
        <span><i class="bi bi-clock-history me-2"></i>Recent Database Changes (<?= number_format($total) ?> total<?= $total > $perPage ? ', showing ' . count($changes) : '' ?>)</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Time</th>
                        <th>Action</th>
                        <th>Table</th>
                        <th>User</th>
                        <th>Description</th>
                        <th>IP</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($changes as $c):
                        $actionIcons = [
                            'create'    => ['bi-plus-circle-fill', 'success'],
                            'update'    => ['bi-pencil-fill', 'primary'],
                            'delete'    => ['bi-trash-fill', 'danger'],
                            'approve'   => ['bi-check-circle-fill', 'success'],
                            'reject'    => ['bi-x-circle-fill', 'danger'],
                            'check_in'  => ['bi-door-open', 'success'],
                            'check_out' => ['bi-door-closed', 'primary'],
                            'process'   => ['bi-gear', 'info'],
                            'register'  => ['bi-person-plus', 'info'],
                        ];
                        $iconInfo = $actionIcons[$c['action']] ?? ['bi-circle', 'secondary'];
                    ?>
                    <tr>
                        <td><code>#<?= $c['id'] ?></code></td>
                        <td class="text-sm" style="white-space: nowrap;">
                            <?= date('M d, Y', strtotime($c['created_at'])) ?><br>
                            <span class="text-muted"><?= date('H:i:s', strtotime($c['created_at'])) ?></span>
                        </td>
                        <td>
                            <span class="badge bg-<?= $iconInfo[1] ?>">
                                <i class="bi <?= $iconInfo[0] ?>"></i>
                                <?= ucfirst(str_replace('_', ' ', $c['action'])) ?>
                            </span>
                        </td>
                        <td><code class="text-primary"><?= htmlspecialchars($c['module']) ?></code></td>
                        <td class="text-sm">
                            <?= $c['user_name'] ? htmlspecialchars($c['user_name']) : '<span class="text-muted">System</span>' ?>
                        </td>
                        <td class="text-sm"><?= htmlspecialchars($c['description']) ?></td>
                        <td class="text-muted-sm"><?= htmlspecialchars($c['ip_address']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/database-activity/<?= $c['id'] ?>" class="btn btn-sm btn-light btn-icon-sm" title="View details">
                                <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($changes)): ?>
                    <tr><td colspan="8" class="empty-state">
                        <i class="bi bi-database-slash"></i>
                        <h6>No database changes found</h6>
                        <p>Try adjusting filters or perform an action (add employee, edit user, etc.)</p>
                    </td></tr>
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
                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>">Previous</a>
                </li>
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $i])) ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])) ?>">Next</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
