<?php /** @var array $data */ ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0">My Profile</h4>
</div>

<div class="row g-3">
    <div class="col-md-4">
        <div class="card text-center mb-3">
            <div class="card-body">
                <div class="employee-avatar mb-3" style="width:100px;height:100px;font-size:32px;margin:0 auto;">
                    <?php if (!empty($user['avatar']) && file_exists(UPLOAD_PATH . '/' . $user['avatar'])): ?>
                        <img src="<?= UPLOAD_URL . '/' . $user['avatar'] ?>" alt="">
                    <?php else: ?>
                        <?= strtoupper(substr($user['name'], 0, 1)) ?>
                    <?php endif; ?>
                </div>
                <h5 class="mb-1"><?= htmlspecialchars($user['name']) ?></h5>
                <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <span class="badge bg-primary"><?= htmlspecialchars($user['role_name']) ?></span>
                <?php if ($user['employee_code']): ?>
                <p class="text-muted-sm mt-2"><i class="bi bi-person-badge"></i> <?= htmlspecialchars($user['employee_code']) ?></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-clock-history me-2"></i>Recent Activity</div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush">
                    <?php foreach ($activities as $a): ?>
                    <div class="list-group-item py-2">
                        <div class="text-sm fw-600"><?= htmlspecialchars($a['activity']) ?></div>
                        <small class="text-muted"><?= date('M d, Y H:i', strtotime($a['created_at'])) ?> · <?= htmlspecialchars($a['ip_address']) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header"><i class="bi bi-person me-2"></i>Profile Information</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/profile/update" enctype="multipart/form-data">
                    <?= $csrf ?>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label required">Full Name</label>
                            <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($user['name']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone</label>
                            <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email (read-only)</label>
                            <input type="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Role (read-only)</label>
                            <input type="text" class="form-control" value="<?= htmlspecialchars($user['role_name']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Avatar</label>
                            <input type="file" name="avatar" class="form-control" accept="image/*">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-primary"><i class="bi bi-check-lg"></i> Update Profile</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-lock me-2"></i>Change Password</div>
            <div class="card-body">
                <form method="POST" action="<?= BASE_URL ?>/profile/password">
                    <?= $csrf ?>
                    <div class="row g-3">
                        <div class="col-md-12">
                            <label class="form-label required">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">New Password</label>
                            <input type="password" name="new_password" class="form-control" required minlength="8">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label required">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="col-12">
                            <button class="btn btn-warning"><i class="bi bi-key"></i> Change Password</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
