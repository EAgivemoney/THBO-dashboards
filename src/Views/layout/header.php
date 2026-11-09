<?php
/** @var string $title */
use App\Core\Lang;

$role = (int) ($_SESSION['user_role'] ?? 0);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Lang::htmlLang()) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="light dark">
    <!-- Apply the saved theme before the stylesheet loads, so there is no flash of the wrong theme -->
    <script>
        (function () {
            try {
                var theme = localStorage.getItem('thbo-theme');
                if (theme === 'dark' || theme === 'light') {
                    document.documentElement.setAttribute('data-theme', theme);
                }
            } catch (e) { /* storage not available: follow the system setting */ }
        })();
    </script>
    <title><?= htmlspecialchars($title ?? 'THBO Dashboards') ?> — THBO Dashboards</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <link rel="stylesheet" href="<?= base_url('/css/main.css') ?>?v=<?= filemtime(dirname(__DIR__, 3) . '/public/css/main.css') ?>">
</head>
<body class="app">
    <header class="topbar">
        <div class="topbar__inner">
            <a class="topbar__brand" href="<?= base_url('/') ?>">
                <span class="topbar__logo">T</span>
                <span class="topbar__name">THBO Dashboards</span>
            </a>

            <nav class="topbar__nav">
                <?php if (!empty($_SESSION['user_id'])): ?>
                    <span class="topbar__user"><?= t('ui.layout.hi', ['name' => $_SESSION['user_name'] ?? '']) ?></span>
                    <a class="btn btn--ghost" href="<?= base_url('/dashboard') ?>"><?= t('ui.layout.dashboard') ?></a>
                    <a class="btn btn--ghost" href="<?= base_url('/profile') ?>"><?= t('ui.layout.profile') ?></a>
                    <?php if (in_array($role, [2, 3], true)): ?>
                        <a class="btn btn--ghost<?= $role === 3 ? ' btn--admin-owner' : '' ?>"
                           href="<?= base_url('/admin') ?>">
                            <?= $role === 3 ? '★ ' : '' ?><?= t('ui.layout.admin') ?>
                        </a>
                    <?php endif; ?>
                    <a class="btn btn--ghost" href="<?= base_url('/logout') ?>"><?= t('ui.layout.logout') ?></a>
                <?php else: ?>
                    <a class="btn btn--ghost"   href="<?= base_url('/login') ?>"><?= t('ui.layout.login') ?></a>
                    <a class="btn btn--primary" href="<?= base_url('/register') ?>"><?= t('ui.layout.register') ?></a>
                <?php endif; ?>

                <!-- Light/dark toggle: hidden until app.js is running, because it needs JavaScript -->
                <button type="button" class="btn btn--ghost theme-toggle" id="theme-toggle" hidden
                        data-label-to-dark="<?= t('ui.layout.theme_to_dark') ?>"
                        data-label-to-light="<?= t('ui.layout.theme_to_light') ?>">
                    <span class="material-symbols-outlined theme-toggle__icon" aria-hidden="true">dark_mode</span>
                </button>

                <!-- Language picker: also works without JavaScript (details/summary + plain form buttons) -->
                <details class="lang-switch">
                    <summary class="btn btn--ghost lang-switch__button" title="<?= t('ui.layout.language') ?>" aria-label="<?= t('ui.layout.language') ?>">
                        <span aria-hidden="true">&#127760;</span> <?= htmlspecialchars(Lang::languages()[Lang::locale()]['short']) ?>
                    </summary>
                    <form class="lang-switch__menu" method="post" action="<?= base_url('/language') ?>">
                        <input type="hidden" name="back" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/', ENT_QUOTES) ?>">
                        <?php foreach (Lang::languages() as $code => $info): ?>
                            <button type="submit" name="lang" value="<?= htmlspecialchars($code) ?>"
                                    class="lang-switch__option<?= $code === Lang::locale() ? ' lang-switch__option--active' : '' ?>"
                                    lang="<?= htmlspecialchars($code) ?>">
                                <?= htmlspecialchars($info['name']) ?>
                                <span class="lang-switch__code"><?= htmlspecialchars($info['short']) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </form>
                </details>
            </nav>
        </div>
    </header>

    <main class="main">