<?php
/**
 * @var array   $user
 * @var ?string $flash
 * @var array   $errors
 */
use App\Core\Lang;
use App\Models\Admin;
?>

<section class="profile">
    <header class="profile__head">
        <div>
            <h1 class="profile__title"><?= t('ui.profile.title') ?></h1>
            <p class="profile__subtitle"><?= t('ui.profile.subtitle') ?></p>
        </div>
        <a class="btn btn--ghost" href="<?= base_url('/dashboard') ?>"><?= t('ui.profile.back') ?></a>
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

    <!-- Profile overview -->
    <div class="profile__card">
        <div class="profile__avatar">
            <?= mb_strtoupper(mb_substr($user['first_name'], 0, 1)) ?>
        </div>
        <div class="profile__info">
            <div class="profile__info-row">
                <span class="profile__info-label"><?= t('ui.profile.name') ?></span>
                <span class="profile__info-value"><?= htmlspecialchars($user['full_name']) ?></span>
            </div>
            <div class="profile__info-row">
                <span class="profile__info-label"><?= t('ui.profile.email') ?></span>
                <span class="profile__info-value"><?= htmlspecialchars($user['email']) ?></span>
            </div>
            <div class="profile__info-row">
                <span class="profile__info-label"><?= t('ui.profile.role') ?></span>
                <span class="profile__info-value">
                    <span class="role-badge role-badge--<?= strtolower(Admin::roleName((int) $user['role_id'])) ?>">
                        <?= htmlspecialchars(Admin::roleLabel((int) $user['role_id'])) ?>
                    </span>
                </span>
            </div>
            <div class="profile__info-row">
                <span class="profile__info-label"><?= t('ui.profile.member_since') ?></span>
                <span class="profile__info-value"><?= htmlspecialchars(Lang::date($user['created_at'])) ?></span>
            </div>
        </div>
        <div class="profile__card-footer">
            <button type="button" class="btn btn--primary" id="password-toggle">
                <?= t('ui.profile.change_password') ?>
            </button>
        </div>
    </div>

    <!-- Password form (hidden until the button is clicked) -->
    <div class="profile__card profile__card--password" id="password-form" hidden>
        <h2 class="profile__card-title"><?= t('ui.profile.change_password') ?></h2>
        <form action="<?= base_url('/profile/password') ?>" method="post" class="form">
            <label class="form__field">
                <span class="form__label"><?= t('ui.profile.current') ?></span>
                <input type="password" name="current_password" required autocomplete="current-password">
            </label>
            <label class="form__field">
                <span class="form__label"><?= t('ui.profile.new') ?></span>
                <input type="password" name="new_password" required minlength="8" autocomplete="new-password">
            </label>
            <label class="form__field">
                <span class="form__label"><?= t('ui.profile.confirm') ?></span>
                <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
            </label>
            <div class="profile__form-actions">
                <button type="button" class="btn btn--ghost" id="password-cancel"><?= t('ui.profile.cancel') ?></button>
                <button type="submit" class="btn btn--primary"><?= t('ui.profile.save') ?></button>
            </div>
        </form>
    </div>
</section>

<script>
(function () {
    var toggle   = document.getElementById('password-toggle');
    var form     = document.getElementById('password-form');
    var cancel   = document.getElementById('password-cancel');

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

    cancel.addEventListener('click', function () {
        form.hidden = true;
        toggle.hidden = false;
    });
})();
</script>