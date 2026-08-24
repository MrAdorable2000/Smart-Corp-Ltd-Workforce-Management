<?php
/**
 * Base Controller Class
 * All controllers extend this
 */

abstract class Controller
{
    protected $layout = 'app';
    protected $request;
    protected $session;

    public function __construct()
    {
        $this->session = Session::getInstance();
    }

    /**
     * Render a view with layout
     */
    protected function view($view, $data = [])
    {
        extract($data);
        $viewFile = APP_PATH . '/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new Exception("View not found: {$view}");
        }

        // Capture view content
        ob_start();
        require $viewFile;
        $content = ob_get_clean();

        // Layout
        $layoutFile = APP_PATH . '/views/layouts/' . $this->layout . '.php';
        if (file_exists($layoutFile)) {
            require $layoutFile;
        } else {
            echo $content;
        }
    }

    /**
     * Render view without layout (partial)
     */
    protected function partial($view, $data = [])
    {
        extract($data);
        $viewFile = APP_PATH . '/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        }
    }

    /**
     * Return JSON response (for AJAX/API)
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    /**
     * Redirect to URL
     */
    protected function redirect($url)
    {
        if (strpos($url, 'http') !== 0) {
            $url = BASE_URL . $url;
        }
        header("Location: {$url}");
        exit;
    }

    /**
     * Redirect with flash message
     */
    protected function redirectWith($url, $type, $message)
    {
        Flash::set($type, $message);
        $this->redirect($url);
    }

    /**
     * Get POST input
     */
    protected function input($key = null, $default = null)
    {
        if ($key === null) {
            return $_POST;
        }
        return $_POST[$key] ?? $default;
    }

    /**
     * Get GET input
     */
    protected function query($key = null, $default = null)
    {
        if ($key === null) {
            return $_GET;
        }
        return $_GET[$key] ?? $default;
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax()
    {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    /**
     * Check if request is POST
     */
    protected function isPost()
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    /**
     * Validate CSRF token
     */
    protected function validateCsrf()
    {
        $token = $this->input(CSRF_TOKEN_NAME) ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (!CSRF::verify($token)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Invalid CSRF token'], 419);
            }
            Flash::set('error', 'Security token expired. Please try again.');
            $this->redirect('/login');
        }
    }

    /**
     * Require authentication
     */
    protected function requireAuth()
    {
        if (!Auth::check()) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Authentication required'], 401);
            }
            $this->redirect('/login');
        }
    }

    /**
     * Require specific permission
     */
    protected function requirePermission($permission)
    {
        $this->requireAuth();
        if (!Auth::can($permission)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Insufficient permissions'], 403);
            }
            Flash::set('error', 'You do not have permission to access this resource.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Require specific role
     */
    protected function requireRole(...$roles)
    {
        $this->requireAuth();
        if (!in_array(Auth::role(), $roles)) {
            if ($this->isAjax()) {
                $this->json(['success' => false, 'message' => 'Insufficient role'], 403);
            }
            Flash::set('error', 'Access denied.');
            $this->redirect('/dashboard');
        }
    }

    /**
     * Sanitize input for output (XSS protection)
     */
    protected function clean($value)
    {
        if (is_array($value)) {
            return array_map([$this, 'clean'], $value);
        }
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}
