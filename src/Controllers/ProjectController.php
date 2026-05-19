<?php
namespace App\Controllers;

use App\Models\Project;

class ProjectController
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
     * POST /projects — nieuw project aanmaken.
     */
    public function store(): void
    {
        $userId       = $this->requireLogin();
        $titel        = trim($_POST['titel']        ?? '');
        $beschrijving = trim($_POST['beschrijving'] ?? '');

        $errors = [];
        if ($titel === '') {
            $errors[] = 'Projectnaam is verplicht.';
        } elseif (mb_strlen($titel) > 120) {
            $errors[] = 'Projectnaam mag maximaal 120 tekens bevatten.';
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
        } else {
            try {
                Project::create($userId, $titel, $beschrijving ?: null);
                $_SESSION['flash'] = 'Project aangemaakt.';
            } catch (\Exception $e) {
                // SP_ProjectAanmaken gooit een fout bij een dubbele naam
                $_SESSION['errors'] = ['Er bestaat al een project met die naam.'];
            }
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }

    /**
     * POST /projects/update — bestaand project bijwerken.
     */
    public function update(): void
    {
        $userId       = $this->requireLogin();
        $projectId    = (int)  ($_POST['project_id']   ?? 0);
        $titel        = trim(   $_POST['titel']         ?? '');
        $beschrijving = trim(   $_POST['beschrijving']  ?? '');

        $errors = [];
        if ($projectId <= 0)   { $errors[] = 'Ongeldig project.'; }
        if ($titel === '')     { $errors[] = 'Projectnaam is verplicht.'; }

        if ($errors) {
            $_SESSION['errors'] = $errors;
        } else {
            try {
                $ok = Project::update($projectId, $titel, $beschrijving ?: null, $userId);
                $_SESSION['flash'] = $ok ? 'Project bijgewerkt.' : 'Project niet gevonden.';
            } catch (\Exception $e) {
                $_SESSION['errors'] = ['Er bestaat al een project met die naam.'];
            }
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }

    /**
     * POST /projects/delete — project soft-deleten.
     */
    public function delete(): void
    {
        $userId    = $this->requireLogin();
        $projectId = (int) ($_POST['project_id'] ?? 0);

        if ($projectId > 0 && Project::softDelete($projectId, $userId)) {
            $_SESSION['flash'] = 'Project verwijderd.';
        } else {
            $_SESSION['errors'] = ['Kon het project niet verwijderen.'];
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }
}