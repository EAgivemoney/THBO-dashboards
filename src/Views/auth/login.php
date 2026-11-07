<?php /** @var array $errors @var array $old */ ?>
<section class="auth">
    <div class="auth__card">
        <h1 class="auth__title"><?= t('ui.login.title') ?></h1>
        <p class="auth__subtitle"><?= t('ui.login.subtitle') ?></p>

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
                <span class="form__label"><?= t('ui.login.email') ?></span>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                       autocomplete="username" required>
            </label>

            <label class="form__field">
                <span class="form__label"><?= t('ui.login.password') ?></span>
                <input type="password" name="password"
                       autocomplete="current-password" required>
            </label>

            <button type="submit" class="btn btn--primary btn--block"><?= t('ui.login.submit') ?></button>
        </form>

        <p class="auth__alt">
            <?= t('ui.login.no_account') ?>
            <a href="<?= base_url('/register') ?>"><?= t('ui.login.register_link') ?></a>.
        </p>
    </div>
</section>
