<?php
/**
 * Session Management Class
 * Secure session handling with timeout and regeneration
 */

class Session
{
    private static $instance = null;
    private $started = false;

    private function __construct()
    {
        $this->start();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function start()
    {
        if ($this->started || session_status() === PHP_SESSION_ACTIVE) {
            $this->started = true;
            return;
        }

        // Secure session params
        session_set_cookie_params([
            'lifetime' => SESSION_TIMEOUT,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        session_name('SMART_ATTENDANCE_SESS');
        session_start();
        $this->started = true;

        // Regenerate ID periodically to prevent fixation
        if (!isset($_SESSION['_created'])) {
            $_SESSION['_created'] = time();
        } else if (time() - $_SESSION['_created'] > 300) {
            session_regenerate_id(true);
            $_SESSION['_created'] = time();
        }

        // Check session timeout (only for authenticated users)
        if (isset($_SESSION['_last_activity']) && (time() - $_SESSION['_last_activity'] > SESSION_TIMEOUT)) {
            $this->destroy();
            return;
        }
        $_SESSION['_last_activity'] = time();
    }

    public function get($key, $default = null)
    {
        return $_SESSION[$key] ?? $default;
    }

    public function set($key, $value)
    {
        $_SESSION[$key] = $value;
    }

    public function has($key)
    {
        return isset($_SESSION[$key]);
    }

    public function remove($key)
    {
        unset($_SESSION[$key]);
    }

    public function destroy()
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public function regenerate()
    {
        session_regenerate_id(true);
    }

    public function csrfToken()
    {
        if (!$this->has('_csrf_token')) {
            $this->set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return $this->get('_csrf_token');
    }
}

/**
 * Helper to access session
 */
function session() {
    return Session::getInstance();
}
