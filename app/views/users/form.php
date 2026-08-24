<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= $pageTitle ?></h4>
    <a href="<?= BASE_URL ?>/users" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= $user ? BASE_URL . '/users/' . $user['id'] . '/update' : BASE_URL . '/users/store' ?>">
                    <?= $csrf ?>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label required">Full Name</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Role</label>
                            <select name="role_id" class="form-select" required>
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= ($user['role_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= htmlspecialchars($r['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Email</label>
                            <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($user['email'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Employee ID <small class="text-muted">(leave blank if not an employee)</small></label>
                            <input type="number" name="employee_id" class="form-control" value="<?= $user['employee_id'] ?? '' ?>" min="1" placeholder="e.g. 1, 2, 3...">
                            <small class="text-muted">Only fill if this user should be linked to an employee record.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" <?= ($user['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="inactive" <?= ($user['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                                <option value="suspended" <?= ($user['status'] ?? '') === 'suspended' ? 'selected' : '' ?>>Suspended</option>
                                <option value="pending" <?= ($user['status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label"><?= $user ? 'New Password (leave blank to keep)' : 'Password' ?></label>
                            <input type="password" name="password" class="form-control" <?= $user ? '' : 'required' ?> minlength="8">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= $user ? 'Update User' : 'Create User' ?></button>
                            <a href="<?= BASE_URL ?>/users" class="btn btn-light">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Available Roles</div>
            <div class="card-body">
                <?php foreach ($roles as $r): ?>
                <div class="mb-2">
                    <strong><?= htmlspecialchars($r['name']) ?></strong>
                    <p class="text-muted-sm mb-0"><?= htmlspecialchars($r['description']) ?></p>
                </div>
                <hr class="my-2">
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
