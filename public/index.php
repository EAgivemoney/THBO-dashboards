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
use App\Core\Logger;
use App\Controllers\AuthController;
use App\Controllers\TaskController;
use App\Controllers\ProjectController;
use App\Controllers\AdminController;
use App\Controllers\ProfielController;

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

// ---------------------------------------------------------------------------
// Foutafhandeling + logging naar storage/logs/
// ---------------------------------------------------------------------------

// PHP warnings/notices loggen. 'return false' laat PHP's eigen weergave
// (display_errors) gewoon doorlopen.
set_error_handler(function (int $severity, string $message, string $file, int $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }

    $level = match ($severity) {
        E_WARNING, E_USER_WARNING, E_CORE_WARNING, E_COMPILE_WARNING => 'warning',
        default => 'info', // notices, deprecations
    };

    $short = str_replace('\\', '/', $file);
    $root  = str_replace('\\', '/', dirname(__DIR__)) . '/';
    if (str_starts_with($short, $root)) {
        $short = substr($short, strlen($root));
    }

    Logger::$level('PHP: ' . $message, ['file' => $short, 'line' => $line]);
    return false;
});

// Niet-afgevangen excepties: loggen en een nette foutpagina tonen.
set_exception_handler(function (Throwable $e) use ($debug): void {
    Logger::exception($e);

    $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_contains($_SERVER['CONTENT_HYPE'] ?? '', 'application/json');

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-type: ' . ($wantsJson ? 'application/json' : 'text/html') . '; charset=utf-8');
    }

    if ($wantsJson) {
        echo json_encode(['ok' => false, 'error' => 'Interne serverfout']);
        return;
    }

    echo '<h1>Er is iets misgegaan</h1>';
    if ($debug) {
        echo '<p><strong>' . htmlspecialchars(get_class($e)) . ':</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>' . htmlspecialchars($e->getFile()) . ':' . (int) $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<p>Probeer het later opnieuw. De fout is vastgeled in het logbestand.</p>';
    }
});

// Fatale fouter (bijv. memory limit) komen niet langs de exception handler.
register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        Logger::error('PHP fatal: ' . $err['message'], ['file' => $err['file'], 'line' => $err['line']]);
    }
});

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

// Als de sessie verwijst naar een gebruiker die niet (meer) bestaat of inactief is
// (bijv. na het opnieuw importeren van sql/database.sql), de sessie opruimen zodat
// de gebruiker opnieuw moet inloggen in plaats van tegen een FK-fout aan te lopen.
if (!empty($_SESSION['user_id'])) {
    $sessieGebruiker = \App\Models\User::findById((int) $_SESSION['user_id']);
    if (!$sessieGebruiker || !$sessieGebruiker['IsActief']) {
        Logger::warning('Sessie opgeruimd: gebruiker bestaat niet of is inactief', ['user_id' => (int) $_SESSION['user_id']]);
        $_SESSION = [];
        session_regenerate_id(true);
    }
}

// ---------------------------------------------------------------------------
// Routing
// ---------------------------------------------------------------------------
$router   = new Router();
$auth     = new AuthController();
$tasks    = new TaskController();
$projects = new ProjectController();
$admin    = new AdminController();
$profiel  = new ProfielController();

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
$router->post('/projects/delete',          fn() => $projects->delete());

$router->get ('/admin',                    fn() => $admin->index());
$router->post('/admin/gebruiker/rol',      fn() => $admin->updateRol());
$router->post('/admin/gebruiker/toggle',   fn() => $admin->toggleActief());

$router->get ('/profiel',                  fn() => $profiel->show());
$router->post('/profiel/wachtwoord',       fn() => $profiel->updatePassword());

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI']    ?? '/'
);