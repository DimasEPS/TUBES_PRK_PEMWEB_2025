<?php
/**
 * Bootstrap File
 * Initialize application
 */

// CORS headers for frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
session_start();

// Load .env file
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (preg_match('/^(["\'])(.*)\1$/', $value, $matches)) {
            $value = $matches[2];
        }
        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv("$key=$value");
        }
    }
}

// Helper to get env
function env($key, $default = null) {
    return $_ENV[$key] ?? getenv($key) ?: $default;
}

// Error reporting
if (env('APP_DEBUG', 'false') === 'true') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Load configuration
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/config/database.php';

// Load core files
require_once dirname(__DIR__) . '/core/Helper.php';
require_once dirname(__DIR__) . '/core/Controller.php';
require_once dirname(__DIR__) . '/core/Router.php';

// Load controllers
require_once dirname(__DIR__) . '/modules/pages/PageController.php';
require_once dirname(__DIR__) . '/modules/auth/AuthController.php';
require_once dirname(__DIR__) . '/modules/mahasiswa/MahasiswaController.php';
require_once dirname(__DIR__) . '/modules/petugas/PetugasController.php';
require_once dirname(__DIR__) . '/modules/admin/AdminController.php';

// Initialize router
$router = new Router();

// ==================== PUBLIC ROUTES ====================
$router->get('/', function() {
    $controller = new PageController();
    $controller->index();
});

$router->get('/about', function() {
    $controller = new PageController();
    $controller->about();
});

$router->get('/unauthorized', function() {
    $controller = new PageController();
    $controller->unauthorized();
});

// ==================== AUTH ROUTES ====================
$router->post('/login', function() {
    $controller = new AuthController();
    $controller->login();
});

$router->post('/register', function() {
    $controller = new AuthController();
    $controller->register();
});

$router->get('/logout', function() {
    $controller = new AuthController();
    $controller->logout();
});

// ==================== MAHASISWA ROUTES ====================
$router->get('/mahasiswa/dashboard', function() {
    $controller = new MahasiswaController();
    $controller->dashboard();
});

$router->get('/mahasiswa/complaints', function() {
    $controller = new MahasiswaController();
    $controller->listComplaints();
});

$router->get('/mahasiswa/complaints/categories', function() {
    $controller = new MahasiswaController();
    $controller->getCategories();
});

$router->post('/mahasiswa/complaints/create', function() {
    $controller = new MahasiswaController();
    $controller->storeComplaint();
});

$router->get('/mahasiswa/complaints/:id', function($id) {
    $controller = new MahasiswaController();
    $controller->detailComplaint($id);
});

// ==================== PETUGAS ROUTES ====================
$router->get('/petugas/dashboard', function() {
    $controller = new PetugasController();
    $controller->dashboard();
});

$router->get('/petugas/complaints', function() {
    $controller = new PetugasController();
    $controller->listComplaints();
});

$router->get('/petugas/complaints/:id', function($id) {
    $controller = new PetugasController();
    $controller->detailComplaint($id);
});

$router->post('/petugas/complaints/update-status', function() {
    $controller = new PetugasController();
    $controller->updateStatus();
});

$router->post('/petugas/complaints/add-note', function() {
    $controller = new PetugasController();
    $controller->addNote();
});

// ==================== ADMIN ROUTES ====================
$router->get('/admin/dashboard', function() {
    $controller = new AdminController();
    $controller->dashboard();
});

// Units
$router->get('/admin/units', function() {
    $controller = new AdminController();
    $controller->listUnits();
});

$router->post('/admin/units/create', function() {
    $controller = new AdminController();
    $controller->createUnit();
});

$router->post('/admin/units/update/:id', function($id) {
    $controller = new AdminController();
    $controller->updateUnit($id);
});

$router->post('/admin/units/delete/:id', function($id) {
    $controller = new AdminController();
    $controller->deleteUnit($id);
});

// Categories
$router->get('/admin/categories', function() {
    $controller = new AdminController();
    $controller->listCategories();
});

$router->post('/admin/categories/create', function() {
    $controller = new AdminController();
    $controller->createCategory();
});

$router->post('/admin/categories/update/:id', function($id) {
    $controller = new AdminController();
    $controller->updateCategory($id);
});

$router->post('/admin/categories/delete/:id', function($id) {
    $controller = new AdminController();
    $controller->deleteCategory($id);
});

// Petugas
$router->get('/admin/petugas', function() {
    $controller = new AdminController();
    $controller->listPetugas();
});

$router->post('/admin/petugas/create', function() {
    $controller = new AdminController();
    $controller->createPetugas();
});

$router->post('/admin/petugas/update/:id', function($id) {
    $controller = new AdminController();
    $controller->updatePetugas($id);
});

$router->post('/admin/petugas/delete/:id', function($id) {
    $controller = new AdminController();
    $controller->deletePetugas($id);
});

// ==================== 404 HANDLER ====================
$router->notFound(function() {
    $controller = new PageController();
    $controller->notFound();
});

// Run the application
$router->run();
