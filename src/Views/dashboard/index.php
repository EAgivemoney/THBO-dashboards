<?php
/** @var array $board @var array $projects @var ?string $flash @var array $errors */
use App\Core\Lang;
use App\Models\Task;
$columns = [
    Task::STATUS_TODO  => ['label' => Lang::get('status.todo'),  'slug' => 'todo'],
    Task::STATUS_DOING => ['label' => Lang::get('status.doing'), 'slug' => 'doing'],
    Task::STATUS_DONE  => ['label' => Lang::get('status.done'),  'slug' => 'done'],
];
$priorityLabels = [
    1 => ['label' => Lang::get('priority.1'), 'class' => 'prio--1'],
    2 => ['label' => Lang::get('priority.2'), 'class' => 'prio--2'],
    3 => ['label' => Lang::get('priority.3'), 'class' => 'prio--3'],
    4 => ['label' => Lang::get('priority.4'), 'class' => 'prio--4'],
    5 => ['label' => Lang::get('priority.5'), 'class' => 'prio--5'],
];
?>
<section class="dashboard">
    <header class="dashboard__head">
        <div>
            <h1 class="dashboard__title"><?= t('ui.dashboard.title') ?></h1>
            <p class="dashboard__subtitle"><?= t('ui.dashboard.subtitle') ?></p>
        </div>
        <div class="dashboard__actions">
            <button type="button" class="btn btn--ghost" data-open="project-modal">&#128193; <?= t('ui.dashboard.manage_projects') ?></button>
            <button type="button" class="btn btn--primary" data-open="task-modal"><?= t('ui.dashboard.new_task') ?></button>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="alert alert--success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert--error"><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <!-- Filter bar -->
    <div class="filter-bar">
        <div class="filter-bar__group">
            <label class="filter-bar__label" for="filter-project"><?= t('ui.dashboard.filter_project') ?></label>
            <select class="filter-bar__select" id="filter-project">
                <option value=""><?= t('ui.dashboard.all_projects') ?></option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-bar__group">
            <label class="filter-bar__label" for="filter-priority"><?= t('ui.dashboard.filter_priority') ?></label>
            <select class="filter-bar__select" id="filter-priority">
                <option value=""><?= t('ui.dashboard.all_priorities') ?></option>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> &mdash; <?= t('priority.' . $i) ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button class="filter-bar__reset" id="filter-reset" hidden><?= t('ui.dashboard.clear_filters') ?></button>
    </div>

    <div class="kanban">
        <?php foreach ($columns as $statusId => $info): ?>
            <div class="kanban__col" data-status-id="<?= $statusId ?>" data-slug="<?= $info['slug'] ?>">
                <header class="kanban__col-head">
                    <h2 class="kanban__col-title"><?= htmlspecialchars($info['label']) ?></h2>
                    <span class="kanban__count"><?= count($board[$statusId] ?? []) ?></span>
                </header>
                <div class="kanban__list" data-droppable="1">
                    <?php foreach ($board[$statusId] ?? [] as $task): ?>
                        <?php $dl = !empty($task['deadline']) ? date('Y-m-d\TH:i', strtotime($task['deadline'])) : ''; ?>
                        <article class="card"
                                 draggable="true"
                                 data-task-id="<?= (int) $task['id'] ?>"
                                 data-project-id="<?= (int) $task['project_id'] ?>"
                                 data-title="<?= htmlspecialchars($task['title'], ENT_QUOTES) ?>"
                                 data-description="<?= htmlspecialchars($task['description'] ?? '', ENT_QUOTES) ?>"
                                 data-priority="<?= (int) $task['priority'] ?>"
                                 data-deadline="<?= htmlspecialchars($dl) ?>">
                            <header class="card__head">
                                <h3 class="card__title"><?= htmlspecialchars($task['title']) ?></h3>
                                <span class="card__prio <?= $priorityLabels[(int)$task['priority']]['class'] ?? 'prio--3' ?>">
                                    <?= htmlspecialchars($priorityLabels[(int)$task['priority']]['label'] ?? Lang::get('priority.3')) ?>
                                </span>
                            </header>
                            <?php if (!empty($task['description'])): ?>
                                <p class="card__body"><?= nl2br(htmlspecialchars($task['description'])) ?></p>
                            <?php endif; ?>
                            <footer class="card__foot">
                                <span class="card__meta"><?= htmlspecialchars($task['project_title']) ?></span>
                                <?php if (!empty($task['deadline'])): ?>
                                    <span class="card__deadline">⏰ <?= htmlspecialchars(Lang::dateShort($task['deadline'])) ?></span>
                                <?php endif; ?>
                                <form action="<?= base_url('/tasks/delete') ?>" method="post" class="card__delete" onsubmit="<?= t_confirm('ui.dashboard.confirm_task_delete') ?>">
                                    <input type="hidden" name="task_id" value="<?= (int) $task['id'] ?>">
                                    <button type="submit" class="card__delete-btn" aria-label="<?= t('ui.dashboard.delete') ?>">&times;</button>
                                </form>
                            </footer>
                        </article>
                    <?php endforeach; ?>
                    <?php if (empty($board[$statusId])): ?>
                        <p class="kanban__empty"><?= t('ui.dashboard.empty_column') ?></p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Modal: new task -->
<div class="modal" id="task-modal" hidden>
    <div class="modal__backdrop" data-close="task-modal"></div>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="task-modal-title">
        <header class="modal__head">
            <h2 id="task-modal-title" class="modal__title"><?= t('ui.dashboard.task_modal_title') ?></h2>
            <button type="button" class="modal__close" data-close="task-modal" aria-label="<?= t('ui.dashboard.close') ?>">&times;</button>
        </header>
        <form action="<?= base_url('/tasks') ?>" method="post" class="form">
            <label class="form__field">
                <span class="form__label"><?= t('ui.dashboard.field_title') ?></span>
                <input type="text" name="title" required maxlength="120">
            </label>
            <label class="form__field">
                <span class="form__label"><?= t('ui.dashboard.field_description') ?></span>
                <textarea name="description" rows="3"></textarea>
            </label>
            <div class="form__row">
                <label class="form__field">
                    <span class="form__label"><?= t('ui.dashboard.field_project') ?></span>
                    <select name="project_id" required>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form__field">
                    <span class="form__label"><?= t('ui.dashboard.field_priority') ?></span>
                    <select name="priority">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>" <?= $i === 3 ? 'selected' : '' ?>><?= $i ?> &mdash; <?= t('priority.' . $i) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
            <label class="form__field">
                <span class="form__label"><?= t('ui.dashboard.field_deadline') ?></span>
                <input type="datetime-local" name="deadline">
            </label>
            <div class="modal__actions">
                <button type="button" class="btn btn--ghost" data-close="task-modal"><?= t('ui.dashboard.cancel') ?></button>
                <button type="submit" class="btn btn--primary"><?= t('ui.dashboard.add_task') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: edit task -->
<div class="modal" id="edit-modal" hidden>
    <div class="modal__backdrop" data-close="edit-modal"></div>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
        <header class="modal__head">
            <h2 id="edit-modal-title" class="modal__title"><?= t('ui.dashboard.edit_task_title') ?></h2>
            <button type="button" class="modal__close" data-close="edit-modal" aria-label="<?= t('ui.dashboard.close') ?>">&times;</button>
        </header>
        <form action="<?= base_url('/tasks/update') ?>" method="post" class="form">
            <input type="hidden" name="task_id" id="edit-task-id">
            <label class="form__field">
                <span class="form__label"><?= t('ui.dashboard.field_title') ?></span>
                <input type="text" name="title" id="edit-title" required maxlength="120">
            </label>
            <label class="form__field">
                <span class="form__label"><?= t('ui.dashboard.field_description') ?></span>
                <textarea name="description" id="edit-description" rows="3"></textarea>
            </label>
            <div class="form__row">
                <label class="form__field">
                    <span class="form__label"><?= t('ui.dashboard.field_project') ?></span>
                    <select name="project_id" id="edit-task-project-id" required>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= htmlspecialchars($p['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form__field">
                    <span class="form__label"><?= t('ui.dashboard.field_priority') ?></span>
                    <select name="priority" id="edit-priority">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?= $i ?>"><?= $i ?> &mdash; <?= t('priority.' . $i) ?></option>
                        <?php endfor; ?>
                    </select>
                </label>
            </div>
            <label class="form__field">
                <span class="form__label"><?= t('ui.dashboard.field_deadline') ?></span>
                <input type="datetime-local" name="deadline" id="edit-deadline">
            </label>
            <div class="modal__actions">
                <button type="button" class="btn btn--ghost" data-close="edit-modal"><?= t('ui.dashboard.cancel') ?></button>
                <button type="submit" class="btn btn--primary"><?= t('ui.dashboard.save') ?></button>
            </div>
        </form>
    </div>
</div>
<!-- Modal: manage projects -->
<div class="modal" id="project-modal" hidden>
    <div class="modal__backdrop" data-close="project-modal"></div>
    <div class="modal__panel modal__panel--wide" role="dialog" aria-modal="true" aria-labelledby="project-modal-title">
        <header class="modal__head">
            <h2 id="project-modal-title" class="modal__title"><?= t('ui.dashboard.projects_title') ?></h2>
            <button type="button" class="modal__close" data-close="project-modal" aria-label="<?= t('ui.dashboard.close') ?>">&times;</button>
        </header>

        <!-- Existing projects -->
        <div class="project-list">
            <?php if (empty($projectDetails)): ?>
                <p class="project-list__empty"><?= t('ui.dashboard.no_projects') ?></p>
            <?php else: ?>
                <?php $isOnlyProject = count($projectDetails) === 1; ?>
                <?php foreach ($projectDetails as $projectDetail): ?>
                    <div class="project-item" data-project-id="<?= (int) $projectDetail['id'] ?>">
                        <div class="project-item__info">
                            <div class="project-item__header">
                                <strong class="project-item__name"><?= htmlspecialchars($projectDetail['title']) ?></strong>
                                <div class="project-item__actions">
                                    <button type="button"
                                            class="btn btn--ghost btn--sm"
                                            data-open="edit-project-modal"
                                            data-project-id="<?= (int) $projectDetail['id'] ?>"
                                            data-project-title="<?= htmlspecialchars($projectDetail['title'], ENT_QUOTES) ?>"
                                            data-project-description="<?= htmlspecialchars($projectDetail['description'] ?? '', ENT_QUOTES) ?>">
                                        <?= t('ui.dashboard.edit') ?>
                                    </button>
                                    <?php if ($isOnlyProject): ?>
                                        <!-- The only project cannot be deleted until another one exists -->
                                        <button type="button" class="btn btn--danger btn--sm" disabled
                                                title="<?= t('ui.dashboard.last_project_hint') ?>"><?= t('ui.dashboard.delete') ?></button>
                                    <?php else: ?>
                                        <form action="<?= base_url('/projects/delete') ?>" method="post"
                                              onsubmit="<?= t_confirm('ui.dashboard.confirm_project_delete', ['name' => $projectDetail['title']]) ?>">
                                            <input type="hidden" name="project_id" value="<?= (int) $projectDetail['id'] ?>">
                                            <button type="submit" class="btn btn--danger btn--sm"><?= t('ui.dashboard.delete') ?></button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($projectDetail['description'])): ?>
                                <span class="project-item__desc"><?= htmlspecialchars($projectDetail['description']) ?></span>
                            <?php endif; ?>
                            <?php
                                $progress  = (int) $projectDetail['progress'];
                                $total     = (int) $projectDetail['task_count'];
                                $completed   = (int) $projectDetail['completed_count'];
                            ?>
                            <div class="project-item__progress">
                                <div class="progress">
                                    <div class="progress__bar" style="width: <?= $progress ?>%"></div>
                                </div>
                                <span class="progress__label">
                                    <?php if ($total === 0): ?>
                                        <?= t('ui.dashboard.no_tasks') ?>
                                    <?php else: ?>
                                        <?= t('ui.dashboard.progress', ['done' => $completed, 'total' => $total, 'percent' => $progress]) ?>
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if ($isOnlyProject): ?>
                    <p class="project-list__hint"><?= t('ui.dashboard.last_project_hint') ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <hr class="modal__divider">

        <!-- New project form -->
        <h3 class="modal__subtitle"><?= t('ui.dashboard.new_project') ?></h3>
        <form action="<?= base_url('/projects') ?>" method="post" class="form">
            <label class="form__field">
                <span class="form__label"><?= t('ui.dashboard.project_name') ?> <span aria-hidden="true">*</span></span>
                <input type="text" name="title" required maxlength="120" placeholder="<?= t('ui.dashboard.project_placeholder') ?>">
            </label>
            <label class="form__field">
                <span class="form__label"><?= t('ui.dashboard.field_description') ?></span>
                <textarea name="description" rows="2" placeholder="<?= t('ui.dashboard.description_placeholder') ?>"></textarea>
            </label>
            <div class="modal__actions">
                <button type="button" class="btn btn--ghost" data-close="project-modal"><?= t('ui.dashboard.cancel') ?></button>
                <button type="submit" class="btn btn--primary"><?= t('ui.dashboard.create_project') ?></button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: edit project -->
<div class="modal" id="edit-project-modal" hidden>
    <div class="modal__backdrop" data-close="edit-project-modal"></div>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="edit-project-modal-title">
        <header class="modal__head">
            <h2 id="edit-project-modal-title" class="modal__title"><?= t('ui.dashboard.edit_project_title') ?></h2>
            <button type="button" class="modal__close" data-close="edit-project-modal" aria-label="<?= t('ui.dashboard.close') ?>">&times;</button>
        </header>
        <form action="<?= base_url('/projects/update') ?>" method="post" class="form">
            <input type="hidden" name="project_id" id="edit-project-id">
            <label class="form__field">
                <span class="form__label"><?= t('ui.dashboard.project_name') ?> <span aria-hidden="true">*</span></span>
                <input type="text" name="title" id="edit-project-title" required maxlength="120">
            </label>
            <label class="form__field">
                <span class="form__label"><?= t('ui.dashboard.field_description') ?></span>
                <textarea name="description" id="edit-project-description" rows="2"></textarea>
            </label>
            <div class="modal__actions">
                <button type="button" class="btn btn--ghost" data-close="edit-project-modal"><?= t('ui.dashboard.cancel') ?></button>
                <button type="submit" class="btn btn--primary"><?= t('ui.dashboard.save') ?></button>
            </div>
        </form>
    </div>
</div>