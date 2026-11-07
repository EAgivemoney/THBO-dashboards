<?php /** @var array $errors @var array $old */ ?>
<section class="auth">
    <div class="auth__card">
        <h1 class="auth__title"><?= t('ui.register.title') ?></h1>
        <p class="auth__subtitle"><?= t('ui.register.subtitle') ?></p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert--error">
                <ul>
                    <?php foreach ($errors as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('/register') ?>" method="post" class="form">

            <div class="form__row" style="grid-template-columns: 1fr minmax(80px, 140px) 1fr;">
                <label class="form__field">
                    <span class="form__label"><?= t('ui.register.firstname') ?></span>
                    <input type="text" name="first_name"
                           value="<?= htmlspecialchars($old['first_name'] ?? '') ?>" required>
                </label>

                <label class="form__field">
                    <span class="form__label"><?= t('ui.register.infix') ?></span>
                    <input type="text" name="name_prefix"
                           value="<?= htmlspecialchars($old['name_prefix'] ?? '') ?>">
                </label>

                <label class="form__field">
                    <span class="form__label"><?= t('ui.register.lastname') ?></span>
                    <input type="text" name="last_name"
                           value="<?= htmlspecialchars($old['last_name'] ?? '') ?>" required>
                </label>
            </div>

            <label class="form__field">
                <span class="form__label"><?= t('ui.register.email') ?></span>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                       autocomplete="email" required>
            </label>

            <div class="form__row">
                <label class="form__field">
                    <span class="form__label"><?= t('ui.register.password') ?></span>
                    <input type="password" name="password"
                           autocomplete="new-password" required minlength="8">
                </label>

                <label class="form__field">
                    <span class="form__label"><?= t('ui.register.confirm') ?></span>
                    <input type="password" name="password_confirmation"
                           autocomplete="new-password" required minlength="8">
                </label>
            </div>

            <button type="submit" class="btn btn--primary btn--block"><?= t('ui.register.submit') ?></button>
        </form>

        <p class="auth__alt">
            <?= t('ui.register.have_account') ?>
            <a href="<?= base_url('/login') ?>"><?= t('ui.register.login_link') ?></a>.
        </p>
    </div>
</section>