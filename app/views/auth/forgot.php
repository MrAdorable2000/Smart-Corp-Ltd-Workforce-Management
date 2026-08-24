<div class="auth-form-wrap">
    <h2 class="auth-heading">Reset Password</h2>
    <p class="auth-subheading">Enter your email to receive reset instructions</p>

    <form method="POST" action="<?= BASE_URL ?>/forgot-password" class="auth-form">
        <?= $csrf ?>
        <div class="form-floating mb-3">
            <input type="email" name="email" id="email" class="form-control" placeholder="Email Address" required>
            <label for="email"><i class="bi bi-envelope me-1"></i> Email Address</label>
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-auth">
            <i class="bi bi-send me-1"></i> Send Reset Link
        </button>
    </form>

    <div class="text-center mt-3">
        <a href="<?= BASE_URL ?>/login"><i class="bi bi-arrow-left"></i> Back to Login</a>
    </div>
</div>
