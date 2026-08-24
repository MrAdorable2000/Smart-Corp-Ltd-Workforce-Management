<div class="auth-form-wrap">
    <h2 class="auth-heading">Set New Password</h2>
    <p class="auth-subheading">Choose a strong password for your account</p>

    <form method="POST" action="<?= BASE_URL ?>/reset-password" class="auth-form">
        <?= $csrf ?>
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="form-floating mb-3 position-relative">
            <input type="password" name="password" id="password" class="form-control" placeholder="New Password" required minlength="8">
            <label for="password"><i class="bi bi-lock me-1"></i> New Password</label>
            <button type="button" class="toggle-pwd" data-target="password"><i class="bi bi-eye"></i></button>
        </div>
        <div class="form-floating mb-3 position-relative">
            <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="Confirm Password" required>
            <label for="password_confirmation"><i class="bi bi-lock-fill me-1"></i> Confirm Password</label>
            <button type="button" class="toggle-pwd" data-target="password_confirmation"><i class="bi bi-eye"></i></button>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-auth">
            <i class="bi bi-check2-circle me-1"></i> Reset Password
        </button>
    </form>
</div>
