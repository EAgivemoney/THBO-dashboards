<?php
/**
 * Front controller - every request enters here.
 *
 * Requires mod_rewrite + .htaccess (see public/.htaccess) so that URLs like
 *   /dashboard, /login, /tasks
 * are routed to this file.
 */

declare(strict_types=1);

use App\Core\Router;
use App\Core\Logger;
use App\Core\Lang;
use App\Controllers\AuthController;
use App\Controllers\TaskController;
use App\Controllers\ProjectController;
use App\Controllers\AdminController;
use App\Controllers\ProfileController;
use App\Controllers\LanguageController;

// ---------------------------------------------------------------------------
// Autoloader (PSR-4 for the App\ namespace)
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
// Configuration & helpers
// ---------------------------------------------------------------------------
require dirname(__DIR__) . '/config/database.php'; // loads .env + helpers

$debug = filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN);
if ($debug) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
}

// ---------------------------------------------------------------------------
// Error handling + logging to storage/logs/
// ---------------------------------------------------------------------------

// Log PHP warnings/notices. 'return false' lets PHP's own handling
// (display_errors) carry on as usual.
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

// Uncaught exceptions: log them and show a friendly error page.
set_exception_handler(function (Throwable $e) use ($debug): void {
    Logger::exception($e);

    $wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
        || str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: ' . ($wantsJson ? 'application/json' : 'text/html') . '; charset=utf-8');
    }

    if ($wantsJson) {
        echo json_encode(['ok' => false, 'error' => Lang::get('errors.server_json')]);
        return;
    }

    echo '<h1>' . htmlspecialchars(Lang::get('errors.server_title')) . '</h1>';
    if ($debug) {
        echo '<p><strong>' . htmlspecialchars(get_class($e)) . ':</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
        echo '<p>' . htmlspecialchars($e->getFile()) . ':' . (int) $e->getLine() . '</p>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    } else {
        echo '<p>' . htmlspecialchars(Lang::get('errors.server_text')) . '</p>';
    }
});

// Fatal errors (e.g. memory limit) never reach the exception handler.
register_shutdown_function(function (): void {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        Logger::error('PHP fatal: ' . $err['message'], ['file' => $err['file'], 'line' => $err['line']]);
    }
});

if (!function_exists('base_url')) {
    /**
     * Returns a URL that accounts for the subfolder the project runs in
     * under WAMP, e.g. /THBO-dashboards/public.
     */
    function base_url(string $path = '/'): string
    {
        $scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
        $path      = '/' . ltrim($path, '/');
        return $scriptDir . ($path === '/' ? '' : $path);
    }
}

// ---------------------------------------------------------------------------
// Translation helpers for the views (texts come from resources/lang/<code>.php)
// ---------------------------------------------------------------------------

if (!function_exists('t')) {
    /**
     * Translated text, ready for HTML output (escaped).
     *   <?= t('ui.dashboard.title') ?>
     *   <?= t('ui.layout.hi', ['name' => $name]) ?>
     */
    function t(string $key, array $replace = []): string
    {
        return htmlspecialchars(Lang::get($key, $replace), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('t_confirm')) {
    /**
     * Value for an onsubmit attribute that asks for confirmation:
     *   <form onsubmit="<?= t_confirm('ui.dashboard.confirm_task_delete') ?>">
     * The text is wrapped safely as a JavaScript string, even with apostrophes or quotes.
     */
    function t_confirm(string $key, array $replace = []): string
    {
        $message = json_encode(
            Lang::get($key, $replace),
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        );

        return htmlspecialchars('return confirm(' . $message . ');', ENT_QUOTES, 'UTF-8');
    }
}

// ---------------------------------------------------------------------------
// Session
// ---------------------------------------------------------------------------
$sessionName = env('SESSION_NAME', 'thbo_session');
session_name($sessionName);
session_start();

// If the session points to a user that no longer exists or is inactive (e.g.
// after re-importing sql/database.sql), clear it so the user has to log in
// again instead of running into a foreign key error. Otherwise keep the name
// and role in the session in sync with the database, so role changes apply
// immediately without a new login.
if (!empty($_SESSION['user_id'])) {
    $sessionUser = \App\Models\User::findById((int) $_SESSION['user_id']);
    if (!$sessionUser || !$sessionUser['is_active']) {
        Logger::warning('Session cleared: user does not exist or is inactive', ['user_id' => (int) $_SESSION['user_id']]);
        $_SESSION = [];
        session_regenerate_id(true);
    } else {
        $_SESSION['user_name'] = $sessionUser['full_name'];
        $_SESSION['user_role'] = (int) $sessionUser['role_id'];
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
$profile  = new ProfileController();
$language = new LanguageController();

$router->get ('/',          fn() => header('Location: ' . base_url(empty($_SESSION['user_id']) ? '/login' : '/dashboard')));

$router->get ('/login',     fn() => $auth->showLogin());
$router->post('/login',     fn() => $auth->login());
$router->get ('/register',  fn() => $auth->showRegister());
$router->post('/register',  fn() => $auth->register());
$router->get ('/logout',    fn() => $auth->logout());

$router->get ('/dashboard',       fn() => $tasks->dashboard());
$router->post('/tasks',           fn() => $tasks->store());
$router->post('/tasks/update',    fn() => $tasks->update());
$router->post('/tasks/status',    fn() => $tasks->updateStatus());
$router->post('/tasks/delete',    fn() => $tasks->delete());

$router->post('/projects',        fn() => $projects->store());
$router->post('/projects/update', fn() => $projects->update());
$router->post('/projects/delete', fn() => $projects->delete());

$router->get ('/admin',              fn() => $admin->index());
$router->get ('/admin/logs',         fn() => $admin->logs());
$router->post('/admin/demo',         fn() => $admin->loadDemo());
$router->post('/admin/user/role',    fn() => $admin->updateRole());
$router->post('/admin/user/toggle',  fn() => $admin->toggleActive());

$router->post('/language',           fn() => $language->switch());

$router->get ('/profile',            fn() => $profile->show());
$router->post('/profile/password',   fn() => $profile->updatePassword());

$router->dispatch(
    $_SERVER['REQUEST_METHOD'] ?? 'GET',
    $_SERVER['REQUEST_URI']    ?? '/'
);
