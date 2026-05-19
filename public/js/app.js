/**
 * THBO Dashboards - frontend interacties
 * 
 * Verzorgt:
 *   1. Modal open/sluiten (data-open / data-close)
 *   2. Drag & drop op het kanban-bord, met pTCH naar /tasks/status
 */
(function () {
    'use strict';

    // ----- helpers -----------------------------------------------------------
    /**
     * base URL bepalen op basis van het pad van app.js zelf,
     * zodat de site ook werkt als WAMP onder /TBHBO-dashboards/public draait.
     */
    function getBaseUrl() {
        var script = document.currentScript ||
            Array.prototype.slice.call(document.scripts).pop();
        if (!script) return '';
        // app.js zit in /js/, dus 2x naar boven = base
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
            var modalC = document.getElementById(closeId);
            if (modalC) modalC.hidden = true;
        }
    });

    // ESC sluit alle open modals
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal:not([hidden])').forEach(function (m) {
                m.hidden = true;
            });
        }
    });

    // ----- drag & drop -------------------------------------------------------
    var draggedCard = null;
    var wasDragged    = false;

    document.querySelectorAll('.card[draggable="true"]').forEach(function (card) {
        card.addEventListener('dragstart', function (e) {
            draggedCard = card;
            wasDragged = true;
            card.classList.add('card--dragging');
            // Firefox heeft een dataTransfer payload nodig om drag te starten
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

    // ----- taak bewerken (klik op kaart) ------------------------------------
    document.querySelectorAll('.card[draggable="true"]').forEach(function (card) {
        card.addEventListener('click', function (e) {
            // Negeer klik als het een drag was of als de delete-knop geklikt is
            if (wasDragged) return;
            if (e.target.closest('.card__delete')) return;

            var modal = document.getElementById('edit-modal');
            if (!modal) return;

            modal.querySelector('#edit-task-id').value        = card.dataset.taskId    || '';
            modal.querySelector('#edit-titel').value          = card.dataset.titel      || '';
            modal.querySelector('#edit-beschrijving').value   = card.dataset.beschrijving || '';
            modal.querySelector('#edit-deadline').value       = card.dataset.deadline   || '';

            var prio = modal.querySelector('#edit-prioriteit');
            if (prio) prio.value = card.dataset.prioriteit || '3';

            var proj = modal.querySelector('#edit-project-id');
            if (proj) proj.value = card.dataset.projectId || '';

            modal.hidden = false;
        });
    });

    document.querySelectorAll('.kanban__list[data-droppable="1"]').forEach(function (list) {
        list.addEventListener('dragover', function (e) {
            e.preventDefault(); // nodig om drop toe te staan
            list.classList.add('kanban__list--over');
        });

        list.addEventListener('dragleave', function () {
            list.classList.remove('kanban__list--over');
        });

        list.addEventListener('drop', function (e) {
            e.preventDefault();
            list.classList.remove('kanban__list--over');

            if (!draggedCard) return;

            var col       = list.closest('.kanban__col');
            if (!col) return;
            var newStatus = parseInt(col.dataset.statusId, 10);
            var taskId    = parseInt(draggedCard.dataset.taskId, 10);

            if (!newStatus || !taskId) return;

            // Onthoud de bronkolom vóór het verplaatsen
            var sourceList = draggedCard.parentElement;

            // Optimistisch verplaatsen - server bevestigt later.
            var emptyMsg = list.querySelector('.kanban__empty');
            list.appendChild(draggedCard);
            if (emptyMsg) emptyMsg.remove();

            // Als de bronkolom nu leeg is, toon het lege-kolom bericht terug
            if (sourceList && sourceList !== list && !sourceList.querySelector('.card')) {
                var p = document.createElement('p');
                p.className = 'kanban__empty';
                p.textContent = 'Geen taken in deze kolom.';
                sourceList.appendChild(p);
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
                    alert('De taak kon niet worden bijgewerkt. Vernieuw de pagina en probeer opnieuw.');
                }
            }).catch(function () {
                alert('Netwerkfout - probeer het opnieuw.');
            });
        });
    });

    function updateColumnCounts() {
        document.querySelectorAll('.kanban__col').forEach(function (col) {
            var n = col.querySelectorAll('.card').length;
            var c = col.querySelector('.kanban__count');
            if (c) c.textContent = n;
        });
    }

    function updateProjectProgress(projectId) {
        if (!projectId) return;

        var alleKaarten = document.querySelectorAll('.card[data-project-id="' + projectId + '"]');
        var totaal      = alleKaarten.length;

        var doneCol   = document.querySelector('.kanban__col[data-status-id="3"]');
        var voltooid  = doneCol
            ? doneCol.querySelectorAll('.card[data-project-id="' + projectId + '"]').length
            : 0;

        var percentage = totaal > 0 ? Math.round((voltooid / totaal) * 100) : 0;

        // Balk en label in de project modal bijwerken
        var projectItem = document.querySelector('.project-item[data-project-id="' + projectId + '"]');
        if (!projectItem) return;

        var balk  = projectItem.querySelector('.progress__bar');
        var label = projectItem.querySelector('.progress__label');

        if (balk)  balk.style.width = percentage + '%';
        if (label) label.textContent = totaal === 0
            ? 'Geen taken'
            : voltooid + ' / ' + totaal + ' taken voltooid \u2014 ' + percentage + '%';
    }
})();
/* ── Project bewerken modal ──────────────────────────────── */
(function () {
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-open="edit-project-modal"]');
        if (!btn) return;

        var modal     = document.getElementById('edit-project-modal');
        var idInput   = document.getElementById('edit-project-id');
        var titelInput     = document.getElementById('edit-project-titel');
        var beschrInput    = document.getElementById('edit-project-beschrijving');

        if (idInput)        idInput.value        = btn.dataset.projectId          || '';
        if (titelInput)     titelInput.value     = btn.dataset.projectTitel       || '';
        if (beschrInput)    beschrInput.value    = btn.dataset.projectBeschrijving || '';

        if (modal) {
            // Sluit het project-overzicht modal eerst
            var overzicht = document.getElementById('project-modal');
            if (overzicht) overzicht.hidden = true;

            modal.hidden = false;
            if (titelInput) titelInput.focus();
        }
    });
})();

/* ── Auto-dismiss alerts ─────────────────────────────────── */
(function () {
    var DELAY    = 4000; // ms voor het starten van de fade
    var DURATION = 500;  // moet overeenkomen met de CSS transitie

    document.querySelectorAll('.alert').forEach(function (el) {
        setTimeout(function () {
            el.classList.add('alert--hiding');
            setTimeout(function () {
                el.remove();
            }, DURATION);
        }, DELAY);
    });
})();

/* ── Filteren op project / prioriteit ────────────────────── */
(function () {
    var selProject   = document.getElementById('filter-project');
    var selPrioriteit = document.getElementById('filter-prioriteit');
    var btnReset     = document.getElementById('filter-reset');

    if (!selProject || !selPrioriteit) return;

    function applyFilters() {
        var projectVal   = selProject.value;
        var prioriteitVal = selPrioriteit.value;
        var actief       = projectVal !== '' || prioriteitVal !== '';

        // Reset knop tonen/verbergen
        if (btnReset) btnReset.hidden = !actief;

        document.querySelectorAll('.kanban__col').forEach(function (col) {
            var zichtbaar = 0;

            col.querySelectorAll('.card').forEach(function (card) {
                var matchProject    = projectVal     === '' || card.dataset.projectId  === projectVal;
                var matchPrioriteit = prioriteitVal  === '' || card.dataset.prioriteit === prioriteitVal;
                var tonen           = matchProject && matchPrioriteit;

                card.style.display = tonen ? '' : 'none';
                if (tonen) zichtbaar++;
            });

            var totaalKaarten    = col.querySelectorAll('.card').length;

            // Originele "Geen taken in deze kolom." — toon alleen als er écht geen kaarten zijn
            var origineel = col.querySelector('.kanban__empty:not(.kanban__empty--filter)');
            if (origineel) {
                origineel.hidden = totaalKaarten > 0;
            }

            // Filter melding — toon alleen als er wél kaarten zijn maar geen zichtbare
            var filterMelding = col.querySelector('.kanban__empty--filter');
            if (totaalKaarten > 0 && zichtbaar === 0) {
                if (!filterMelding) {
                    filterMelding = document.createElement('p');
                    filterMelding.className = 'kanban__empty kanban__empty--filter';
                    filterMelding.textContent = 'Geen taken met dit filter.';
                    col.querySelector('.kanban__list').appendChild(filterMelding);
                }
                filterMelding.hidden = false;
            } else if (filterMelding) {
                filterMelding.hidden = true;
            }

            // Kolomteller bijwerken naar zichtbare kaarten
            var teller = col.querySelector('.kanban__count');
            if (teller) teller.textContent = zichtbaar;
        });
    }

    selProject.addEventListener('change', applyFilters);
    selPrioriteit.addEventListener('change', applyFilters);

    if (btnReset) {
        btnReset.addEventListener('click', function () {
            selProject.value    = '';
            selPrioriteit.value = '';
            applyFilters();
        });
    }
})();