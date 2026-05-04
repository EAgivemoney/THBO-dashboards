<?php
/** @var array $board @var array $projects @var ?string $flash @var array $errors */

use App\Models\Task;

$kolommen = [
    Task::STATUS_TODO  => ['label' => 'Te doen',  'slug' => 'todo'],
    Task::STATUS_DOING => ['label' => 'Bezig',    'slug' => 'doing'],
    Task::STATUS_DONE  => ['label' => 'Klaar',    'slug' => 'done'],
];

$prioLabels = [
    1 => ['label' => 'Hoogst', 'class' => 'prio--1'],
    2 => ['label' => 'Hoog',   'class' => 'prio--2'],
    3 => ['label' => 'Normaal','class' => 'prio--3'],
    4 => ['label' => 'Laag',   'class' => 'prio--4'],
    5 => ['label' => 'Laagst', 'class' => 'prio--5'],
];
?>

<section class="dashboard">
    <header class="dashboard__head">
        <div>
            <h1 class="dashboard__title">Mijn taken</h1>
            <p class="dashboard__subtitle">
                Sleep taken tussen kolommen om hun status te wijzigen.
            </p>
        </div>

        <button type="button" class="btn btn--primary" data-open="task-modal">
            + Nieuwe taak
        </button>
    </header>

    <?php if ($flash): ?>
        <div class="alert alert--success"><?= htmlspecialchars($flash) ?></div>
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

    <div class="kanban">
        <?php foreach ($kolommen as $statusId => $info): ?>
            <div class="kanban__col"
                 data-status-id="<?= $statusId ?>"
                 data-slug="<?= $info['slug'] ?>">
                <header class="kanban__col-head">
                    <h2 class="kanban__col-title"><?= $info['label'] ?></h2>
                    <span class="kanban__count"><?= count($board[$statusId] ?? []) ?></span>
                </header>

                <div class="kanban__list" data-droppable="1">
                    <?php foreach ($board[$statusId] ?? [] as $taak): ?>
                        <article class="card"
                                 draggable="true"
                                 data-task-id="<?= (int) $taak['Id'] ?>">
                            <header class="card__head">
                                <h3 class="card__title">
                                    <?= htmlspecialchars($taak['Titel']) ?>
                                </h3>
                                <span class="card__prio <?= $prioLabels[(int) $taak['Prioriteit']]['class'] ?? 'prio--3' ?>">
                                    <?= $prioLabels[(int) $taak['Prioriteit']]['label'] ?? 'Normaal' ?>
                                </span>
                            </header>

                            <?php if (!empty($taak['Beschrijving'])): ?>
                                <p class="card__body">
                                    <?= nl2br(htmlspecialchars($taak['Beschrijving'])) ?>
                                </p>
                            <?php endif; ?>

                            <footer class="card__foot">
                                <span class="card__meta">
                                    <?= htmlspecialchars($taak['ProjectTitel']) ?>
                                </span>
                                <?php if (!empty($taak['Deadline'])): ?>
                                    <span class="card__deadline">
                                        ⏰ <?= htmlspecialchars(date('d-m-Y', strtotime($taak['Deadline']))) ?>
                                    </span>
                                <?php endif; ?>

                                <form action="<?= base_url('/tasks/delete') ?>" method="post"
                                      class="card__delete"
                                      onsubmit="return confirm('Taak verwijderen?');">
                                    <input type="hidden" name="task_id" value="<?= (int) $taak['Id'] ?>">
                                    <button type="submit" class="card__delete-btn" aria-label="Verwijderen">×</button>
                                </form>
                            </footer>
                        </article>
                    <?php endforeach; ?>

                    <?php if (empty($board[$statusId])): ?>
                        <p class="kanban__empty">Geen taken in deze kolom.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Modal voor nieuwe taak -->
<div class="modal" id="task-modal" hidden>
    <div class="modal__backdrop" data-close="task-modal"></div>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="task-modal-title">
        <header class="modal__head">
            <h2 id="task-modal-title" class="modal__title">Nieuwe taak</h2>
            <button type="button" class="modal__close" data-close="task-modal" aria-label="Sluiten">×</button>
        </header>

        <form action="<?= base_url('/tasks') ?>" method="post" class="form">
            <label class="form__field">
                <span class="form__label">Titel</span>
                <input type="text" name="titel" required maxlength="120">
            </label>

            <label class="form__field">
                <span class="form__label">Beschrijving</span>
                <textarea name="beschrijving" rows="3"></textarea>
            </label>

            <div class="form__row">
                <label class="form__field">
                    <span class="form__label">Project</span>
                    <select name="project_id" required>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= (int) $p['Id'] ?>">
                                <?= htmlspecialchars($p['Titel']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label class="form__field form__field--narrow">
                    <span class="form__label">Prioriteit</span>
                    <select name="prioriteit">
                        <option value="1">1 — Hoogst</option>
                        <option value="2">2 — Hoog</option>
                        <option value="3" selected>3 — Normaal</option>
                        <option value="4">4 — Laag</option>
                        <option value="5">5 — Laagst</option>
                    </select>
                </label>

                <label class="form__field">
                    <span class="form__label">Deadline</span>
                    <input type="datetime-local" name="deadline">
                </label>
            </div>

            <div class="modal__actions">
                <button type="button" class="btn btn--ghost" data-close="task-modal">Annuleren</button>
                <button type="submit" class="btn btn--primary">Taak toevoegen</button>
            </div>
        </form>
    </div>
</div>
