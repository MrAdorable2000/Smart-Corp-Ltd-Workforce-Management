<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1">Department Management</h4>
        <p class="text-muted mb-0">Manage organizational departments</p>
    </div>
    <a href="<?= BASE_URL ?>/departments/create" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add Department</a>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-diagram-3 me-2"></i>All Departments (<?= count($departments) ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover data-table mb-0">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Branch</th><th>Employees</th><th>Description</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $d): ?>
                    <tr>
                        <td><span class="badge bg-primary"><?= htmlspecialchars($d['code']) ?></span></td>
                        <td class="fw-600"><?= htmlspecialchars($d['name']) ?></td>
                        <td><?= htmlspecialchars($d['branch_name'] ?? '-') ?></td>
                        <td><span class="badge bg-light text-dark"><?= $d['employee_count'] ?></span></td>
                        <td class="text-sm text-muted"><?= htmlspecialchars($d['description'] ?: '-') ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>/departments/<?= $d['id'] ?>/edit" class="btn btn-sm btn-light btn-icon-sm"><i class="bi bi-pencil"></i></a>
                            <form method="POST" action="<?= BASE_URL ?>/departments/<?= $d['id'] ?>/delete" class="d-inline">
                                <?= $csrf ?>
                                <button class="btn btn-sm btn-light btn-icon-sm text-danger confirm-delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($departments)): ?>
                    <tr><td colspan="6" class="text-center py-4 text-muted">No departments found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
