/* ── Translations ────────────────────────────────────────────
 * The texts come from resources/lang/<code>.php (group "js") and are passed
 * on by layout/footer.php as window.I18N.
 *   THBO_T('network_error')
 *   THBO_T('progress', { done: 2, total: 5, percent: 40 })   // :done, :total, :percent
 */
window.THBO_T = function (key, vars) {
    var text = (window.I18N && window.I18N[key]) || key;
    if (vars) {
        Object.keys(vars).forEach(function (name) {
            text = text.split(':' + name).join(String(vars[name]));
        });
    }
    return text;
};

/**
 * THBO Dashboards - front-end interactions
 *
 * Handles:
 *   1. Opening/closing modals (data-open / data-close)
 *   2. Drag & drop on the kanban board, with a POST to /tasks/status
 */
(function () {
    'use strict';

    // ----- helpers -----------------------------------------------------------
    /**
     * Determine the base URL from the path of app.js itself,
     * so the site also works when WAMP runs it under /THBO-dashboards/public.
     */
    function getBaseUrl() {
        var script = document.currentScript ||
            Array.prototype.slice.call(document.scripts).pop();
        if (!script) return '';
        // app.js lives in /js/, so strip that part to get the base
        var src = script.getAttribute('src') || '';
        return src.replace(/\/js\/app\.js.*$/, '');
    }

    var BASE = getBaseUrl();
    // ----- modal -------------------------------------------------------------
    document.addEventListener('click', function (e) {
        var openId = e.target.getAttribute && e.target.getAttribute('data-open');
        var closeId = e.target.getAttribute && e.target.getAttribute('data-close');

        if (openId) {
            var modal = document.getElementById(openId);
            if (modal) modal.hidden = false;
        }
        if (closeId) {
            var modalToClose = document.getElementById(closeId);
            if (modalToClose) modalToClose.hidden = true;
        }
    });

    // ESC closes all open modals
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal:not([hidden])').forEach(function (m) {
                m.hidden = true;
            });
        }
    });

    // ----- drag & drop -------------------------------------------------------
    var draggedCard = null;
    var wasDragged  = false;

    document.querySelectorAll('.card[draggable="true"]').forEach(function (card) {
        card.addEventListener('dragstart', function (e) {
            draggedCard = card;
            wasDragged = true;
            card.classList.add('card--dragging');
            // Firefox needs a dataTransfer payload to start a drag
            if (e.dataTransfer) {
                e.dataTransfer.effectAllowed = 'move';
                e.dataTransfer.setData('text/plain', card.dataset.taskId || '');
            }
        });

        card.addEventListener('dragend', function () {
            card.classList.remove('card--dragging');
            draggedCard = null;
            setTimeout(function () { wasDragged = false; }, 0);
            document.querySelectorAll('.kanban__list--over').forEach(function (l) {
                l.classList.remove('kanban__list--over');
            });
        });
    });

    // ----- edit task (click on a card) ---------------------------------------
    document.querySelectorAll('.card[draggable="true"]').forEach(function (card) {
        card.addEventListener('click', function (e) {
            // Ignore the click if it was a drag or if the delete button was clicked
            if (wasDragged) return;
            if (e.target.closest('.card__delete')) return;

            var modal = document.getElementById('edit-modal');
            if (!modal) return;

            modal.querySelector('#edit-task-id').value     = card.dataset.taskId      || '';
            modal.querySelector('#edit-title').value       = card.dataset.title       || '';
            modal.querySelector('#edit-description').value = card.dataset.description || '';
            modal.querySelector('#edit-deadline').value    = card.dataset.deadline    || '';

            var priority = modal.querySelector('#edit-priority');
            if (priority) priority.value = card.dataset.priority || '3';

            var project = modal.querySelector('#edit-task-project-id');
            if (project) project.value = card.dataset.projectId || '';

            modal.hidden = false;
        });
    });

    document.querySelectorAll('.kanban__list[data-droppable="1"]').forEach(function (list) {
        list.addEventListener('dragover', function (e) {
            e.preventDefault(); // required to allow a drop
            list.classList.add('kanban__list--over');
        });

        list.addEventListener('dragleave', function () {
            list.classList.remove('kanban__list--over');
        });

        list.addEventListener('drop', function (e) {
            e.preventDefault();
            list.classList.remove('kanban__list--over');

            if (!draggedCard) return;

            var column = list.closest('.kanban__col');
            if (!column) return;
            var newStatus = parseInt(column.dataset.statusId, 10);
            var taskId    = parseInt(draggedCard.dataset.taskId, 10);

            if (!newStatus || !taskId) return;

            // Remember the source column before moving
            var sourceList = draggedCard.parentElement;

            // Move optimistically - the server confirms later.
            var emptyMessage = list.querySelector('.kanban__empty');
            list.appendChild(draggedCard);
            if (emptyMessage) emptyMessage.remove();

            // If the source column is now empty, show the empty-column message again
            if (sourceList && sourceList !== list && !sourceList.querySelector('.card')) {
                var message = document.createElement('p');
                message.className = 'kanban__empty';
                message.textContent = THBO_T('empty_column');
                sourceList.appendChild(message);
            }

            updateColumnCounts();
            updateProjectProgress(draggedCard.dataset.projectId);

            fetch(BASE + '/tasks/status', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                credentials: 'same-origin',
                body: JSON.stringify({ task_id: taskId, status_id: newStatus })
            }).then(function (res) {
                return res.json().catch(function () { return { ok: false }; });
            }).then(function (data) {
                if (!data || !data.ok) {
                    alert(THBO_T('task_update_failed'));
                }
            }).catch(function () {
                alert(THBO_T('network_error'));
            });
        });
    });

    function updateColumnCounts() {
        document.querySelectorAll('.kanban__col').forEach(function (column) {
            var count = column.querySelectorAll('.card').length;
            var badge = column.querySelector('.kanban__count');
            if (badge) badge.textContent = count;
        });
    }

    function updateProjectProgress(projectId) {
        if (!projectId) return;

        var allCards = document.querySelectorAll('.card[data-project-id="' + projectId + '"]');
        var total    = allCards.length;

        var doneColumn = document.querySelector('.kanban__col[data-status-id="3"]');
        var completed  = doneColumn
            ? doneColumn.querySelectorAll('.card[data-project-id="' + projectId + '"]').length
            : 0;

        var percentage = total > 0 ? Math.round((completed / total) * 100) : 0;

        // Update the bar and label in the project modal
        var projectItem = document.querySelector('.project-item[data-project-id="' + projectId + '"]');
        if (!projectItem) return;

        var bar   = projectItem.querySelector('.progress__bar');
        var label = projectItem.querySelector('.progress__label');

        if (bar)   bar.style.width = percentage + '%';
        if (label) label.textContent = total === 0
            ? THBO_T('no_tasks')
            : THBO_T('progress', { done: completed, total: total, percent: percentage });
    }
})();
/* ── Edit project modal ──────────────────────────────────── */
(function () {
    document.addEventListener('click', function (e) {
        var button = e.target.closest('[data-open="edit-project-modal"]');
        if (!button) return;

        var modal            = document.getElementById('edit-project-modal');
        var idInput          = document.getElementById('edit-project-id');
        var titleInput       = document.getElementById('edit-project-title');
        var descriptionInput = document.getElementById('edit-project-description');

        if (idInput)          idInput.value          = button.dataset.projectId          || '';
        if (titleInput)       titleInput.value       = button.dataset.projectTitle       || '';
        if (descriptionInput) descriptionInput.value = button.dataset.projectDescription || '';

        if (modal) {
            // Close the project overview modal first
            var overview = document.getElementById('project-modal');
            if (overview) overview.hidden = true;

            modal.hidden = false;
            if (titleInput) titleInput.focus();
        }
    });
})();

/* ── Auto-dismiss alerts ─────────────────────────────────── */
(function () {
    var DELAY    = 4000; // ms before the fade starts
    var DURATION = 500;  // must match the CSS transition

    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.classList.add('alert--hiding');
            setTimeout(function () {
                el.remove();
            }, DURATION);
        }, DELAY);
    });
})();

/* ── Filter by project / priority ────────────────────────── */
(function () {
    var projectSelect  = document.getElementById('filter-project');
    var prioritySelect = document.getElementById('filter-priority');
    var resetButton    = document.getElementById('filter-reset');

    if (!projectSelect || !prioritySelect) return;

    function applyFilters() {
        var projectValue  = projectSelect.value;
        var priorityValue = prioritySelect.value;
        var isFiltering   = projectValue !== '' || priorityValue !== '';

        // Show/hide the reset button
        if (resetButton) resetButton.hidden = !isFiltering;

        document.querySelectorAll('.kanban__col').forEach(function (column) {
            var visible = 0;

            column.querySelectorAll('.card').forEach(function (card) {
                var matchesProject  = projectValue  === '' || card.dataset.projectId === projectValue;
                var matchesPriority = priorityValue === '' || card.dataset.priority  === priorityValue;
                var show            = matchesProject && matchesPriority;

                card.style.display = show ? '' : 'none';
                if (show) visible++;
            });

            var totalCards = column.querySelectorAll('.card').length;

            // Original "No tasks in this column." message — only show it if there really are no cards
            var originalMessage = column.querySelector('.kanban__empty:not(.kanban__empty--filter)');
            if (originalMessage) {
                originalMessage.hidden = totalCards > 0;
            }

            // Filter message — only show it if there are cards but none are visible
            var filterMessage = column.querySelector('.kanban__empty--filter');
            if (totalCards > 0 && visible === 0) {
                if (!filterMessage) {
                    filterMessage = document.createElement('p');
                    filterMessage.className = 'kanban__empty kanban__empty--filter';
                    filterMessage.textContent = THBO_T('empty_filter');
                    column.querySelector('.kanban__list').appendChild(filterMessage);
                }
                filterMessage.hidden = false;
            } else if (filterMessage) {
                filterMessage.hidden = true;
            }

            // Update the column counter to the number of visible cards
            var counter = column.querySelector('.kanban__count');
            if (counter) counter.textContent = visible;
        });
    }

    projectSelect.addEventListener('change', applyFilters);
    prioritySelect.addEventListener('change', applyFilters);

    if (resetButton) {
        resetButton.addEventListener('click', function () {
            projectSelect.value  = '';
            prioritySelect.value = '';
            applyFilters();
        });
    }
})();
/* ── Language picker (details/summary): close on outside click or Escape ── */
(function () {
    var menu = document.querySelector('.lang-switch');
    if (!menu) return;

    document.addEventListener('click', function (e) {
        if (menu.open && !menu.contains(e.target)) menu.open = false;
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && menu.open) {
            menu.open = false;
            menu.querySelector('summary').focus();
        }
    });
})();
