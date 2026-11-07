<?php
namespace App\Controllers;

use App\Core\Activity;
use App\Core\Lang;
use App\Core\LogReader;
use App\Core\Logger;
use App\Core\Seeder;
use App\Models\Admin;

class AdminController
{
    /**
     * Checks that the logged-in user is an admin or owner.
     * Responds with a 403 page if not.
     */
    private function requireAdmin(): int
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . base_url('/login'));
            exit;
        }

        $role = (int) ($_SESSION['user_role'] ?? 0);
        if (!in_array($role, [Admin::ROLE_ADMIN, Admin::ROLE_OWNER], true)) {
            http_response_code(403);
            require dirname(__DIR__) . '/Views/layout/header.php';
            require dirname(__DIR__) . '/Views/admin/forbidden.php';
            require dirname(__DIR__) . '/Views/layout/footer.php';
            exit;
        }

        return (int) $_SESSION['user_id'];
    }

    /**
     * Checks that the logged-in user is the owner.
     * Redirects to the admin panel with an error message if not.
     */
    private function requireOwner(): void
    {
        $this->requireAdmin();

        if ((int) ($_SESSION['user_role'] ?? 0) !== Admin::ROLE_OWNER) {
            $_SESSION['errors'] = [Lang::get('admin.owner_only')];
            header('Location: ' . base_url('/admin'));
            exit;
        }
    }

    /**
     * GET /admin — admin panel.
     */
    public function index(): void
    {
        $this->requireAdmin();

        $title    = Lang::get('ui.admin.page_title');
        $role     = (int) ($_SESSION['user_role'] ?? 0);
        $stats    = Admin::systemStats();
        $users    = Admin::allUsers();
        $projects = Admin::allProjects();
        $flash    = $_SESSION['flash']  ?? null;
        $errors   = $_SESSION['errors'] ?? [];
        unset($_SESSION['flash'], $_SESSION['errors']);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/admin/index.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }

    /**
     * GET /admin/logs — view the activity log and the error log (read only).
     *
     * Query: type=activity|app, date=Y-m-d, f=<level|category>, q=<search text>, page=<n>
     */
    public function logs(): void
    {
        $this->requireAdmin();

        $title  = Lang::get('logs.title');
        $type   = (string) ($_GET['type'] ?? 'activity');
        if (!isset(LogReader::types()[$type])) {
            $type = 'activity';
        }

        // The date has to match an existing log file (no free-form file name).
        $dates  = LogReader::dates($type);
        $date   = (string) ($_GET['date'] ?? '');
        if (!in_array($date, $dates, true)) {
            $date = $dates[0] ?? '';
        }

        $filter = (string) ($_GET['f'] ?? '');
        $valid  = $type === 'activity' ? Activity::categories() : LogReader::levels();
        if (!isset($valid[$filter])) {
            $filter = '';
        }

        $query  = trim(substr((string) ($_GET['q'] ?? ''), 0, 100));
        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $result = $date !== '' ? LogReader::read($type, $date, $filter, $query, $page) : null;

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/admin/logs.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }

    /**
     * POST /admin/demo — load sample projects and tasks (owner only).
     * The data lives in resources/seed/demo.<code>.json and goes into the owner's account.
     */
    public function loadDemo(): void
    {
        $this->requireOwner();
        $userId = (int) $_SESSION['user_id'];

        try {
            $stats = Seeder::loadDemo($userId);
            Activity::log('admin.demo_loaded', $stats);
            $_SESSION['flash'] = Lang::get('admin.demo_loaded', $stats);
        } catch (\Throwable $e) {
            Logger::exception($e, 'Failed to load demo data');
            $_SESSION['errors'] = [Lang::get('admin.demo_failed')];
        }

        header('Location: ' . base_url('/admin'));
        exit;
    }

    /**
     * POST /admin/user/role — change a role (owner only).
     */
    public function updateRole(): void
    {
        $this->requireOwner();

        $targetUserId = (int) ($_POST['user_id'] ?? 0);
        $newRoleId    = (int) ($_POST['role_id'] ?? 0);

        if ($targetUserId > 0 && Admin::updateRole($targetUserId, $newRoleId)) {
            Activity::log('admin.role_changed', [
                'target_user_id' => $targetUserId,
                'new_role'       => strtolower(Admin::roleName($newRoleId)),
            ]);
            $_SESSION['flash'] = Lang::get('admin.role_updated');
        } else {
            $_SESSION['errors'] = [Lang::get('admin.role_failed')];
        }

        header('Location: ' . base_url('/admin'));
        exit;
    }

    /**
     * POST /admin/user/toggle — activate or deactivate a user (owner only).
     */
    public function toggleActive(): void
    {
        $this->requireOwner();

        $targetUserId = (int)  ($_POST['user_id'] ?? 0);
        $active       = (bool) ($_POST['active']  ?? false);

        // Prevent the owner from deactivating themselves
        if ($targetUserId === (int) $_SESSION['user_id']) {
            $_SESSION['errors'] = [Lang::get('admin.no_self_deactivate')];
            header('Location: ' . base_url('/admin'));
            exit;
        }

        if ($targetUserId > 0 && Admin::toggleActive($targetUserId, $active)) {
            Activity::log('admin.user_toggled', [
                'target_user_id' => $targetUserId,
                'active'         => $active,
            ]);
            $_SESSION['flash'] = $active ? Lang::get('admin.user_activated') : Lang::get('admin.user_deactivated');
        } else {
            $_SESSION['errors'] = [Lang::get('admin.status_failed')];
        }

        header('Location: ' . base_url('/admin'));
        exit;
    }
}
