<?php /** @var array $data */ ?>
<div class="register-page">
    <div class="container">
        <div class="register-card">
            <div class="register-header">
                <div class="register-icon">
                    <i class="bi bi-person-plus-fill"></i>
                </div>
                <h1 class="register-title">Create New Account</h1>
                <p class="register-subtitle">Join the Smart Attendance System</p>
            </div>

            <div class="approval-notice">
                <i class="bi bi-clock-history"></i>
                <div class="approval-notice-text">
                    <strong>Admin Approval Required</strong><br>
                    After registration, your account will be created with <strong>"Pending"</strong> status.
                    An administrator will review and approve your account before you can login.
                    You'll receive a notification once approved.
                </div>
            </div>

            <form method="POST" action="<?= BASE_URL ?>/register">
                <?= $csrf ?>

                <div class="mb-3">
                    <label class="form-label required">Full Name</label>
                    <input type="text" name="name" class="form-control" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="e.g. John Smith">
                </div>

                <div class="mb-3">
                    <label class="form-label required">Email Address</label>
                    <input type="email" name="email" class="form-control" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com">
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone Number <small class="text-muted">(with country code for WhatsApp)</small></label>
                    <input type="tel" name="phone" class="form-control" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+1 555 123 4567">
                    <small class="text-muted">Used for WhatsApp contact in the user directory</small>
                </div>

                <div class="mb-3">
                    <label class="form-label required">Role</label>
                    <select name="role_id" class="form-select" required>
                        <option value="">-- Select Role --</option>
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r['id'] ?>" <?= ($_POST['role_id'] ?? '') == $r['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['name']) ?> — <?= htmlspecialchars($r['description']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Super Admin role is not selectable</small>
                </div>

                <div class="mb-3">
                    <label class="form-label">Employee ID <small class="text-muted">(if you have one)</small></label>
                    <input type="number" name="employee_id" class="form-control" value="<?= $_POST['employee_id'] ?? '' ?>" min="1" placeholder="e.g. 1, 2, 3...">
                    <small class="text-muted">Leave blank if you don't have an employee record</small>
                </div>

                <div class="mb-3">
                    <label class="form-label required">Password</label>
                    <input type="password" name="password" class="form-control" required minlength="8" placeholder="At least 8 characters">
                </div>

                <div class="mb-4">
                    <label class="form-label required">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" required minlength="8">
                </div>

                <button type="submit" class="btn btn-primary w-100 mb-3" style="padding: 12px;">
                    <i class="bi bi-send-fill"></i> Submit Registration Request
                </button>

                <div class="text-center">
                    <small class="text-muted">
                        Already have an account? <a href="<?= BASE_URL ?>/login">Login here</a>
                    </small>
                </div>
            </form>
        </div>
    </div>
</div>
