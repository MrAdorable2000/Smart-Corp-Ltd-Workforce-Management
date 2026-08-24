<?php
/**
 * Simple Router
 * Maps URLs to controller@action
 */

class Router
{
    private static $routes = [];
    private static $middleware = [];

    /**
     * Register a route
     * @param string $method GET|POST|PUT|DELETE|ANY
     * @param string $pattern URL pattern with {params}
     * @param string $action Controller@method
     * @param array $middleware Middleware list
     */
    public static function add($method, $pattern, $action, $middleware = [])
    {
        self::$routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $pattern,
            'action'     => $action,
            'middleware' => $middleware
        ];
    }

    public static function get($pattern, $action, $middleware = [])
    {
        self::add('GET', $pattern, $action, $middleware);
    }

    public static function post($pattern, $action, $middleware = [])
    {
        self::add('POST', $pattern, $action, $middleware);
    }

    public static function put($pattern, $action, $middleware = [])
    {
        self::add('PUT', $pattern, $action, $middleware);
    }

    public static function delete($pattern, $action, $middleware = [])
    {
        self::add('DELETE', $pattern, $action, $middleware);
    }

    public static function any($pattern, $action, $middleware = [])
    {
        self::add('ANY', $pattern, $action, $middleware);
    }

    /**
     * Dispatch the current request
     */
    public static function dispatch($url, $method = null)
    {
        $method = $method ?: $_SERVER['REQUEST_METHOD'];

        // Handle _method override for forms
        if ($method === 'POST' && isset($_POST['_method'])) {
            $method = strtoupper($_POST['_method']);
        }

        // Normalize URL: ensure leading slash (route patterns all start with "/")
        // Empty string means homepage — leave as-is so it matches route ''
        if ($url !== '' && $url[0] !== '/') {
            $url = '/' . $url;
        }

        foreach (self::$routes as $route) {
            if ($route['method'] !== 'ANY' && $route['method'] !== $method) {
                continue;
            }

            $pattern = $route['pattern'];
            // Convert {param} to regex
            $regex = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $url, $matches)) {
                // Extract named params
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key)) {
                        $params[$key] = $value;
                    }
                }

                // Run middleware
                foreach ($route['middleware'] as $mw) {
                    $middlewareClass = ucfirst($mw) . 'Middleware';
                    $middlewareFile = APP_PATH . '/middleware/' . $middlewareClass . '.php';
                    if (file_exists($middlewareFile)) {
                        require_once $middlewareFile;
                        $instance = new $middlewareClass();
                        $instance->handle($params);
                    }
                }

                // Call controller
                list($controller, $action) = explode('@', $route['action']);

                // Handle namespaced controllers (e.g. Api\Auth)
                if (strpos($controller, '\\') !== false || strpos($controller, '/') !== false) {
                    $parts = explode('\\', str_replace('/', '\\', $controller));
                    $controllerClass = implode('\\', $parts) . 'Controller';
                    $relativePath = implode('/', $parts) . 'Controller.php';
                    $controllerFile = APP_PATH . '/controllers/' . $relativePath;
                } else {
                    $controllerClass = $controller . 'Controller';
                    $controllerFile = APP_PATH . '/controllers/' . $controllerClass . '.php';
                }

                if (!file_exists($controllerFile)) {
                    self::notFound("Controller not found: {$controllerClass}");
                }

                require_once $controllerFile;

                // Instantiate (handle namespaced class)
                if (class_exists($controllerClass)) {
                    $instance = new $controllerClass();
                } else {
                    // Try without namespace prefix
                    $shortName = basename(str_replace('\\', '/', $controllerClass));
                    if (class_exists($shortName)) {
                        $instance = new $shortName();
                    } else {
                        self::notFound("Class not found: {$controllerClass}");
                    }
                }

                if (!method_exists($instance, $action)) {
                    self::notFound("Method not found: {$controllerClass}@{$action}");
                }

                call_user_func_array([$instance, $action], $params);
                return;
            }
        }

        self::notFound("Route not found: {$method} {$url}");
    }

    public static function notFound($message = 'Page not found')
    {
        http_response_code(404);
        if (APP_ENV === 'development') {
            echo "<h1>404 - Not Found</h1><p>{$message}</p>";
        } else {
            $viewFile = APP_PATH . '/views/errors/404.php';
            if (file_exists($viewFile)) {
                require $viewFile;
            } else {
                echo '<h1>404 - Page Not Found</h1>';
            }
        }
        exit;
    }
}
