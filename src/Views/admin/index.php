<?php
/**
 * @var int    $rol        ROL_ADMIN (2) of ROL_EIGENAAR (3)
 * @var array  $stats      Systeembrede statistieken
 * @var array  $gebruikers Alle gebruikers
 * @var array  $projecten  Alle projecten
 * @var ?string $flash
 * @var array  $errors
 */
use App\Models\Admin;

$isEigenaar = $rol === Admin::ROL_EIGENAAR;
?>

<section class="admin">

    <header class="admin__head">
        <div>
            <h1 class="admin__title">
                Admin panel
                <?php if ($isEigenaar): ?>
                    <span class="rol-badge rol-badge--eigenaar">Eigenaar</span>
                <?php else: ?>
                    <span class="rol-badge rol-badge--admin">Admin</span>
                <?php endif; ?>
            </h1>
            <p class="admin__subtitle">
                <?= $isEigenaar
                    ? 'Volledig beheer over alle gebruikers, rollen en projecten.'
                    : 'Leesrechten op alle gebruikers en projecten.' ?>
            </p>
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

    <!-- Statistieken -->
    <div class="admin-stats">
        <div class="stat-card">
            <span class="stat-card__getal"><?= (int) $stats['aantalGebruikers'] ?></span>
            <span class="stat-card__label">Actieve gebruikers</span>
        </div>
        <div class="stat-card">
            <span class="stat-card__getal"><?= (int) $stats['aantalInactief'] ?></span>
            <span class="stat-card__label">Inactieve gebruikers</span>
        </div>
        <div class="stat-card">
            <span class="stat-card__getal"><?= (int) $stats['aantalProjecten'] ?></span>
            <span class="stat-card__label">Actieve projecten</span>
        </div>
        <div class="stat-card">
            <span class="stat-card__getal"><?= (int) $stats['aantalTaken'] ?></span>
            <span class="stat-card__label">Actieve taken</span>
        </div>
        <div class="stat-card stat-card--accent">
            <?php
                $pct = $stats['aantalTaken'] > 0
                    ? round(($stats['aantalVoltooid'] / $stats['aantalTaken']) * 100)
                    : 0;
            ?>
            <span class="stat-card__getal"><?= $pct ?>%</span>
            <span class="stat-card__label">Voltooid systeem-breed</span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="admin-tabs">
        <button class="admin-tab admin-tab--active" data-tab="gebruikers">
            Gebruikers <span class="admin-tab__count"><?= count($gebruikers) ?></span>
        </button>
        <button class="admin-tab" data-tab="projecten">
            Projecten <span class="admin-tab__count"><?= count($projecten) ?></span>
        </button>
    </div>

    <!-- Tab: Gebruikers -->
    <div class="admin-panel" id="tab-gebruikers">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>E-mail</th>
                    <th>Rol</th>
                    <th>Projecten</th>
                    <th>Status</th>
                    <?php if ($isEigenaar): ?><th>Acties</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gebruikers as $g): ?>
                    <?php $gRol = (int) $g['RolId']; ?>
                    <tr class="<?= !$g['IsActief'] ? 'admin-table__row--inactief' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars($g['VolledigeNaam']) ?></strong>
                            <?php if ($g['Id'] === $_SESSION['user_id']): ?>
                                <span class="tag tag--jij">jij</span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-table__muted"><?= htmlspecialchars($g['Email']) ?></td>
                        <td>
                            <span class="rol-badge rol-badge--<?= strtolower(Admin::rolNaam($gRol)) ?>">
                                <?= Admin::rolNaam($gRol) ?>
                            </span>
                        </td>
                        <td class="admin-table__center"><?= (int) $g['AantalProjecten'] ?></td>
                        <td>
                            <span class="status-dot status-dot--<?= $g['IsActief'] ? 'actief' : 'inactief' ?>">
                                <?= $g['IsActief'] ? 'Actief' : 'Inactief' ?>
                            </span>
                        </td>
                        <?php if ($isEigenaar): ?>
                            <td class="admin-table__actions">
                                <?php if ($g['Id'] !== (int) $_SESSION['user_id']): ?>
                                    <!-- Rol wijzigen -->
                                    <form action="<?= base_url('/admin/gebruiker/rol') ?>" method="post" class="inline-form">
                                        <input type="hidden" name="gebruiker_id" value="<?= (int) $g['Id'] ?>">
                                        <select name="rol_id" class="select-inline" onchange="this.form.submit()" title="Rol wijzigen">
                                            <option value="<?= Admin::ROL_GEBRUIKER ?>" <?= $gRol === Admin::ROL_GEBRUIKER ? 'selected' : '' ?>>Gebruiker</option>
                                            <option value="<?= Admin::ROL_ADMIN ?>"     <?= $gRol === Admin::ROL_ADMIN     ? 'selected' : '' ?>>Admin</option>
                                            <option value="<?= Admin::ROL_EIGENAAR ?>"  <?= $gRol === Admin::ROL_EIGENAAR  ? 'selected' : '' ?>>Eigenaar</option>
                                        </select>
                                    </form>
                                    <!-- Activeren / deactiveren -->
                                    <form action="<?= base_url('/admin/gebruiker/toggle') ?>" method="post"
                                          onsubmit="return confirm('Gebruiker <?= htmlspecialchars($g['VolledigeNaam'], ENT_QUOTES) ?> <?= $g['IsActief'] ? 'deactiveren' : 'activeren' ?>?')">
                                        <input type="hidden" name="gebruiker_id" value="<?= (int) $g['Id'] ?>">
                                        <input type="hidden" name="actief"       value="<?= $g['IsActief'] ? '0' : '1' ?>">
                                        <button type="submit" class="btn btn--sm <?= $g['IsActief'] ? 'btn--danger' : 'btn--success' ?>">
                                            <?= $g['IsActief'] ? 'Deactiveren' : 'Activeren' ?>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="admin-table__muted">—</span>
                                <?php endif; ?>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Tab: Projecten -->
    <div class="admin-panel admin-panel--hidden" id="tab-projecten">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Eigenaar</th>
                    <th>Taken</th>
                    <th>Voortgang</th>
                    <th>Aangemaakt</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projecten as $p): ?>
                    <tr class="<?= !$p['IsActief'] ? 'admin-table__row--inactief' : '' ?>">
                        <td><strong><?= htmlspecialchars($p['Titel']) ?></strong></td>
                        <td class="admin-table__muted"><?= htmlspecialchars($p['Eigenaar']) ?></td>
                        <td class="admin-table__center"><?= (int) $p['AantalTaken'] ?></td>
                        <td class="admin-table__progress">
                            <div class="progress">
                                <div class="progress__bar" style="width: <?= (int) $p['Voortgang'] ?>%"></div>
                            </div>
                            <span class="progress__label"><?= (int) $p['Voortgang'] ?>%</span>
                        </td>
                        <td class="admin-table__muted"><?= date('d-m-Y', strtotime($p['AangemaaktOp'])) ?></td>
                        <td>
                            <span class="status-dot status-dot--<?= $p['IsActief'] ? 'actief' : 'inactief' ?>">
                                <?= $p['IsActief'] ? 'Actief' : 'Verwijderd' ?>
                            </span>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

</section>

<script>
(function () {
    var tabs   = document.querySelectorAll('.admin-tab');
    var panels = document.querySelectorAll('.admin-panel');

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) { t.classList.remove('admin-tab--active'); });
            panels.forEach(function (p) { p.classList.add('admin-panel--hidden'); });

            tab.classList.add('admin-tab--active');
            var panel = document.getElementById('tab-' + tab.dataset.tab);
            if (panel) panel.classList.remove('admin-panel--hidden');
        });
    });
})();
</script>