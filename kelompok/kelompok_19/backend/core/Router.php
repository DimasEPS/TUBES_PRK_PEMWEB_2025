<?php
/**
 * Router Class
 * Handle routing untuk aplikasi
 */

class Router {
    private $routes = [];
    private $notFoundCallback;
    
    /**
     * Add GET route
     */
    public function get($path, $callback) {
        $this->addRoute('GET', $path, $callback);
    }
    
    /**
     * Add POST route
     */
    public function post($path, $callback) {
        $this->addRoute('POST', $path, $callback);
    }
    
    /**
     * Add route with method
     */
    private function addRoute($method, $path, $callback) {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'callback' => $callback
        ];
    }
    
    /**
     * Set 404 handler
     */
    public function notFound($callback) {
        $this->notFoundCallback = $callback;
    }
    
    /**
     * Run router
     */
    public function run() {
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove base path if needed
        $basePath = '/TUBES_PRK_PEMWEB_2025/kelompok/kelompok_19/backend/public';
        $requestUri = str_replace($basePath, '', $requestUri);
        
        if (empty($requestUri)) {
            $requestUri = '/';
        }
        
        foreach ($this->routes as $route) {
            if ($route['method'] === $requestMethod) {
                $pattern = $this->convertPathToRegex($route['path']);
                if (preg_match($pattern, $requestUri, $matches)) {
                    array_shift($matches); // Remove full match
                    call_user_func_array($route['callback'], $matches);
                    return;
                }
            }
        }
        
        // 404 Not Found
        if ($this->notFoundCallback) {
            call_user_func($this->notFoundCallback);
        } else {
            http_response_code(404);
            echo "404 - Page Not Found";
        }
    }
    
    /**
     * Convert path with params to regex
     */
    private function convertPathToRegex($path) {
        // Convert :param to regex capture group
        $pattern = preg_replace('/\/:([^\/]+)/', '/([^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}
