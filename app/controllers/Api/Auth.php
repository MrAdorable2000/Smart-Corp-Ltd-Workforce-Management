<?php
/**
 * API Authentication Controller
 * Handles REST API authentication with API tokens
 */

namespace Api;

class Auth
{
    public function login()
    {
        $input = json_decode(file_get_contents('php://input'), true);
        $email = $input['email'] ?? '';
        $password = $input['password'] ?? '';

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email and password required']);
            return;
        }

        $db = \Database::getInstance();
        $user = $db->fetch(
            "SELECT u.*, r.slug AS role_slug, r.name AS role_name
             FROM users u INNER JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email AND u.status = 'active'",
            ['email' => $email]
        );

        if (!$user || !password_verify($password, $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
            return;
        }

        // Generate API token (simplified - production should use JWT or OAuth)
        $token = bin2hex(random_bytes(32));
        $db->update('users', ['remember_token' => hash('sha256', $token)], 'id = :id', ['id' => $user['id']]);

        $db->insert('activity_logs', [
            'user_id' => $user['id'],
            'activity' => 'api_login',
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role_slug'],
            ]
        ]);
    }

    public function logout()
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $token = str_replace('Bearer ', '', $token);
        if ($token) {
            \Database::getInstance()->update('users',
                ['remember_token' => null],
                'remember_token = :t',
                ['t' => hash('sha256', $token)]
            );
        }
        echo json_encode(['success' => true, 'message' => 'Logged out']);
    }
}
