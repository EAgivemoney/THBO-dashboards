<?php /** @var array $errors @var array $old */ ?>
<section class="auth">
    <div class="auth__card">
        <h1 class="auth__title">Welkom terug</h1>
        <p class="auth__subtitle">Log in om je dashboard te openen.</p>

        <?php if (!empty($_SESSION['flash'])): ?>
            <div class="alert alert--success"><?= htmlspecialchars($_SESSION['flash']) ?></div>
            <?php unset($_SESSION['flash']); ?>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert--error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('/login') ?>" method="post" class="form">
            <label class="form__field">
                <span class="form__label">E-mailadres</span>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                       autocomplete="username" required>
            </label>

            <label class="form__field">
                <span class="form__label">Wachtwoord</span>
                <input type="password" name="wachtwoord"
                       autocomplete="current-password" required>
            </label>

            <button type="submit" class="btn btn--primary btn--block">Inloggen</button>
        </form>

        <p class="auth__alt">
            Nog geen account?
            <a href="<?= base_url('/register') ?>">Registreer hier</a>.
        </p>
    </div>
</section>
