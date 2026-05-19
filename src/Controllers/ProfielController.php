<?php
namespace App\Controllers;

use App\Models\User;

class ProfielController
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
        $title  = 'Mijn profiel';
        $user   = User::findById($userId);
        $flash  = $_SESSION['flash']  ?? null;
        $errors = $_SESSION['errors'] ?? [];
        unset($_SESSION['flash'], $_SESSION['errors']);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/profiel/index.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }

    public function updatePassword(): void
    {
        $userId     = $this->requireLogin();
        $huidig     = $_POST['huidig_wachtwoord'] ?? '';
        $nieuw      = $_POST['nieuw_wachtwoord']  ?? '';
        $bevestiging = $_POST['bevestiging']       ?? '';

        $errors = [];
        if ($huidig === '')              { $errors[] = 'Huidig wachtwoord is verplicht.'; }
        if (strlen($nieuw) < 8)         { $errors[] = 'Nieuw wachtwoord moet minimaal 8 tekens zijn.'; }
        if ($nieuw !== $bevestiging)    { $errors[] = 'De nieuwe wachtwoorden komen niet overeen.'; }
        if ($nieuw === $huidig)         { $errors[] = 'Het nieuwe wachtwoord moet anders zijn dan het huidige.'; }

        if (!$errors) {
            if (User::updatePassword($userId, $huidig, $nieuw)) {
                $_SESSION['flash'] = 'Wachtwoord succesvol gewijzigd.';
            } else {
                $errors[] = 'Huidig wachtwoord is onjuist.';
            }
        }

        if ($errors) {
            $_SESSION['errors'] = $errors;
        }

        header('Location: ' . base_url('/profiel'));
        exit;
    }
}