<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">System Users</h4>
        <?php
        $pendingCount = 0;
        $suspendedCount = 0;
        foreach ($users as $u) {
            if ($u['status'] === 'pending') $pendingCount++;
            if ($u['status'] === 'suspended') $suspendedCount++;
        }
        if ($pendingCount > 0 || $suspendedCount > 0): ?>
            <p class="text-muted mb-0">
                <?php if ($pendingCount > 0): ?>
                    <i class="bi bi-clock-history text-warning"></i> <?= $pendingCount ?> pending approval
                <?php endif; ?>
                <?php if ($pendingCount > 0 && $suspendedCount > 0): ?> · <?php endif; ?>
                <?php if ($suspendedCount > 0): ?>
                    <i class="bi bi-pause-circle text-danger"></i> <?= $suspendedCount ?> suspended
                <?php endif; ?>
            </p>
        <?php endif; ?>
    </div>
    <a href="<?= BASE_URL ?>/users/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add User</a>
</div>

<!-- Legend -->
<div class="card mb-3">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap gap-3 align-items-center small">
            <strong class="me-2">Status Legend:</strong>
            <span><span class="badge bg-success">Active</span> Can login normally</span>
            <span><span class="badge bg-warning">Pending</span> Awaiting admin approval (cannot login)</span>
            <span><span class="badge bg-danger">Suspended</span> Blocked from system (cannot login)</span>
            <span><span class="badge bg-secondary">Inactive</span> Deactivated (cannot login)</span>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Last Login</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $u):
                        $statusColors = [
                            'active'    => 'success',
                            'inactive'  => 'secondary',
                            'suspended' => 'danger',
                            'pending'   => 'warning'
                        ];
                        $rowBg = '';
                        if ($u['status'] === 'pending')   $rowBg = 'background: rgba(245,158,11,.05);';
                        if ($u['status'] === 'suspended') $rowBg = 'background: rgba(239,68,68,.05);';
                    ?>
                    <tr style="<?= $rowBg ?>">
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-sm">
                                    <?php if (!empty($u['avatar']) && file_exists(UPLOAD_PATH . '/' . $u['avatar'])): ?>
                                        <img src="<?= UPLOAD_URL . '/' . $u['avatar'] ?>" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">
                                    <?php else: ?>
                                        <?= strtoupper(substr($u['name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-600"><?= htmlspecialchars($u['name']) ?></div>
                                    <?php if ($u['employee_code']): ?>
                                        <small class="text-muted"><?= htmlspecialchars($u['employee_code']) ?></small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td class="text-sm"><?= htmlspecialchars($u['phone'] ?: '-') ?></td>
                        <td><span class="badge bg-primary"><?= htmlspecialchars($u['role_name']) ?></span></td>
                        <td>
                            <span class="badge bg-<?= $statusColors[$u['status']] ?? 'secondary' ?>"><?= ucfirst($u['status']) ?></span>
                        </td>
                        <td class="text-sm"><?= $u['last_login_at'] ? date('M d, H:i', strtotime($u['last_login_at'])) : 'Never' ?></td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light btn-icon-sm" data-bs-toggle="dropdown" title="Actions">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/users/<?= $u['id'] ?>/edit">
                                        <i class="bi bi-pencil me-2"></i>Edit User
                                    </a></li>
                                    <li><hr class="dropdown-divider"></li>

                                    <?php if ($u['status'] === 'pending'): ?>
                                        <!-- Pending user actions -->
                                        <li>
                                            <form method="POST" action="<?= BASE_URL ?>/users/<?= $u['id'] ?>/approve" class="d-inline w-100">
                                                <?= $csrf ?>
                                                <button type="submit" class="dropdown-item text-success">
                                                    <i class="bi bi-check-circle me-2"></i>Approve (Activate)
                                                </button>
                                            </form>
                                        </li>
                                        <li>
                                            <form method="POST" action="<?= BASE_URL ?>/users/<?= $u['id'] ?>/reject" class="d-inline w-100" onsubmit="return confirm('Reject this registration? This will DELETE the user record permanently.')">
                                                <?= $csrf ?>
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-x-circle me-2"></i>Reject (Delete)
                                                </button>
                                            </form>
                                        </li>
                                    <?php elseif ($u['status'] === 'active'): ?>
                                        <!-- Active user actions -->
                                        <?php if ($u['id'] != Auth::id() && $u['role_slug'] !== 'super_admin'): ?>
                                        <li>
                                            <form method="POST" action="<?= BASE_URL ?>/users/<?= $u['id'] ?>/suspend" class="d-inline w-100" onsubmit="return confirm('Suspend this user? They will be immediately blocked from accessing the system.')">
                                                <?= $csrf ?>
                                                <button type="submit" class="dropdown-item text-warning">
                                                    <i class="bi bi-pause-circle me-2"></i>Suspend User
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                    <?php elseif ($u['status'] === 'suspended' || $u['status'] === 'inactive'): ?>
                                        <!-- Suspended/Inactive user actions -->
                                        <li>
                                            <form method="POST" action="<?= BASE_URL ?>/users/<?= $u['id'] ?>/reactivate" class="d-inline w-100">
                                                <?= $csrf ?>
                                                <button type="submit" class="dropdown-item text-success">
                                                    <i class="bi bi-play-circle me-2"></i>Reactivate
                                                </button>
                                            </form>
                                        </li>
                                    <?php endif; ?>

                                    <?php if ($u['id'] != Auth::id()): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="<?= BASE_URL ?>/users/<?= $u['id'] ?>/delete" class="d-inline w-100" onsubmit="return confirm('⚠️ PERMANENT DELETE WARNING ⚠️\n\nThis will completely remove &quot;<?= htmlspecialchars($u['name']) ?>&quot; from the database.\n\nThe user record will be gone forever (cannot be recovered).\n\nAre you sure?')">
                                            <?= $csrf ?>
                                            <button type="submit" class="dropdown-item text-danger">
                                                <i class="bi bi-trash me-2"></i>Delete Permanently
                                            </button>
                                        </form>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($users)): ?>
                    <tr><td colspan="7" class="empty-state">
                        <i class="bi bi-people"></i>
                        <h6>No users found</h6>
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Info Card -->
<div class="card mt-3">
    <div class="card-body">
        <h6><i class="bi bi-info-circle text-primary me-2"></i>How User Status Works</h6>
        <ul class="mb-0 small text-muted">
            <li><strong>Active</strong> — User can login and access the system normally.</li>
            <li><strong>Pending</strong> — New registration awaiting admin approval. Cannot login until approved.</li>
            <li><strong>Suspended</strong> — User is blocked from the system. If they were logged in, they will be automatically kicked out on their next page load. They cannot login again until reactivated.</li>
            <li><strong>Inactive</strong> — User account is deactivated. Cannot login until reactivated.</li>
        </ul>
        <p class="small text-danger mt-2 mb-0">
            <i class="bi bi-exclamation-triangle"></i> <strong>Delete Permanently</strong> — Removes the user record COMPLETELY from the database. This action cannot be undone. The user's notifications are deleted, and their audit/activity log entries are preserved with user_id set to NULL.
        </p>
        <p class="small text-warning mt-2 mb-0">
            <i class="bi bi-shield-exclamation"></i> <strong>Security:</strong> When you suspend a user, their "remember me" token is invalidated immediately, so they cannot bypass the suspension via saved login.
        </p>
    </div>
</div>
