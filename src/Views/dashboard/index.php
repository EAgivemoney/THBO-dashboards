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
            <p class="dashboard__subtitle">Sleep taken tussen kolommen om hun status te wijzigen.</p>
        </div>
        <div class="dashboard__actions">
            <button type="button" class="btn btn--ghost" data-open="project-modal">&#128193; Projecten beheren</button>
            <button type="button" class="btn btn--primary" data-open="task-modal">+ Nieuwe taak</button>
        </div>
    </header>

    <?php if ($flash): ?>
        <div class="alert alert--success"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
        <div class="alert alert--error"><ul><?php foreach ($errors as $err): ?><li><?= htmlspecialchars($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <!-- Filterbalk -->
    <div class="filter-bar">
        <div class="filter-bar__group">
            <label class="filter-bar__label" for="filter-project">Project</label>
            <select class="filter-bar__select" id="filter-project">
                <option value="">Alle projecten</option>
                <?php foreach ($projects as $p): ?>
                    <option value="<?= (int) $p['Id'] ?>"><?= htmlspecialchars($p['Titel']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filter-bar__group">
            <label class="filter-bar__label" for="filter-prioriteit">Prioriteit</label>
            <select class="filter-bar__select" id="filter-prioriteit">
                <option value="">Alle prioriteiten</option>
                <option value="1">1 — Hoogst</option>
                <option value="2">2 — Hoog</option>
                <option value="3">3 — Normaal</option>
                <option value="4">4 — Laag</option>
                <option value="5">5 — Laagst</option>
            </select>
        </div>
        <button class="filter-bar__reset" id="filter-reset" hidden>Filters wissen</button>
    </div>

    <div class="kanban">
        <?php foreach ($kolommen as $statusId => $info): ?>
            <div class="kanban__col" data-status-id="<?= $statusId ?>" data-slug="<?= $info['slug'] ?>">
                <header class="kanban__col-head">
                    <h2 class="kanban__col-title"><?= $info['label'] ?></h2>
                    <span class="kanban__count"><?= count($board[$statusId] ?? []) ?></span>
                </header>
                <div class="kanban__list" data-droppable="1">
                    <?php foreach ($board[$statusId] ?? [] as $taak): ?>
                        <?php $dl = !empty($taak['Deadline']) ? date('Y-m-d\TH:i', strtotime($taak['Deadline'])) : ''; ?>
                        <article class="card"
                                 draggable="true"
                                 data-task-id="<?= (int) $taak['Id'] ?>"
                                 data-project-id="<?= (int) $taak['ProjectId'] ?>"
                                 data-titel="<?= htmlspecialchars($taak['Titel'], ENT_QUOTES) ?>"
                                 data-beschrijving="<?= htmlspecialchars($taak['Beschrijving'] ?? '', ENT_QUOTES) ?>"
                                 data-prioriteit="<?= (int) $taak['Prioriteit'] ?>"
                                 data-deadline="<?= htmlspecialchars($dl) ?>">
                            <header class="card__head">
                                <h3 class="card__title"><?= htmlspecialchars($taak['Titel']) ?></h3>
                                <span class="card__prio <?= $prioLabels[(int)$taak['Prioriteit']]['class'] ?? 'prio--3' ?>">
                                    <?= $prioLabels[(int)$taak['Prioriteit']]['label'] ?? 'Normaal' ?>
                                </span>
                            </header>
                            <?php if (!empty($taak['Beschrijving'])): ?>
                                <p class="card__body"><?= nl2br(htmlspecialchars($taak['Beschrijving'])) ?></p>
                            <?php endif; ?>
                            <footer class="card__foot">
                                <span class="card__meta"><?= htmlspecialchars($taak['ProjectTitel']) ?></span>
                                <?php if (!empty($taak['Deadline'])): ?>
                                    <span class="card__deadline">⏰ <?= htmlspecialchars(date('d-m-Y', strtotime($taak['Deadline']))) ?></span>
                                <?php endif; ?>
                                <form action="<?= base_url('/tasks/delete') ?>" method="post" class="card__delete" onsubmit="return confirm('Taak verwijderen?');">
                                    <input type="hidden" name="task_id" value="<?= (int) $taak['Id'] ?>">
                                    <button type="submit" class="card__delete-btn" aria-label="Verwijderen">&times;</button>
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

<!-- Modal: nieuwe taak -->
<div class="modal" id="task-modal" hidden>
    <div class="modal__backdrop" data-close="task-modal"></div>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="task-modal-title">
        <header class="modal__head">
            <h2 id="task-modal-title" class="modal__title">Nieuwe taak</h2>
            <button type="button" class="modal__close" data-close="task-modal" aria-label="Sluiten">&times;</button>
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
                            <option value="<?= (int) $p['Id'] ?>"><?= htmlspecialchars($p['Titel']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form__field">
                    <span class="form__label">Prioriteit</span>
                    <select name="prioriteit">
                        <option value="1">1 &mdash; Hoogst</option>
                        <option value="2">2 &mdash; Hoog</option>
                        <option value="3" selected>3 &mdash; Normaal</option>
                        <option value="4">4 &mdash; Laag</option>
                        <option value="5">5 &mdash; Laagst</option>
                    </select>
                </label>
            </div>
            <label class="form__field">
                <span class="form__label">Deadline</span>
                <input type="datetime-local" name="deadline">
            </label>
            <div class="modal__actions">
                <button type="button" class="btn btn--ghost" data-close="task-modal">Annuleren</button>
                <button type="submit" class="btn btn--primary">Taak toevoegen</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: taak bewerken -->
<div class="modal" id="edit-modal" hidden>
    <div class="modal__backdrop" data-close="edit-modal"></div>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="edit-modal-title">
        <header class="modal__head">
            <h2 id="edit-modal-title" class="modal__title">Taak bewerken</h2>
            <button type="button" class="modal__close" data-close="edit-modal" aria-label="Sluiten">&times;</button>
        </header>
        <form action="<?= base_url('/tasks/update') ?>" method="post" class="form">
            <input type="hidden" name="task_id" id="edit-task-id">
            <label class="form__field">
                <span class="form__label">Titel</span>
                <input type="text" name="titel" id="edit-titel" required maxlength="120">
            </label>
            <label class="form__field">
                <span class="form__label">Beschrijving</span>
                <textarea name="beschrijving" id="edit-beschrijving" rows="3"></textarea>
            </label>
            <div class="form__row">
                <label class="form__field">
                    <span class="form__label">Project</span>
                    <select name="project_id" id="edit-project-id" required>
                        <?php foreach ($projects as $p): ?>
                            <option value="<?= (int) $p['Id'] ?>"><?= htmlspecialchars($p['Titel']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="form__field">
                    <span class="form__label">Prioriteit</span>
                    <select name="prioriteit" id="edit-prioriteit">
                        <option value="1">1 &mdash; Hoogst</option>
                        <option value="2">2 &mdash; Hoog</option>
                        <option value="3">3 &mdash; Normaal</option>
                        <option value="4">4 &mdash; Laag</option>
                        <option value="5">5 &mdash; Laagst</option>
                    </select>
                </label>
            </div>
            <label class="form__field">
                <span class="form__label">Deadline</span>
                <input type="datetime-local" name="deadline" id="edit-deadline">
            </label>
            <div class="modal__actions">
                <button type="button" class="btn btn--ghost" data-close="edit-modal">Annuleren</button>
                <button type="submit" class="btn btn--primary">Opslaan</button>
            </div>
        </form>
    </div>
</div>
<!-- Modal: projecten beheren -->
<div class="modal" id="project-modal" hidden>
    <div class="modal__backdrop" data-close="project-modal"></div>
    <div class="modal__panel modal__panel--wide" role="dialog" aria-modal="true" aria-labelledby="project-modal-title">
        <header class="modal__head">
            <h2 id="project-modal-title" class="modal__title">Projecten beheren</h2>
            <button type="button" class="modal__close" data-close="project-modal" aria-label="Sluiten">&times;</button>
        </header>

        <!-- Bestaande projecten -->
        <div class="project-list">
            <?php if (empty($projectDetails)): ?>
                <p class="project-list__empty">Nog geen projecten. Maak er een aan hieronder.</p>
            <?php else: ?>
                <?php foreach ($projectDetails as $proj): ?>
                    <div class="project-item" data-project-id="<?= (int) $proj['Id'] ?>">
                        <div class="project-item__info">
                            <div class="project-item__header">
                                <strong class="project-item__name"><?= htmlspecialchars($proj['Titel']) ?></strong>
                                <div class="project-item__actions">
                                    <button type="button"
                                            class="btn btn--ghost btn--sm"
                                            data-open="edit-project-modal"
                                            data-project-id="<?= (int) $proj['Id'] ?>"
                                            data-project-titel="<?= htmlspecialchars($proj['Titel'], ENT_QUOTES) ?>"
                                            data-project-beschrijving="<?= htmlspecialchars($proj['Beschrijving'] ?? '', ENT_QUOTES) ?>">
                                        Bewerken
                                    </button>
                                    <form action="<?= base_url('/projects/delete') ?>" method="post"
                                          onsubmit="return confirm('Project &quot;<?= htmlspecialchars($proj['Titel'], ENT_QUOTES) ?>&quot; verwijderen? Alle taken worden ook verwijderd.');">
                                        <input type="hidden" name="project_id" value="<?= (int) $proj['Id'] ?>">
                                        <button type="submit" class="btn btn--danger btn--sm">Verwijderen</button>
                                    </form>
                                </div>
                            </div>
                            <?php if (!empty($proj['Beschrijving'])): ?>
                                <span class="project-item__desc"><?= htmlspecialchars($proj['Beschrijving']) ?></span>
                            <?php endif; ?>
                            <?php
                                $voortgang  = (int) $proj['Voortgang'];
                                $totaal     = (int) $proj['AantakTaken'];
                                $voltooid   = (int) $proj['AantalVoltooid'];
                            ?>
                            <div class="project-item__progress">
                                <div class="progress">
                                    <div class="progress__bar" style="width: <?= $voortgang ?>%"></div>
                                </div>
                                <span class="progress__label">
                                    <?php if ($totaal === 0): ?>
                                        Geen taken
                                    <?php else: ?>
                                        <?= $voltooid ?> / <?= $totaal ?> taken voltooid &mdash; <?= $voortgang ?>%
                                    <?php endif; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <hr class="modal__divider">

        <!-- Nieuw project formulier -->
        <h3 class="modal__subtitle">Nieuw project aanmaken</h3>
        <form action="<?= base_url('/projects') ?>" method="post" class="form">
            <label class="form__field">
                <span class="form__label">Projectnaam <span aria-hidden="true">*</span></span>
                <input type="text" name="titel" required maxlength="120" placeholder="bijv. Website redesign">
            </label>
            <label class="form__field">
                <span class="form__label">Beschrijving</span>
                <textarea name="beschrijving" rows="2" placeholder="Optionele omschrijving..."></textarea>
            </label>
            <div class="modal__actions">
                <button type="button" class="btn btn--ghost" data-close="project-modal">Annuleren</button>
                <button type="submit" class="btn btn--primary">Project aanmaken</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: project bewerken -->
<div class="modal" id="edit-project-modal" hidden>
    <div class="modal__backdrop" data-close="edit-project-modal"></div>
    <div class="modal__panel" role="dialog" aria-modal="true" aria-labelledby="edit-project-modal-title">
        <header class="modal__head">
            <h2 id="edit-project-modal-title" class="modal__title">Project bewerken</h2>
            <button type="button" class="modal__close" data-close="edit-project-modal" aria-label="Sluiten">&times;</button>
        </header>
        <form action="<?= base_url('/projects/update') ?>" method="post" class="form">
            <input type="hidden" name="project_id" id="edit-project-id">
            <label class="form__field">
                <span class="form__label">Projectnaam <span aria-hidden="true">*</span></span>
                <input type="text" name="titel" id="edit-project-titel" required maxlength="120">
            </label>
            <label class="form__field">
                <span class="form__label">Beschrijving</span>
                <textarea name="beschrijving" id="edit-project-beschrijving" rows="2"></textarea>
            </label>
            <div class="modal__actions">
                <button type="button" class="btn btn--ghost" data-close="edit-project-modal">Annuleren</button>
                <button type="submit" class="btn btn--primary">Opslaan</button>
            </div>
        </form>
    </div>
</div>