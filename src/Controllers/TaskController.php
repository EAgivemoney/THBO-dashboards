<?php
namespace App\Controllers;

use App\Models\Task;

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

    public function dashboard(): void
    {
        $userId = $this->requireLogin();

        // Zorg dat er minimaal één project is - anders is het bord leeg
        // Bij de allereerste login en kun je nergens een taak aan koppelen
        Task::ensureDefaultProject($userId);

        $title    = 'Dashboard';
        $board    = Task::boardForUser($userId);
        $projects = Task::projectsForUser($userId);
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

        $titel        = trim($_POST['titel']        ?? '');
        $beschrijving = trim($_POST['beschrijving'] ?? '');
        $projectId    = (int) ($_POST['project_id'] ?? 0);
        $prioriteit   = max(1, min(5, (int) ($_POST['prioriteit'] ?? 3)));
        $deadline     = trim($_POST['deadline']     ?? '');

        $errors = [];
        if ($titel === '')     { $errors[] = 'Titel is verplicht.'; }
        if ($projectId === 0)  { $errors[] = 'Kies een project.'; }

        // Verifieer dat het project van deze gebruiker is.
        if (!$errors) {
            $owns = false;
            foreach (Task::projectsForUser($userId) as $p) {
                if ((int) $p['Id'] === $projectId) {
                    $owns = true;
                    break;
                }
            }
            if (!$owns) {
                $errors[] = 'Ongeldig project.';
            }
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
        } else {
            Task::create($projectId, $titel, $beschrijving, $prioriteit, $deadline);
            $_SESSION['flash'] = 'Taak toegevoegd.';
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }

    /**
     * AJAX endpoint — verplaatst een taak naar een andere kolom.
     * Verwacht JSON: { "task_id": 1, "status_id": 2 }
     */
    public function updateStatus(): void
    {
        header('Content-Type: application/json');

        if (empty($_SESSION['user_id'])) {
            http_response_code(401);
            echo json_encode(['ok' => false, 'error' => 'Niet ingelogd']);
            return;
        }

        $payload = json_decode(file_get_contents('php://input') ?: '[]', true);
        $taskId  = (int) ($payload['task_id']   ?? 0);
        $status  = (int) ($payload['status_id'] ?? 0);

        if ($taskId <= 0 || $status <= 0) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Ongeldige invoer']);
            return;
        }

        $ok = Task::updateStatus($taskId, $status, (int) $_SESSION['user_id']);
        echo json_encode(['ok' => $ok]);
    }


    public function update(): void
    {
        $userId = $this->requireLogin();

        $taskId       = (int)   ($_POST['task_id']       ?? 0);
        $titel        = trim(   $_POST['titel']          ?? '');
        $beschrijving = trim(   $_POST['beschrijving']   ?? '');
        $projectId    = (int)   ($_POST['project_id']    ?? 0);
        $prioriteit   = max(1, min(5, (int) ($_POST['prioriteit'] ?? 3)));
        $deadline     = trim(   $_POST['deadline']       ?? '');

        $errors = [];
        if ($taskId <= 0)     { $errors[] = 'Ongeldige taak.'; }
        if ($titel === '')    { $errors[] = 'Titel is verplicht.'; }
        if ($projectId === 0) { $errors[] = 'Kies een project.'; }

        if (!$errors) {
            $owns = false;
            foreach (Task::projectsForUser($userId) as $p) {
                if ((int) $p['Id'] === $projectId) { $owns = true; break; }
            }
            if (!$owns) { $errors[] = 'Ongeldig project.'; }
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
        } else {
            Task::update($taskId, $projectId, $titel, $beschrijving ?: null, $prioriteit, $deadline ?: null, $userId);
            $_SESSION['flash'] = 'Taak bijgewerkt.';
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }

    public function delete(): void
    {
        $userId = $this->requireLogin();
        $taskId = (int) ($_POST['task_id'] ?? 0);

        if ($taskId > 0 && Task::softDelete($taskId, $userId)) {
            $_SESSION['flash'] = 'Taak verwijdered.';
        } else {
            $_SESSION['errors'] = ['Kon de taak niet verwijderen.'];
        }

        header('Location: ' . base_url('/dashboard'));
    }
}