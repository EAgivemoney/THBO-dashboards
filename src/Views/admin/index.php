<?php
/**
 * @var int    $role     ROLE_ADMIN (2) or ROLE_OWNER (3)
 * @var array  $stats    System-wide statistics
 * @var array  $users    All users
 * @var array  $projects All projects
 * @var ?string $flash
 * @var array  $errors
 */
use App\Core\Lang;
use App\Models\Admin;

$isOwner = $role === Admin::ROLE_OWNER;
?>

<section class="admin">

    <header class="admin__head">
        <div>
            <h1 class="admin__title">
                <?= t('ui.admin.title') ?>
                <?php if ($isOwner): ?>
                    <span class="role-badge role-badge--owner"><?= htmlspecialchars(Admin::roleLabel(Admin::ROLE_OWNER)) ?></span>
                <?php else: ?>
                    <span class="role-badge role-badge--admin"><?= htmlspecialchars(Admin::roleLabel(Admin::ROLE_ADMIN)) ?></span>
                <?php endif; ?>
            </h1>
            <p class="admin__subtitle">
                <?= $isOwner
                    ? t('ui.admin.owner_subtitle')
                    : t('ui.admin.admin_subtitle') ?>
            </p>
        </div>
        <div class="admin__actions">
            <?php if ($isOwner): ?>
                <form method="post" action="<?= base_url('/admin/demo') ?>"
                      onsubmit="<?= t_confirm('ui.admin.confirm_demo') ?>">
                    <button type="submit" class="btn btn--ghost"><?= t('ui.admin.load_demo') ?></button>
                </form>
            <?php endif; ?>
            <a class="btn btn--ghost" href="<?= base_url('/admin/logs') ?>"><?= t('ui.admin.logs') ?></a>
            <a class="btn btn--ghost" href="<?= base_url('/dashboard') ?>"><?= t('ui.admin.back') ?></a>
        </div>
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

    <!-- Statistics -->
    <div class="admin-stats">
        <div class="stat-card">
            <span class="stat-card__number"><?= (int) $stats['user_count'] ?></span>
            <span class="stat-card__label"><?= t('ui.admin.stat_users') ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-card__number"><?= (int) $stats['inactive_user_count'] ?></span>
            <span class="stat-card__label"><?= t('ui.admin.stat_inactive') ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-card__number"><?= (int) $stats['project_count'] ?></span>
            <span class="stat-card__label"><?= t('ui.admin.stat_projects') ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-card__number"><?= (int) $stats['task_count'] ?></span>
            <span class="stat-card__label"><?= t('ui.admin.stat_tasks') ?></span>
        </div>
        <div class="stat-card stat-card--accent">
            <?php
                $pct = $stats['task_count'] > 0
                    ? round(($stats['completed_task_count'] / $stats['task_count']) * 100)
                    : 0;
            ?>
            <span class="stat-card__number"><?= $pct ?>%</span>
            <span class="stat-card__label"><?= t('ui.admin.stat_done') ?></span>
        </div>
    </div>

    <!-- Tabs -->
    <div class="admin-tabs">
        <button class="admin-tab admin-tab--active" data-tab="users">
            <?= t('ui.admin.tab_users') ?> <span class="admin-tab__count"><?= count($users) ?></span>
        </button>
        <button class="admin-tab" data-tab="projects">
            <?= t('ui.admin.tab_projects') ?> <span class="admin-tab__count"><?= count($projects) ?></span>
        </button>
    </div>

    <!-- Tab: Users -->
    <div class="admin-panel" id="tab-users">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= t('ui.admin.th_name') ?></th>
                    <th><?= t('ui.admin.th_email') ?></th>
                    <th><?= t('ui.admin.th_role') ?></th>
                    <th><?= t('ui.admin.th_projects') ?></th>
                    <th><?= t('ui.admin.th_status') ?></th>
                    <?php if ($isOwner): ?><th><?= t('ui.admin.th_actions') ?></th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <?php $userRole = (int) $user['role_id']; ?>
                    <tr class="<?= !$user['is_active'] ? 'admin-table__row--inactive' : '' ?>">
                        <td>
                            <strong><?= htmlspecialchars($user['full_name']) ?></strong>
                            <?php if ($user['id'] === $_SESSION['user_id']): ?>
                                <span class="tag tag--you"><?= t('ui.admin.you') ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="admin-table__muted"><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <span class="role-badge role-badge--<?= strtolower(Admin::roleName($userRole)) ?>">
                                <?= htmlspecialchars(Admin::roleLabel($userRole)) ?>
                            </span>
                        </td>
                        <td class="admin-table__center"><?= (int) $user['project_count'] ?></td>
                        <td>
                            <span class="status-dot status-dot--<?= $user['is_active'] ? 'active' : 'inactive' ?>">
                                <?= t($user['is_active'] ? 'ui.admin.active' : 'ui.admin.inactive') ?>
                            </span>
                        </td>
                        <?php if ($isOwner): ?>
                            <td class="admin-table__actions">
                                <?php if ($user['id'] !== (int) $_SESSION['user_id']): ?>
                                    <!-- Change role -->
                                    <form action="<?= base_url('/admin/user/role') ?>" method="post" class="inline-form">
                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                        <select name="role_id" class="select-inline" onchange="this.form.submit()" title="<?= t('ui.admin.change_role') ?>">
                                            <option value="<?= Admin::ROLE_USER ?>" <?= $userRole === Admin::ROLE_USER ? 'selected' : '' ?>><?= htmlspecialchars(Admin::roleLabel(Admin::ROLE_USER)) ?></option>
                                            <option value="<?= Admin::ROLE_ADMIN ?>"     <?= $userRole === Admin::ROLE_ADMIN     ? 'selected' : '' ?>><?= htmlspecialchars(Admin::roleLabel(Admin::ROLE_ADMIN)) ?></option>
                                            <option value="<?= Admin::ROLE_OWNER ?>"  <?= $userRole === Admin::ROLE_OWNER  ? 'selected' : '' ?>><?= htmlspecialchars(Admin::roleLabel(Admin::ROLE_OWNER)) ?></option>
                                        </select>
                                    </form>
                                    <!-- Activate / deactivate -->
                                    <form action="<?= base_url('/admin/user/toggle') ?>" method="post"
                                          onsubmit="<?= t_confirm($user['is_active'] ? 'ui.admin.confirm_deactivate' : 'ui.admin.confirm_activate', ['name' => $user['full_name']]) ?>">
                                        <input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>">
                                        <input type="hidden" name="active"       value="<?= $user['is_active'] ? '0' : '1' ?>">
                                        <button type="submit" class="btn btn--sm <?= $user['is_active'] ? 'btn--danger' : 'btn--success' ?>">
                                            <?= t($user['is_active'] ? 'ui.admin.deactivate' : 'ui.admin.activate') ?>
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

    <!-- Tab: Projects -->
    <div class="admin-panel admin-panel--hidden" id="tab-projects">
        <table class="admin-table">
            <thead>
                <tr>
                    <th><?= t('ui.admin.th_project') ?></th>
                    <th><?= t('ui.admin.th_owner') ?></th>
                    <th><?= t('ui.admin.th_tasks') ?></th>
                    <th><?= t('ui.admin.th_progress') ?></th>
                    <th><?= t('ui.admin.th_created') ?></th>
                    <th><?= t('ui.admin.th_status') ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <tr class="<?= !$project['is_active'] ? 'admin-table__row--inactive' : '' ?>">
                        <td><strong><?= htmlspecialchars($project['title']) ?></strong></td>
                        <td class="admin-table__muted"><?= htmlspecialchars($project['owner']) ?></td>
                        <td class="admin-table__center"><?= (int) $project['task_count'] ?></td>
                        <td class="admin-table__progress">
                            <div class="progress">
                                <div class="progress__bar" style="width: <?= (int) $project['progress'] ?>%"></div>
                            </div>
                            <span class="progress__label"><?= (int) $project['progress'] ?>%</span>
                        </td>
                        <td class="admin-table__muted"><?= htmlspecialchars(Lang::dateShort($project['created_at'])) ?></td>
                        <td>
                            <span class="status-dot status-dot--<?= $project['is_active'] ? 'active' : 'inactive' ?>">
                                <?= t($project['is_active'] ? 'ui.admin.active' : 'ui.admin.deleted') ?>
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