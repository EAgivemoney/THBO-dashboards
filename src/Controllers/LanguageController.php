<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Lang;

/**
 * POST /language — switch language.
 *
 * The choice is stored in a cookie for one year (also after logging out)
 * and applies to the whole site. The visitor is then sent back to the page
 * they came from.
 */
class LanguageController
{
    private const ONE_YEAR = 31536000;

    public function switch(): void
    {
        $code = (string) ($_POST['lang'] ?? '');

        // Only languages that have a file in resources/lang/.
        if (Lang::isAvailable($code)) {
            setcookie(Lang::COOKIE, $code, [
                'expires'  => time() + self::ONE_YEAR,
                'path'     => '/',
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
        }

        header('Location: ' . $this->safeBackUrl((string) ($_POST['back'] ?? '')));
        exit;
    }

    /**
     * Only go back to a path on this site (no http://..., no //host),
     * otherwise to the dashboard or the login page.
     */
    private function safeBackUrl(string $back): string
    {
        $isLocalPath = $back !== ''
            && $back[0] === '/'
            && !str_starts_with($back, '//')
            && !str_contains($back, '\\')
            && !preg_match('/[\x00-\x1f\x7f]/', $back);

        return $isLocalPath
            ? $back
            : base_url(empty($_SESSION['user_id']) ? '/login' : '/dashboard');
    }
}