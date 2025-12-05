<?php
/**
 * Base Controller
 * Parent class untuk semua controller
 */

class Controller {
    protected $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Load view file
     */
    protected function view($viewPath, $data = []) {
        extract($data);
        require_once dirname(__DIR__) . "/modules/{$viewPath}.php";
    }
    
    /**
     * Redirect to URL
     */
    protected function redirect($path) {
        header("Location: " . BASE_URL . $path);
        exit;
    }
    
    /**
     * Return JSON response
     */
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    /**
     * Get POST data
     */
    protected function post($key = null, $default = null) {
        if ($key === null) {
            return $_POST;
        }
        return isset($_POST[$key]) ? $_POST[$key] : $default;
    }
    
    /**
     * Get GET data
     */
    protected function get($key = null, $default = null) {
        if ($key === null) {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : $default;
    }
    
    /**
     * Validate CSRF token
     */
    protected function validateCSRF($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Check if user is logged in
     */
    protected function requireAuth($role = null) {
        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
        }
        
        if ($role !== null && $_SESSION['role'] !== $role) {
            $this->redirect('/unauthorized');
        }
    }
}
