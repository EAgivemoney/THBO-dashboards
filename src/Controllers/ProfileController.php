<?php
namespace App\Controllers;

use App\Core\Activity;
use App\Core\Lang;
use App\Models\User;

/**
 * Profile controller - shows the account details and changes the password.
 */
class ProfileController
{
    private function requireLogin(): int
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . base_url('/login'));
            exit;
        }
        return (int) $_SESSION['user_id'];
    }

    public function show(): void
    {
        $userId = $this->requireLogin();
        $title  = Lang::get('ui.profile.page_title');
        $user   = User::findById($userId);
        $flash  = $_SESSION['flash'] ?? null;
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['flash'], $_SESSION['errors']);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/profile/index.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }

    public function updatePassword(): void
    {
        $userId               = $this->requireLogin();
        $currentPassword      = $_POST['current_password']      ?? '';
        $newPassword          = $_POST['new_password']          ?? '';
        $passwordConfirmation = $_POST['password_confirmation'] ?? '';

        $errors = [];
        if ($currentPassword === '')                { $errors[] = Lang::get('profile.current_required'); }
        if (strlen($newPassword) < 8)               { $errors[] = Lang::get('profile.new_min'); }
        if ($newPassword !== $passwordConfirmation) { $errors[] = Lang::get('profile.mismatch'); }
        if ($newPassword === $currentPassword)      { $errors[] = Lang::get('profile.same_as_old'); }

        if (!$errors) {
            if (User::updatePAssword($userId, $currentPassword, $newPassword)) {
                Activity::log('profile.password');
                $_SESSION['flash'] = Lang::get('profile.changed');
            } else {
                $errors[] = Lang::get('profile.current_wrong');
            }
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
        }

        header('Location: ' . base_url('/profile'));
        exit;
    }
}