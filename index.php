<?php

require __DIR__ . '/src/config/environment.php';
load_environment(__DIR__ . '/.env');

$isProduction = (getenv('APP_ENV') ?: 'production') === 'production';
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_secure', $isProduction ? '1' : '0');
ini_set('session.cookie_samesite', getenv('SESSION_COOKIE_SAMESITE') ?: 'Lax');
session_name(getenv('SESSION_COOKIE_NAME') ?: 'owebku_session');
session_start();

spl_autoload_register(function ($class) {
    // Prefix base direktori
    $base_dir = __DIR__ . '/src/';

    // Core namespace
    if (str_starts_with($class, 'Core\\')) {
        $file = $base_dir . 'core/' . substr($class, 5) . '.php';
        if (file_exists($file)) {
            require $file;
        }
        return;
    }

    // Module namespace (e.g. Modules\Auth\AuthController)
    if (str_starts_with($class, 'Modules\\')) {
        $parts = explode('\\', $class);
        array_shift($parts); // hapus 'Modules'
        $module = strtolower(array_shift($parts)); // ambil nama module
        
        $file = $base_dir . 'modules/' . $module . '/' . implode('/', $parts) . '.php';
        if (file_exists($file)) {
            require $file;
        }
    }
});

// Load helpers
require __DIR__ . '/src/helpers/utils.php';

use Core\Router;
use Modules\Auth\AuthController;
use Modules\Dashboard\DashboardController;
use Modules\Projects\ProjectsController;
use Modules\Files\FilesController;
use Modules\Publish\PublishController;

// Init Router
$router = new Router();

// Routes Auth
$router->get('/login', [AuthController::class, 'index']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'register']);
$router->post('/register', [AuthController::class, 'doRegister']);
$router->get('/profile', [AuthController::class, 'profile']);
$router->post('/profile/update-password', [AuthController::class, 'updatePassword']);
$router->post('/logout', [AuthController::class, 'logout']);

// Routes Dashboard
$router->get('/dashboard', [DashboardController::class, 'index']);
$router->get('/dashboard/activities', [DashboardController::class, 'activities']);
$router->get('/', [DashboardController::class, 'index']);

// Routes Projects CRUD
$router->post('/projects/create', [ProjectsController::class, 'create']);
$router->post('/projects/rename', [ProjectsController::class, 'rename']);
$router->post('/projects/delete', [ProjectsController::class, 'delete']);

// Routes Editor & Files
$router->get('/editor/{projectId}', [FilesController::class, 'editor']);
$router->post('/files/get-content', [FilesController::class, 'getContent']);
$router->post('/files/save-content', [FilesController::class, 'saveContent']);
$router->post('/files/create', [FilesController::class, 'createFile']);
$router->post('/files/rename', [FilesController::class, 'renameFile']);
$router->post('/files/move', [FilesController::class, 'moveFile']);
$router->post('/files/delete', [FilesController::class, 'deleteFile']);
$router->post('/files/upload', [FilesController::class, 'uploadFile']);
$router->post('/files/import-zip', [FilesController::class, 'importZip']);

// Routes Publish
$router->post('/publish', [PublishController::class, 'publish']);

// Dispatch
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$router->dispatch($_SERVER['REQUEST_METHOD'], $uri);
