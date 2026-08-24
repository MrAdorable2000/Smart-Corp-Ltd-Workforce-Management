<?php
/**
 * Authentication Controller
 * Handles login, logout, registration, password reset, OTP
 */
class AuthController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->redirect('/login');
    }

    public function login()
    {
        if (Auth::check()) {
            $this->redirect('/dashboard');
        }
        $this->layout = 'auth';
        $this->view('auth/login', [
            'csrf' => CSRF::field()
        ]);
    }

    public function loginPost()
    {
        $this->validateCsrf();

        $email    = trim($this->input('email', ''));
        $password = $this->input('password', '');
        $remember = (bool) $this->input('remember', false);
        $otp      = trim($this->input('otp', ''));

        if (empty($email) || empty($password)) {
            Flash::set('error', 'Email and password are required.');
            $this->redirect('/login');
        }

        $result = Auth::attempt($email, $password, $remember);

        if ($result === false) {
            Flash::set('error', 'Invalid email or password.');
            $this->redirect('/login');
        }

        if (is_array($result) && isset($result['locked'])) {
            Flash::set('error', 'Account locked. Try again after ' . date('H:i:s', strtotime($result['until'])));
            $this->redirect('/login');
        }

        if (is_array($result) && isset($result['pending'])) {
            Flash::set('error', 'Your account is pending admin approval. Please wait for an administrator to approve your registration.');
            $this->redirect('/login');
        }

        if (is_array($result) && isset($result['inactive'])) {
            $status = $result['status'] ?? 'inactive';
            if ($status === 'suspended') {
                Flash::set('error', 'Your account has been SUSPENDED by an administrator. You cannot access the system. Please contact HR or your manager to request reactivation.');
            } else {
                Flash::set('error', "Your account is {$status}. Please contact an administrator.");
            }
            $this->redirect('/login');
        }

        // OTP for admin roles (Two-Factor)
        if (Auth::user()['role_slug'] === 'super_admin' && Auth::user()['two_fa_enabled'] ?? false) {
            $this->sendOtp();
            Session::getInstance()->set('_otp_pending', true);
            $this->redirect('/verify-otp');
        }

        Flash::set('success', 'Welcome back, ' . Auth::name() . '!');
        $this->redirect('/dashboard');
    }

    public function logout()
    {
        Auth::logout();
        Flash::set('success', 'You have been logged out successfully.');
        $this->redirect('/');  // Redirect to public home page
    }

    public function forgot()
    {
        $this->layout = 'auth';
        $this->view('auth/forgot', ['csrf' => CSRF::field()]);
    }

    public function forgotPost()
    {
        $this->validateCsrf();
        $email = trim($this->input('email', ''));

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Flash::set('error', 'Please enter a valid email address.');
            $this->redirect('/forgot-password');
        }

        $user = Database::getInstance()->fetch(
            "SELECT * FROM users WHERE email = :email AND status = 'active'",
            ['email' => $email]
        );

        if ($user) {
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            Database::getInstance()->update('users', [
                'password_reset_token'      => hash('sha256', $token),
                'password_reset_expires_at' => $expires
            ], 'id = :id', ['id' => $user['id']]);

            $resetLink = BASE_URL . "/reset-password?token=" . $token;

            // In production: send via SMTP
            // For now: store as notification
            Database::getInstance()->insert('notifications', [
                'user_id'  => $user['id'],
                'type'     => 'system',
                'title'    => 'Password Reset Request',
                'message'  => "Click the link to reset your password: {$resetLink}",
                'channel'  => 'in_app',
                'status'   => 'sent',
                'sent_at'  => date('Y-m-d H:i:s')
            ]);

            if (APP_ENV === 'development') {
                Flash::set('info', "Dev mode: Reset link - {$resetLink}");
            }
        }

        Flash::set('success', 'If the email exists, password reset instructions have been sent.');
        $this->redirect('/login');
    }

    public function reset()
    {
        $token = $this->query('token', '');
        if (empty($token)) {
            Flash::set('error', 'Invalid reset link.');
            $this->redirect('/forgot-password');
        }

        $user = Database::getInstance()->fetch(
            "SELECT * FROM users WHERE password_reset_token = :token AND password_reset_expires_at > NOW()",
            ['token' => hash('sha256', $token)]
        );

        if (!$user) {
            Flash::set('error', 'Reset token invalid or expired.');
            $this->redirect('/forgot-password');
        }

        $this->layout = 'auth';
        $this->view('auth/reset', ['token' => $token, 'csrf' => CSRF::field()]);
    }

    public function resetPost()
    {
        $this->validateCsrf();
        $token    = $this->input('token', '');
        $password = $this->input('password', '');
        $confirm  = $this->input('password_confirmation', '');

        if (strlen($password) < 8) {
            Flash::set('error', 'Password must be at least 8 characters.');
            $this->redirect('/reset-password?token=' . $token);
        }
        if ($password !== $confirm) {
            Flash::set('error', 'Passwords do not match.');
            $this->redirect('/reset-password?token=' . $token);
        }

        $user = Database::getInstance()->fetch(
            "SELECT * FROM users WHERE password_reset_token = :token AND password_reset_expires_at > NOW()",
            ['token' => hash('sha256', $token)]
        );

        if (!$user) {
            Flash::set('error', 'Reset token invalid or expired.');
            $this->redirect('/forgot-password');
        }

        Database::getInstance()->update('users', [
            'password_hash'             => password_hash($password, PASSWORD_ALGO, ['cost' => PASSWORD_COST]),
            'password_reset_token'      => null,
            'password_reset_expires_at' => null
        ], 'id = :id', ['id' => $user['id']]);

        Auth::audit('password_reset', 'users', "User {$user['email']} reset password");

        Flash::set('success', 'Password reset successfully. Please login.');
        $this->redirect('/login');
    }

    public function verifyOtp()
    {
        if (!Session::getInstance()->get('_otp_pending')) {
            $this->redirect('/dashboard');
        }
        $this->layout = 'auth';
        $this->view('auth/verify_otp', ['csrf' => CSRF::field()]);
    }

    public function verifyOtpPost()
    {
        $this->validateCsrf();
        $otp = trim($this->input('otp', ''));
        $user = Auth::user();

        $dbUser = Database::getInstance()->fetch(
            "SELECT * FROM users WHERE id = :id",
            ['id' => $user['id']]
        );

        if (!$dbUser || !$dbUser['otp_code'] || strtotime($dbUser['otp_expires_at']) < time()) {
            Flash::set('error', 'OTP expired. Please login again.');
            Auth::logout();
            $this->redirect('/login');
        }

        if (!hash_equals($dbUser['otp_code'], $otp)) {
            Flash::set('error', 'Invalid OTP code.');
            $this->redirect('/verify-otp');
        }

        Database::getInstance()->update('users', [
            'otp_code' => null, 'otp_expires_at' => null
        ], 'id = :id', ['id' => $user['id']]);
        Session::getInstance()->remove('_otp_pending');

        Flash::set('success', 'Welcome back, ' . Auth::name() . '!');
        $this->redirect('/dashboard');
    }

    private function sendOtp()
    {
        $otp = (string) random_int(100000, 999999);
        $expires = date('Y-m-d H:i:s', time() + 300); // 5 min
        Database::getInstance()->update('users', [
            'otp_code' => $otp, 'otp_expires_at' => $expires
        ], 'id = :id', ['id' => Auth::id()]);

        Database::getInstance()->insert('notifications', [
            'user_id'  => Auth::id(),
            'type'     => 'system',
            'title'    => 'Your OTP Code',
            'message'  => "Your one-time password is: {$otp}",
            'channel'  => 'in_app',
            'status'   => 'sent',
            'sent_at'  => date('Y-m-d H:i:s')
        ]);

        if (APP_ENV === 'development') {
            Flash::set('info', "Dev mode OTP: {$otp}");
        }
    }

    public function register() { /* Reserved for future use */ $this->redirect('/login'); }
    public function registerPost() { /* Reserved */ $this->redirect('/login'); }
}
