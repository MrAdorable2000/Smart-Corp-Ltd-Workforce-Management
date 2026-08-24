<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Employee Management</h4>
        <p class="text-muted mb-0">Manage your organization's workforce records</p>
    </div>
    <?php if (Auth::can('manage_employees')): ?>
    <a href="<?= BASE_URL ?>/employees/create" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Add Employee
    </a>
    <?php endif; ?>
</div>

<!-- Stats Row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value"><?= $stats['total'] ?></div>
                <div class="stat-label">Total Active</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card success">
            <div class="stat-icon"><i class="bi bi-person-check"></i></div>
            <div>
                <div class="stat-value"><?= $stats['permanent'] ?></div>
                <div class="stat-label">Permanent</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card warning">
            <div class="stat-icon"><i class="bi bi-file-earmark-text"></i></div>
            <div>
                <div class="stat-value"><?= $stats['contract'] + $stats['probation'] ?></div>
                <div class="stat-label">Contract/Probation</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card info">
            <div class="stat-icon"><i class="bi bi-camera-video"></i></div>
            <div>
                <div class="stat-value"><?= $stats['face_enrolled'] ?></div>
                <div class="stat-label">Face Enrolled</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, code, email..." value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="department_id" class="form-select">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filters['department_id'] == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">All Status</option>
                    <?php foreach (['permanent','contract','probation','intern','terminated','resigned'] as $s): ?>
                        <option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Employee List -->
<div class="card">
    <div class="card-header">
        <span><i class="bi bi-list-ul me-2"></i>Employee List (<?= count($employees) ?>)</span>
        <div class="input-group input-group-sm" style="width: 220px;">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="text" id="tableSearch" class="form-control" placeholder="Quick search...">
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover data-table mb-0">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Code</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Contact</th>
                        <th>Status</th>
                        <th>Face</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $e): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="employee-avatar">
                                    <?php if (!empty($e['photo']) && file_exists(UPLOAD_PATH . '/' . $e['photo'])): ?>
                                        <img src="<?= UPLOAD_URL . '/' . $e['photo'] ?>" alt="">
                                    <?php else: ?>
                                        <?= strtoupper(substr($e['first_name'], 0, 1) . substr($e['last_name'], 0, 1)) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <div class="fw-600"><?= htmlspecialchars($e['first_name'] . ' ' . $e['last_name']) ?></div>
                                    <div class="text-muted-sm"><?= htmlspecialchars($e['email'] ?: '-') ?></div>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark"><?= htmlspecialchars($e['employee_code']) ?></span></td>
                        <td><?= htmlspecialchars($e['department_name'] ?? '-') ?></td>
                        <td><?= htmlspecialchars($e['position'] ?: $e['job_title'] ?: '-') ?></td>
                        <td>
                            <div class="text-sm"><?= htmlspecialchars($e['phone'] ?: '-') ?></div>
                        </td>
                        <td>
                            <?php
                            $statusClass = [
                                'permanent' => 'success', 'contract' => 'info',
                                'probation' => 'warning', 'intern' => 'primary',
                                'terminated' => 'danger', 'resigned' => 'secondary'
                            ];
                            $cls = $statusClass[$e['employment_status']] ?? 'secondary';
                            ?>
                            <span class="badge bg-<?= $cls ?>"><?= ucfirst($e['employment_status']) ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($e['face_enrolled']): ?>
                                <i class="bi bi-camera-video-fill text-success" title="Face enrolled"></i>
                            <?php else: ?>
                                <i class="bi bi-camera-video text-muted" title="Not enrolled"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light btn-icon-sm" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/employees/<?= $e['id'] ?>"><i class="bi bi-eye me-2"></i>View Profile</a></li>
                                    <?php if (Auth::can('manage_employees')): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/employees/<?= $e['id'] ?>/edit"><i class="bi bi-pencil me-2"></i>Edit</a></li>
                                    <?php endif; ?>
                                    <?php if (Auth::can('manage_face_data')): ?>
                                    <li><a class="dropdown-item" href="<?= BASE_URL ?>/face/enroll/<?= $e['id'] ?>"><i class="bi bi-camera-video me-2"></i>Enroll Face</a></li>
                                    <?php endif; ?>
                                    <?php if (Auth::can('manage_employees')): ?>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="<?= BASE_URL ?>/employees/<?= $e['id'] ?>/delete" class="d-inline">
                                            <?= $csrf ?>
                                            <button type="submit" class="dropdown-item text-danger confirm-delete">
                                                <i class="bi bi-trash me-2"></i>Deactivate
                                            </button>
                                        </form>
                                    </li>
                                    <?php endif; ?>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($employees)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-people display-4 d-block mb-2 opacity-50"></i>
                        No employees found.
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
