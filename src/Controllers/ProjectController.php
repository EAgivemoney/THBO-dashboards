<?php
namespace App\Controllers;

use App\Core\Activity;
use App\Core\Lang;
use App\Core\Logger;
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
     * POST /projects — create a new project.
     */
    public function store(): void
    {
        $userId      = $this->requireLogin();
        $title       = trim($_POST['title']       ?? '');
        $description = trim($_POST['description'] ?? '');

        $errors = [];
        if ($title === '') {
            $errors[] = Lang::get('project.name_required');
        } elseif (mb_strlen($title) > 120) {
            $errors[] = Lang::get('project.name_max');
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
        } else {
            try {
                $newProjectId = Project::create($userId, $title, $description ?: null);
                Activity::log('project.created', ['project_id' => $newProjectId, 'title' => Activity::short($title)]);
                $_SESSION['flash'] = Lang::get('project.created');
            } catch (\Exception $e) {
                Logger::exception($e, 'Failed to create project', ['title' => $title]);
                // SP_ProjectAanmaken throws an error for a duplicate name
                $_SESSION['errors'] = [Lang::get('project.exists')];
            }
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }

    /**
     * POST /projects/update — update an existing project.
     */
    public function update(): void
    {
        $userId      = $this->requireLogin();
        $projectId   = (int) ($_POST['project_id'] ?? 0);
        $title       = trim($_POST['title']        ?? '');
        $description = trim($_POST['description']  ?? '');

        $errors = [];
        if ($projectId <= 0) { $errors[] = Lang::get('project.invalid'); }
        if ($title === '')   { $errors[] = Lang::get('project.name_required'); }

        if ($errors) {
            $_SESSION['errors'] = $errors;
        } else {
            try {
                $ok = Project::update($projectId, $title, $description ?: null, $userId);
                if ($ok) {
                    Activity::log('project.updated', ['project_id' => $projectId, 'title' => Activity::short($title)]);
                }
                $_SESSION['flash'] = $ok ? Lang::get('project.updated') : Lang::get('project.not_found');
            } catch (\Exception $e) {
                Logger::exception($e, 'Failed to update project', ['project_id' => $projectId]);
                $_SESSION['errors'] = [Lang::get('project.exists')];
            }
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }

    /**
     * POST /projects/delete — soft delete a project.
     * 
     * A user always keeps at least one project: the last remaining project
     * cannot be deleted until another one has been created.
     */
    public function delete(): void
    {
        $userId    = $this->requireLogin();
        $projectId = (int) ($_POST['project_id'] ?? 0);

        if (Project::countActiveForUser($userId) <= 1) {
            $_SESSION['errors'] = [Lang::get('project.last_one')];
            header('Location: ' . base_url('/dashboard'));
            exit;
        }

        if ($projectId > 0 && Project::softDelete($projectId, $userId)) {
            Activity::log('project.deleted', ['project_id' => $projectId]);
            $_SESSION['flash'] = Lang::get('project.deleted');
        } else {
            $_SESSION['errors'] = [Lang::get('project.delete_failed')];
        }

        header('Location: ' . base_url('/dashboard'));
        exit;
    }
}
