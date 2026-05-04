<?php
namespace App\Controllers;

use App\Models\User;

/**
 * Auth controller - login, register, logout.
 */
class AuthController
{
    public function showLogin(): void
    {
        $title  = 'Inloggen';
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/auth/login.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }

    public function login(): void
    {
        $email      = trim($_POST['email'] ?? '');
        $wachtwoord = $_POST['wachtwoord'] ?? '';

        $errors = [];
        if ($email === '')      { $errors[] = 'E-mailadres is verplicht.'; }
        if ($wachtwoord === '') { $errors[] = 'Wachtwoord is verplicht.'; }

        if (!$errors) {
            $user = User::findByEmail($email);
            if (!$user || !$user['IsActief']) {
                $errors[] = 'Onbekende of inactieve gebruiker.';
            } elseif (!User::verifyPassword($wachtwoord, $user['WachtwoordHash'])) {
                $errors[] = 'Wachtwoord is onjuist.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int) $user['Id'];
                $_SESSION['user_naam'] = $user['VolledigeNaam'];
                $_SESSION['user_rol']  = (int) $user['RolId'];

                header('Location: ' . base_url('/dashboard'));
                exit;
            }
        }

        $_SESSION['errors'] = $errors;
        $_SESSION['old']    = ['email' => $email];
        header('Location: ' . base_url('/login'));
        exit;
    }

    public function showRegister(): void
    {
        $title  = 'Registreren';
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSIONS['old']   ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/auth/register.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }

    public function register(): void
    {
        $voornaam      = trim($_POST['voornaam']          ?? '');
        $tussenvoegsel = trim($_POST['tussenvoegsel']     ?? '');
        $achternaam    = trim($_POST['achternaam']        ?? '');
        $email         = trim($_POST['email']             ?? '');
        $wachtwoord    = $_POST['wachtwoord']             ?? '';
        $bevestiging   = $_POST['wachtwoord_bevestiging'] ?? '';

        $errors = [];
        if ($voornaam === '')                       { $errors[] = 'Voornaam is verplicht.'; }
        if ($achternaam === '')                     { $errors[] = 'Achternaam is verplicht.'; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Vul een geldig e-mailadres in.';
        }
        if (strlen($wachtwoord) < 8)                { $errors[] = 'Wachtwoord moet minimaal 8 tekens zijn.';}
        if ($wachtwoord !== $bevestiging)           { $errors[] = 'De wachtwoorden komen niet overeen.'; }

        if (!$errors & User::findByEmail($email)) {
            $errors[] = 'Er bestaat al een account met dit e-mailadres.';
        }

        if (!$errors) {
            User::create([
                'voornaam'      => $voornaam,
                'tussenvoegsel' => $tussenvoegsel,
                'achternaam'    => $achternaam,
                'email'         => $email,
                'wachtwoord'    => $wachtwoord,
            ]);

            $_SESSION['flash'] = 'Account aangemaakt — je kunt nu inloggen.';
            header('Location: ' . base_url('/login'));
            exit;
        }

        $_SESSION['errors'] = $errors;
        $_SESSION['old']    = compact('voornaam', 'tussenvoegsel', 'achternaam', 'email');
        header('Location: ' . base_url('/register'));
        exit;
    }

    public function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }
        session_destroy();
        header('Location: ' . base_url('/login'));
        exit;
    }
}