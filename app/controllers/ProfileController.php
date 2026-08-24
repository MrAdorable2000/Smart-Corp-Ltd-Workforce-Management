<?php
class ProfileController extends Controller
{
    public function index()
    {
        $this->requireAuth();
        $user = Database::getInstance()->fetch(
            "SELECT u.*, r.name AS role_name, r.slug AS role_slug,
                    e.employee_code, e.first_name, e.last_name
             FROM users u
             INNER JOIN roles r ON r.id = u.role_id
             LEFT JOIN employees e ON e.id = u.employee_id
             WHERE u.id = :id",
            ['id' => Auth::id()]
        );

        $activities = Database::getInstance()->fetchAll(
            "SELECT * FROM activity_logs WHERE user_id = :uid ORDER BY created_at DESC LIMIT 10",
            ['uid' => Auth::id()]
        );

        $this->view('profile/index', [
            'title' => 'My Profile',
            'pageTitle' => 'My Profile',
            'user' => $user,
            'activities' => $activities,
            'csrf' => CSRF::field()
        ]);
    }

    public function update()
    {
        $this->requireAuth();
        $this->validateCsrf();

        $data = $this->input();
        $update = ['name' => $data['name'], 'phone' => $data['phone'] ?? null];

        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
            try {
                $uploader = new Uploader('avatar', ALLOWED_IMAGE_TYPES);
                $update['avatar'] = $uploader->upload('avatars', 'user_' . Auth::id());
            } catch (Exception $e) {
                Flash::set('error', 'Avatar upload failed: ' . $e->getMessage());
                $this->redirect('/profile');
            }
        }

        Database::getInstance()->update('users', $update, 'id = :id', ['id' => Auth::id()]);

        // Update session
        $sessionUser = Session::getInstance()->get('_user');
        $sessionUser['name'] = $data['name'];
        $sessionUser['avatar'] = $update['avatar'] ?? $sessionUser['avatar'];
        Session::getInstance()->set('_user', $sessionUser);

        Auth::audit('update', 'profile', 'Updated own profile');
        Flash::set('success', 'Profile updated.');
        $this->redirect('/profile');
    }

    public function password()
    {
        $this->requireAuth();
        $this->validateCsrf();

        $current = $this->input('current_password');
        $new = $this->input('new_password');
        $confirm = $this->input('confirm_password');

        $user = Database::getInstance()->fetch("SELECT * FROM users WHERE id = :id", ['id' => Auth::id()]);

        if (!password_verify($current, $user['password_hash'])) {
            Flash::set('error', 'Current password is incorrect.');
            $this->redirect('/profile');
        }
        if (strlen($new) < 8) {
            Flash::set('error', 'New password must be at least 8 characters.');
            $this->redirect('/profile');
        }
        if ($new !== $confirm) {
            Flash::set('error', 'New passwords do not match.');
            $this->redirect('/profile');
        }

        Database::getInstance()->update('users', [
            'password_hash' => password_hash($new, PASSWORD_BCRYPT, ['cost' => PASSWORD_COST])
        ], 'id = :id', ['id' => Auth::id()]);

        Auth::audit('password_change', 'profile', 'Changed own password');
        Flash::set('success', 'Password changed successfully.');
        $this->redirect('/profile');
    }
}
