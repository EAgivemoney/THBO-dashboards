<?php
namespace App\Controllers;

use App\Core\Activity;
use App\Core\Lang;
use App\Models\User;

/**
 * Auth controller - login, register, logout.
 */
class AuthController
{
    public function showLogin(): void
    {
        $title  = Lang::get('ui.layout.login');
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/auth/login.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }

    public function login(): void
    {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        $errors = [];
        if ($email === '')    { $errors[] = Lang::get('auth.email_required'); }
        if ($password === '') { $errors[] = Lang::get('auth.password_required'); }

        if (!$errors) {
            $user = User::findByEmail($email);
            if (!$user || !$user['is_active']) {
                $errors[] = Lang::get('auth.unknown_user');
                Activity::log('auth.login_failed', [
                    'email'  => Activity::short($email, 120),
                    'reason' => $user ? 'inactive_user' : 'unknown_user',
                ]);
            } elseif (!User::verifyPassword($password, $user['password_hash'])) {
                $errors[] = Lang::get('auth.wrong_password');
                Activity::log('auth.login_failed', [
                    'email'  => Activity::short($email, 120),
                    'reason' => 'wrong_password',
                ], (int) $user['id'], $user['full_name']);
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = (int) $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = (int) $user['role_id'];

                Activity::log('auth.login');

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
        $title  = Lang::get('ui.register.title');
        $errors = $_SESSION['errors'] ?? [];
        $old    = $_SESSION['old']    ?? [];
        unset($_SESSION['errors'], $_SESSION['old']);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/auth/register.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }

    public function register(): void
    {
        $firstName            = trim($_POST['first_name']            ?? '');
        $namePrefix           = trim($_POST['name_prefix']           ?? '');
        $lastName             = trim($_POST['last_name']             ?? '');
        $email                = trim($_POST['email']                 ?? '');
        $password             = $_POST['password']                   ?? '';
        $passwordConfirmation = $_POST['password_confirmation']      ?? '';

        $errors = [];
        if ($firstName === '')                      { $errors[] = Lang::get('auth.firstname_required'); }
        if ($lastName === '')                       { $errors[] = Lang::get('auth.lastname_required'); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = Lang::get('auth.email_invalid');
        }
        if (strlen($password) < 8)                  { $errors[] = Lang::get('auth.password_min'); }
        if ($password !== $passwordConfirmation)    { $errors[] = Lang::get('auth.password_mismatch'); }

        if (!$errors && User::findByEmail($email)) {
            $errors[] = Lang::get('auth.email_taken');
        }

        if (!$errors) {
            $newUserId = User::create([
                'first_name'  => $firstName,
                'name_prefix' => $namePrefix,
                'last_name'   => $lastName,
                'email'       => $email,
                'password'    => $password,
            ]);

            Activity::log(
                'auth.register',
                ['email' => Activity::short($email, 120)],
                $newUserId,
                trim($firstName . ' ' . ($namePrefix ? $namePrefix . ' ' : '') . $lastName)
            );

            $_SESSION['flash'] = Lang::get('auth.registered');
            header('Location: ' . base_url('/login'));
            exit;
        }

        $_SESSION['errors'] = $errors;
        $_SESSION['old']    = [
            'first_name'  => $firstName,
            'name_prefix' => $namePrefix,
            'last_name'   => $lastName,
            'email'       => $email,
        ];
        header('Location: ' . base_url('/register'));
        exit;
    }

    public function logout(): void
    {
        if (!empty($_SESSION['user_id'])) {
            Activity::log('auth.logout');
        }

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
