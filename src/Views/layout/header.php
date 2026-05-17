<?php /** @var string $title */ ?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'THBO Dashboards') ?> — THBO Dashboards</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="<?= base_url('/css/main.css') ?>">
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
                    <span class="topbar__user">Hoi, <?= htmlspecialchars($_SESSION['user_naam'] ?? '') ?></span>
                    <a class="btn btn--ghost" href="<?= base_url('/dashboard') ?>">Dashboard</a>
                    <a class="btn btn--ghost" href="<?= base_url('/logout') ?>">Uitloggen</a>
                <?php else: ?>
                    <a class="btn btn--ghost"   href="<?= base_url('/login') ?>">Inloggen</a>
                    <a class="btn btn--primary" href="<?= base_url('/register') ?>">Account aanmaken</a>
                <?php endif; ?>
            </nav>
        </div>
    </header>

    <main class="main">