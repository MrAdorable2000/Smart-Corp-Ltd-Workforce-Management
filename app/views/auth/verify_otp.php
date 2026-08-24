<div class="auth-form-wrap">
    <h2 class="auth-heading">Two-Factor Verification</h2>
    <p class="auth-subheading">Enter the 6-digit code sent to you</p>

    <form method="POST" action="<?= BASE_URL ?>/verify-otp" class="auth-form">
        <?= $csrf ?>
        <div class="otp-input-wrap mb-3">
            <input type="text" name="otp" id="otp" class="form-control form-control-lg text-center" maxlength="6" pattern="[0-9]{6}" required autofocus inputmode="numeric">
        </div>
        <button type="submit" class="btn btn-primary w-100 btn-auth">
            <i class="bi bi-shield-check me-1"></i> Verify
        </button>
    </form>
    <div class="text-center mt-3">
        <a href="<?= BASE_URL ?>/logout">Cancel</a>
    </div>
</div>
