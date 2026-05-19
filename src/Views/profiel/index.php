<?php
/**
 * @var array   $user
 * @var ?string $flash
 * @var array   $errors
 */
use App\Models\Admin;
?>

<section class="profiel">
    <header class="profiel__head">
        <div>
            <h1 class="profiel__title">Mijn profiel</h1>
            <p class="profiel__subtitle">Jouw accountgegevens in één overzicht.</p>
        </div>
        <a class="btn btn--ghost" href="<?= base_url('/dashboard') ?>">← Dashboard</a>
    </header>

    <?php if ($flash): ?>
        <div class="alert alert--success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert--error">
            <ul><?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?></ul>
        </div>
    <?php endif; ?>

    <!-- Profieloverzicht -->
    <div class="profiel__card">
        <div class="profiel__avatar">
            <?= mb_strtoupper(mb_substr($user['Voornaam'], 0, 1)) ?>
        </div>
        <div class="profiel__info">
            <div class="profiel__info-rij">
                <span class="profiel__info-label">Naam</span>
                <span class="profiel__info-waarde"><?= htmlspecialchars($user['VolledigeNaam']) ?></span>
            </div>
            <div class="profiel__info-rij">
                <span class="profiel__info-label">E-mail</span>
                <span class="profiel__info-waarde"><?= htmlspecialchars($user['Email']) ?></span>
            </div>
            <div class="profiel__info-rij">
                <span class="profiel__info-label">Rol</span>
                <span class="profiel__info-waarde">
                    <span class="rol-badge rol-badge--<?= strtolower(Admin::rolNaam((int) $user['RolId'])) ?>">
                        <?= Admin::rolNaam((int) $user['RolId']) ?>
                    </span>
                </span>
            </div>
            <div class="profiel__info-rij">
                <span class="profiel__info-label">Lid sinds</span>
                <span class="profiel__info-waarde"><?= date('d F Y', strtotime($user['AangemaaktOp'])) ?></span>
            </div>
        </div>
        <div class="profiel__card-footer">
            <button type="button" class="btn btn--primary" id="wachtwoord-toggle">
                Wachtwoord wijzigen
            </button>
        </div>
    </div>

    <!-- Wachtwoord formulier (verborgen tot op knop geklikt) -->
    <div class="profiel__card profiel__card--wachtwoord" id="wachtwoord-form" hidden>
        <h2 class="profiel__card-title">Wachtwoord wijzigen</h2>
        <form action="<?= base_url('/profiel/wachtwoord') ?>" method="post" class="form">
            <label class="form__field">
                <span class="form__label">Huidig wachtwoord</span>
                <input type="password" name="huidig_wachtwoord" required autocomplete="current-password">
            </label>
            <label class="form__field">
                <span class="form__label">Nieuw wachtwoord</span>
                <input type="password" name="nieuw_wachtwoord" required minlength="8" autocomplete="new-password">
            </label>
            <label class="form__field">
                <span class="form__label">Nieuw wachtwoord bevestigen</span>
                <input type="password" name="bevestiging" required minlength="8" autocomplete="new-password">
            </label>
            <div class="profiel__form-actions">
                <button type="button" class="btn btn--ghost" id="wachtwoord-annuleer">Annuleren</button>
                <button type="submit" class="btn btn--primary">Opslaan</button>
            </div>
        </form>
    </div>
</section>

<script>
(function () {
    var toggle   = document.getElementById('wachtwoord-toggle');
    var form     = document.getElementById('wachtwoord-form');
    var annuleer = document.getElementById('wachtwoord-annuleer');

    if (!toggle || !form) return;

    <?php if (!empty($errors)): ?>
    form.hidden = false;
    toggle.hidden = true;
    <?php endif; ?>

    toggle.addEventListener('click', function () {
        form.hidden = false;
        toggle.hidden = true;
        form.querySelector('input').focus();
    });

    annuleer.addEventListener('click', function () {
        form.hidden = true;
        toggle.hidden = false;
    });
})();
</script>