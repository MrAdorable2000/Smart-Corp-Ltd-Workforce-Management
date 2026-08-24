<?php
/**
 * Authentication Middleware
 * Ensures user is logged in AND still active (not suspended)
 */

class AuthMiddleware
{
    public function handle($params = [])
    {
        if (!Auth::check()) {
            // Auth::check() returns false if user is suspended/inactive/deleted
            // (it auto-logs them out in that case). Show a message if their
            // session was just killed due to suspension.
            if (isset($_COOKIE['was_suspended'])) {
                setcookie('was_suspended', '', time() - 3600, '/');
                Flash::set('error', 'Your session has ended because your account was suspended by an administrator. Please contact HR to request reactivation.');
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
                strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                http_response_code(401);
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Authentication required']);
                exit;
            }
            header('Location: ' . BASE_URL . '/login');
            exit;
        }
    }
}
