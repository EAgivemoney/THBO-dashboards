<?php
namespace App\Controllers;

use App\Models\Admin;

class AdminController
{
    /**
     * Controleert of de ingelogde gebruiker admin of eigenaar is.
     * Gooit een 403 als dat niet zo is.
     */
    private function requireAdmin(): int
    {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . base_url('/login'));
            exit;
        }

        $rol = (int) ($_SESSION['user_rol'] ?? 0);
        if (!in_array($rol, [Admin::ROL_ADMIN, Admin::ROL_EIGENAAR], true)) {
            http_response_code(403);
            require dirname(__DIR__) . '/Views/layout/header.php';
            require dirname(__DIR__) . '/Views/admin/forbidden.php';
            require dirname(__DIR__) . '/Views/layout/footer.php';
            exit;
        }

        return (int) $_SESSION['user_id'];
    }

    /**
     * Controleert of de ingelogde gebruiker eigenaar is.
     * Redirect naar admin panel met foutmelding als dat niet zo is.
     */
    private function requireEigenaar(): void
    {
        $this->requireAdmin();

        if ((int) ($_SESSION['user_rol'] ?? 0) !== Admin::ROL_EIGENAAR) {
            $_SESSION['errors'] = ['Deze actie is alleen beschikbaar voor de eigenaar.'];
            header('Location: ' . base_url('/admin'));
            exit;
        }
    }

    /**
     * GET /admin — admin paneel.
     */
    public function index(): void
    {
        $this->requireAdmin();

        $title      = 'Admin panel';
        $rol        = (int) ($_SESSION['user_rol'] ?? 0);
        $stats      = Admin::systeemStats();
        $gebruikers = Admin::alleGebruikers();
        $projecten  = Admin::alleProjecten();
        $flash      = $_SESSION['flash']  ?? null;
        $errors     = $_SESSION['errors'] ?? [];
        unset($_SESSION['flash'], $_SESSION['errors']);

        require dirname(__DIR__) . '/Views/layout/header.php';
        require dirname(__DIR__) . '/Views/admin/index.php';
        require dirname(__DIR__) . '/Views/layout/footer.php';
    }

    /**
     * POST /admin/gebruiker/rol — rol wijzigen (alleen eigenaar).
     */
    public function updateRol(): void
    {
        $this->requireEigenaar();

        $gebruikerId = (int) ($_POST['gebruiker_id'] ?? 0);
        $nieuweRol   = (int) ($_POST['rol_id']       ?? 0);

        if ($gebruikerId > 0 && Admin::updateRol($gebruikerId, $nieuweRol)) {
            $_SESSION['flash'] = 'Rol bijgewerkt.';
        } else {
            $_SESSION['errors'] = ['Kon de rol niet wijzigen.'];
        }

        header('Location: ' . base_url('/admin'));
        exit;
    }

    /**
     * POST /admin/gebruiker/toggle — gebruiker activeren of deactiveren (alleen eigenaar).
     */
    public function toggleActief(): void
    {
        $this->requireEigenaar();

        $gebruikerId = (int)  ($_POST['gebruiker_id'] ?? 0);
        $actief      = (bool) ($_POST['actief']       ?? false);

        // Voorkom dat de eigenaar zichzelf deactiveert
        if ($gebruikerId === (int) $_SESSION['user_id']) {
            $_SESSION['errors'] = ['Je kunt je eigen account niet deactiveren.'];
            header('Location: ' . base_url('/admin'));
            exit;
        }

        if ($gebruikerId > 0 && Admin::toggleActief($gebruikerId, $actief)) {
            $_SESSION['flash'] = $actief ? 'Gebruiker geactiveerd.' : 'Gebruiker gedeactiveerd.';
        } else {
            $_SESSION['errors'] = ['Kon de status niet wijzigen.'];
        }

        header('Location: ' . base_url('/admin'));
        exit;
    }
}