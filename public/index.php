<?php
/**
 * Front controller - alle requests komen hier binnen.
 * 
 * Vereist mod_rewrite + .htaccess (zie public/.htaccess) zodat URL's als
 *   /dashboard, /login, /tasks
 * naar dit bestand worden gerouteerd.
 */

declare(strict_types=1);

use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\TaskController;
use App\Controllers\ProjectController;

// ---------------------------------------------------------------------------
// Autoloader (PSR-4 voor de namespace App\)
// ---------------------------------------------------------------------------
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/src/';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file     = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (is_readable($file)) {
        require $file;
    }
});

// ---------------------------------------------------------------------------
// Configuratie & helpers
// ---------------------------------------------------------------------------
require dirname(__DIR__) . '/config/database.php'; // laadt .env + helpers

$debug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

if (!function_exists('base_url')) {
    /**
     * Geeft een URL terug die rekening houdt met de submap waarin het project
     * onder WAMP draait, bijv. /THBO-dashboards/public.
     */
    function base_url(string $path = '/'): string
    {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $path      = '/' . ltrim($path, '/');
        return $scriptDir . ($path === '/' ? '' : $path);
    }
}

// ---------------------------------------------------------------------------
// Sessie
// ---------------------------------------------------------------------------
$sessionName = env('SESSION_NAME', 'thbo_session');
session_name($sessionName);
session_start();

// ---------------------------------------------------------------------------
// Routing
// ---------------------------------------------------------------------------
$router = new Router();
$auth     = new AuthController();
$tasks    = new TaskController();
$projects = new ProjectController();

$router->get ('/',          fn() => header('Location: ' . base_url(empty($_SESSION['user_id']) ? '/login' : '/dashboard')));

$router->get ('/login',     fn() => $auth->showLogin());
$router->post('/login',     fn() => $auth->login());
$router->get ('/register',  fn() => $auth->showRegister());
$router->post('/register',  fn() => $auth->register());
$router->get ('/logout',    fn() => $auth->logout());

$router->get ('/dashboard',          fn() => $tasks->dashboard());
$router->post('/tasks',              fn() => $tasks->store());
$router->post('/tasks/update',       fn() => $tasks->update());
$router->post('/tasks/status/',      fn() => $tasks->updateStatus());
$router->post('/tasks/delete',       fn() => $tasks->delete());

$router->post('/projects',           fn() => $projects->store());
$router->post('/projects/update',    fn() => $projects->update());
$router->post('/projects/delete',    fn() => $projects->delete());

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI']    ?? '/'
);