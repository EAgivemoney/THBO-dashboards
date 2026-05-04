<?php /** @var array $errors @var array $old */ ?>
<section class="auth">
    <div class="auth__card">
        <h1 class="auth__title">Account aanmaken</h1>
        <p class="auth__subtitle">Maak een account aan om je projecten en taken te beheren.</p>

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
            <div class="form__row">
                <label class="form__field">
                    <span class="form__label">Voornaam</span>
                    <input type="text" name="voornaam"
                           value="<?= htmlspecialchars($old['voornaam'] ?? '') ?>" required>
                </label>

                <label class="form__field form__field--narrow">
                    <span class="form__label">Tussenvoegsel</span>
                    <input type="text" name="tussenvoegsel"
                           value="<?= htmlspecialchars($old['tussenvoegsel'] ?? '') ?>">
                </label>

                <label class="form__field">
                    <span class="form__label">Achternaam</span>
                    <input type="text" name="achternaam"
                           value="<?= htmlspecialchars($old['achternaam'] ?? '') ?>" required>
                </label>
            </div>

            <label class="form__field">
                <span class="form__label">E-mailadres</span>
                <input type="email" name="email"
                       value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                       autocomplete="email" required>
            </label>

            <div class="form__row">
                <label class="form__field">
                    <span class="form__label">Wachtwoord</span>
                    <input type="password" name="wachtwoord"
                           autocomplete="new-password" required minlength="8">
                </label>

                <label class="form__field">
                    <span class="form__label">Bevestig wachtwoord</span>
                    <input type="password" name="wachtwoord_bevestiging"
                           autocomplete="new-password" required minlength="8">
                </label>
            </div>

            <button type="submit" class="btn btn--primary btn--block">Account aanmaken</button>
        </form>

        <p class="auth__alt">
            Al een account?
            <a href="<?= base_url('/login') ?>">Log hier in</a>.
        </p>
    </div>
</section>
