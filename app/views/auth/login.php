<div class="auth-form-wrap">
    <h2 class="auth-heading">Welcome Back</h2>
    <p class="auth-subheading">Sign in to your account to continue</p>

    <form method="POST" action="<?= BASE_URL ?>/login" class="auth-form">
        <?= $csrf ?>

        <div class="form-floating mb-3">
            <input type="email" name="email" id="email" class="form-control" placeholder="Email Address" required autofocus value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            <label for="email"><i class="bi bi-envelope me-1"></i> Email Address</label>
        </div>

        <div class="form-floating mb-3 position-relative">
            <input type="password" name="password" id="password" class="form-control" placeholder="Password" required>
            <label for="password"><i class="bi bi-lock me-1"></i> Password</label>
            <button type="button" class="toggle-pwd" data-target="password"><i class="bi bi-eye"></i></button>
        </div>

        <div class="form-check mb-3">
            <input type="checkbox" name="remember" id="remember" class="form-check-input" value="1">
            <label for="remember" class="form-check-label">Keep me signed in</label>
            <a href="<?= BASE_URL ?>/forgot-password" class="float-end">Forgot password?</a>
        </div>

        <button type="submit" class="btn btn-primary w-100 btn-auth">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
        </button>
    </form>

    <div class="demo-creds">
        <h6>Demo Credentials</h6>
        <div class="cred-grid">
            <div><strong>Super Admin:</strong><br>ethiennemugisha35@gmail.com</div>
            <div><strong>HR:</strong><br>hr@smartcorp.com</div>
            <div><strong>Manager:</strong><br>manager@smartcorp.com</div>
            <div><strong>Auditor:</strong><br>auditor@smartcorp.com</div>
        </div>
        <div class="text-center mt-2"><small class="text-muted">Password for all: <code>password</code></small></div>
    </div>
</div>
