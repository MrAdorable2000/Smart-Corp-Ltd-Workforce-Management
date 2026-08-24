<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><?= $pageTitle ?></h4>
    <a href="<?= BASE_URL ?>/departments" class="btn btn-light"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/departments/<?= $department ? $department['id'] . '/update' : 'store' ?>">
                    <?= $csrf ?>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label required">Department Name</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($department['name'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Code</label>
                            <input type="text" name="code" class="form-control" required value="<?= htmlspecialchars($department['code'] ?? '') ?>" style="text-transform:uppercase">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">-- None --</option>
                                <?php foreach ($branches as $b): ?>
                                    <option value="<?= $b['id'] ?>" <?= ($department['branch_id'] ?? '') == $b['id'] ? 'selected' : '' ?>><?= htmlspecialchars($b['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6"></div>
                        <div class="col-12">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars($department['description'] ?? '') ?></textarea>
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= $department ? 'Update' : 'Create' ?></button>
                            <a href="<?= BASE_URL ?>/departments" class="btn btn-light">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Default Departments</div>
            <div class="card-body">
                <p class="text-muted-sm">Pre-configured departments in the system:</p>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check2 text-success"></i> HR</li>
                    <li><i class="bi bi-check2 text-success"></i> Finance</li>
                    <li><i class="bi bi-check2 text-success"></i> IT</li>
                    <li><i class="bi bi-check2 text-success"></i> Marketing</li>
                    <li><i class="bi bi-check2 text-success"></i> Procurement</li>
                    <li><i class="bi bi-check2 text-success"></i> Operations</li>
                    <li><i class="bi bi-check2 text-success"></i> Security</li>
                    <li><i class="bi bi-check2 text-success"></i> Administration</li>
                </ul>
            </div>
        </div>
    </div>
</div>
