<?php
namespace App\Controllers;

use App\Core\Activity;
use App\Core\Lang;
use App\Models\Project;
use App\Models\Task;
use PDOException;

class TaskController
{
    private function requireLogin(): int
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . base_url('/login'));
            exit;
        }
        return (int) $_SESSION['user_id'];
    }

    /**
     * Does the project belong to this user?
     */
    private function ownsProject(int $userId, int $projectId): bool
    {
        foreach (Task::projectsForUser($userId) as $project) {
            if ((int) $project['id'] === $projectId) {
                return true;
            }
        }
        return false;
    }

    public function dashboard(): void
    {
        $userId = $this->requireLogin();

        // Make sure there is at least one project - otherwise the board is empty
        // and on the very first login there is nothing a task can be linked to.
        Task::ensureDefaultProject($userId);

        $title    = Lang::get('ui.dashboard.page_title');
        $board    = Task::boardForUser($userId);
        $projects = Task::projectsForUser($userId);

        // Extended project list with task counts (for the project management modal)
        $projectDetails = Project::forUser($userId);
        $flash    = $_SESSION['flash']  ?? null;
        $errors   = $_SESSION['errors'] ?? [];
        unset($_SESSION['flash'], $_SESSION['errors']);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/dashboard/index.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }

    public function store(): void
    {
        $userId = $this->requireLogin();

        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');
        $projectId   = (int) ($_POST['project_id'] ?? 0);
        $priority    = max(1, min(5, (int) ($_POST['priority'] ?? 3)));
        $deadline    = trim($_POST['deadline']    ?? '');

        $errors = [];
        if ($title === '')     { $errors[] = Lang::get('task.title_required'); }
        if ($projectId === 0)  { $errors[] = Lang::get('task.choose_project'); }

        // Verify that the project belongs to this user.
        if (!$errors && !$this->ownsProject($userId, $projectId)) {
            $errors[] = Lang::get('project.invalid');
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
        } else {
            try {
                $taskId = Task::create($projectId, $title, $description, $priority, $deadline);
                Activity::log('task.created', [
                    'task_id'    => $taskId,
                    'title'      => Activity::short($title),
                    'project_id' => $projectId,
                ]);
                $_SESSION['flash'] = Lang::get('task.created');
            } catch (PDOException $e) {
                if (!Task::isDuplicate($e)) {
                    throw $e;
                }
                $_SESSION['errors'] = [Lang::get('task.exists')];
            }
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }

    /**
     * AJAX endpoint — moves a task to another column.
     * Expects JSON: { "task_id": 1, "status_id": 2 }
     */
    public function updateStatus(): void
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => Lang::get('errors.not_logged_in')]);
            return;
        }

        $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
        $taskId  = (int) ($payload['task_id']   ?? 0);
        $status  = (int) ($payload['status_id'] ?? 0);

        if ($taskId <= 0 || $status <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => Lang::get('errors.invalid_input')]);
            return;
        }

        $ok = Task::updateStatus($taskId, $status, (int) $_SESSION['user_id']);
        if ($ok) {
            Activity::log('task.moved', [
                'task_id' => $taskId,
                'to'      => match ($status) {
                    Task::STATUS_TODO  => 'todo',
                    Task::STATUS_DOING => 'doing',
                    Task::STATUS_DONE  => 'done',
                    default            => (string) $status,
                },
            ]);
        }
        echo json_encode(['ok' => $ok]);
    }

    public function update(): void
    {
        $userId = $this->requireLogin();

        $taskId      = (int) ($_POST['task_id']    ?? 0);
        $title       = trim($_POST['title']        ?? '');
        $description = trim($_POST['description']  ?? '');
        $projectId   = (int) ($_POST['project_id'] ?? 0);
        $priority    = max(1, min(5, (int) ($_POST['priority'] ?? 3)));
        $deadline    = trim($_POST['deadline']     ?? '');

        $errors = [];
        if ($taskId <= 0)     { $errors[] = Lang::get('task.invalid'); }
        if ($title === '')    { $errors[] = Lang::get('task.title_required'); }
        if ($projectId === 0) { $errors[] = Lang::get('task.choose_project'); }

        if (!$errors && !$this->ownsProject($userId, $projectId)) {
            $errors[] = Lang::get('project.invalid');
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
        } else {
            try {
                Task::update($taskId, $projectId, $title, $description ?: null, $priority, $deadline ?: null, $userId);
                Activity::log('task.updated', [
                    'task_id'    => $taskId,
                    'title'      => Activity::short($title),
                    'project_id' => $projectId,
                ]);
                $_SESSION['flash'] = Lang::get('task.updated');
            } catch (PDOException $e) {
                if (!Task::isDuplicate($e)) {
                    throw $e;
                }
                $_SESSION['errors'] = [Lang::get('task.exists')];
            }
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }

    public function delete(): void
    {
        $userId = $this->requireLogin();
        $taskId = (int) ($_POST['task_id'] ?? 0);

        if ($taskId > 0 && Task::softDelete($taskId, $userId)) {
            Activity::log('task.deleted', ['task_id' => $taskId]);
            $_SESSION['flash'] = Lang::get('task.deleted');
        } else {
            $_SESSION['errors'] = [Lang::get('task.delete_failed')];
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }
}
