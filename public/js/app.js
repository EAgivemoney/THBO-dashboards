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
})();