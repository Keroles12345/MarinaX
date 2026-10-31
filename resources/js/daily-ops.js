import Sortable from 'sortablejs';

(() => {
        'use strict';

        /* ----------------------------------------------------------
         * Constants & element references
         * ---------------------------------------------------------- */
        const ASSIGNMENT_TYPES = ['boat', 'vehicle'];

        const planner        = document.getElementById('daily-ops');
        const operationList  = document.getElementById('operations-list');
        const message        = document.getElementById('planner-message');
        const saveButton     = document.getElementById('save-planner');
        const searchInput    = document.getElementById('operation-search');
        const assignedBadge  = document.getElementById('assigned-counter');
        const csrfToken      = document.querySelector('meta[name="csrf-token"]')?.content;

        let ctrlPressed = false;

        /* Full class strings (not concatenated) so Tailwind's scanner picks them up. */
        const MESSAGE_BASE    = 'inline-flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium ring-1';
        const MESSAGE_ERROR   = `${MESSAGE_BASE} bg-red-50 text-red-700 ring-red-200 message-error`;
        const MESSAGE_SUCCESS = `${MESSAGE_BASE} bg-emerald-50 text-emerald-700 ring-emerald-200 message-success`;
        const REMOVE_BUTTON_CLASS = 'remove-card -mr-1 grid h-6 w-6 place-items-center rounded-md text-lg leading-none text-slate-400 transition hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-300';

        /* ----------------------------------------------------------
         * UI helpers
         * ---------------------------------------------------------- */
        const setMessage = (text, isError = false) => {
            message.textContent = text;
            message.className = text ? (isError ? MESSAGE_ERROR : MESSAGE_SUCCESS) : 'hidden';
        };

        const operationIdExists = (list, operationId, ignoredCard = null) =>
            [...list.querySelectorAll('.operation-card')]
                .some(card => card !== ignoredCard && card.dataset.operationId === operationId);

        /** All assignment lists belonging to resource columns of a given type ('boat' | 'vehicle'). */
        const listsOfType = (type) =>
            [...document.querySelectorAll(`.resource-column[data-type="${type}"] .assignment-list`)];

        /**
         * An operation may only sit in ONE resource of a given type at a time
         * (e.g. one boat), but it's fine for the same operation to also sit in
         * a vehicle — those are different types and tracked independently.
         */
        const operationAssignedElsewhereOfType = (type, operationId, ignoredCard = null) =>
            listsOfType(type).some(list => operationIdExists(list, operationId, ignoredCard));

        const destinationAllows = (to, dragged) => {
            const column = to.closest('.resource-column');
            const type = column?.dataset.type;
            if (!type) return true;

            return column.dataset.regionId === dragged.dataset.regionId
                && !operationAssignedElsewhereOfType(type, dragged.dataset.operationId, dragged);
        };

        const addRemoveButton = (card) => {
            if (card.querySelector('.remove-card')) return;

            const topLine = card.querySelector('.card-topline');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = REMOVE_BUTTON_CLASS;
            button.textContent = '×';
            button.setAttribute('aria-label', `Remove operation ${card.dataset.operationId}`);
            topLine.appendChild(button);
        };

        const cardPassengers = (card) => Number(card.dataset.adults) + Number(card.dataset.children);

        /**
         * Recomputes the passenger count for every capacity-limited column and
         * shows/hides the empty-state hint on every column. Returns the columns
         * that are over capacity — capacity is never a hard block, an operation
         * can still be added past the limit (the user just gets a warning).
         */
        const updateCapacity = () => {
            const overCapacity = [];

            document.querySelectorAll('.resource-column').forEach(column => {
                const hasCards = column.querySelector('.operation-card') !== null;
                column.querySelector('.drop-hint')?.classList.toggle('hidden', hasCards);
            });

            document.querySelectorAll('.resource-column[data-capacity]').forEach(column => {
                const total = [...column.querySelectorAll('.operation-card')]
                    .reduce((sum, card) => sum + cardPassengers(card), 0);

                const capacity = Number(column.dataset.capacity);
                const isOverCapacity = total > capacity;

                column.querySelector('.capacity-counter').textContent = `${total}/${capacity}`;
                column.classList.toggle('capacity-exceeded', isOverCapacity);

                if (isOverCapacity) overCapacity.push(column);
            });

            return overCapacity;
        };

        const resourceName = (column) => column.querySelector('.resource-heading h3')?.textContent.trim() ?? 'resource';

        /** Warns (without blocking) when the column an operation was just added to is over capacity. */
        const warnIfOverCapacity = (column) => {
            const capacity = Number(column.dataset.capacity);
            const total = [...column.querySelectorAll('.operation-card')]
                .reduce((sum, card) => sum + cardPassengers(card), 0);

            if (total > capacity) {
                setMessage(`Warning: ${resourceName(column)} is over capacity (${total}/${capacity}).`, true);
            }
        };

        /** "3/6 assigned" badge — counts source cards that sit in at least one boat or vehicle. */
        const updateAssignedCounter = () => {
            if (!assignedBadge) return;

            const cards = [...operationList.querySelectorAll('.operation-card')];
            const assigned = cards.filter(card =>
                ASSIGNMENT_TYPES.some(type => card.classList.contains(`assigned-${type}`))
            ).length;

            assignedBadge.textContent = `${assigned}/${cards.length} assigned`;
        };

        /**
         * Reflects the current assignment state on the source card
         * shown in the "Bookings" list, so the user can see at a glance
         * whether an operation has been assigned to a boat, a vehicle, or nothing yet.
         *
         * @param {string} operationId
         */
        const updateOperationColor = (operationId) => {
            const originalCard = operationList.querySelector(
                `.operation-card[data-operation-id="${operationId}"]`
            );

            if (!originalCard) return;

            originalCard.classList.remove(...ASSIGNMENT_TYPES.map(t => `assigned-${t}`));

            ASSIGNMENT_TYPES.forEach(type => {
                const assigned = listsOfType(type).some(list => operationIdExists(list, operationId));
                if (assigned) originalCard.classList.add(`assigned-${type}`);
            });

            updateAssignedCounter();
        };

        /* ----------------------------------------------------------
         * Search (filters the bookings list only)
         * ---------------------------------------------------------- */
        searchInput?.addEventListener('input', () => {
            const query = searchInput.value.trim().toLowerCase();

            operationList.querySelectorAll('.operation-card').forEach(card => {
                card.classList.toggle('hidden', query !== '' && !(card.dataset.search ?? '').includes(query));
            });
        });

        /* ----------------------------------------------------------
         * Drag & drop (SortableJS)
         * ---------------------------------------------------------- */
        const group = {
            name: 'daily-ops-planner',
            pull(to, from, dragged, event) {
                if (from === operationList) return 'clone';

                const sourceType = from.closest('.resource-column')?.dataset.type;
                const targetType = to.closest('.resource-column')?.dataset.type;

                return sourceType !== targetType || event?.ctrlKey || ctrlPressed ? 'clone' : true;
            },
            put: true,
        };

        new Sortable(operationList, {
            group: { name: group.name, pull: 'clone', put: false },
            sort: false,
            animation: 150,
            draggable: '.operation-card:not(.cancelled-operation)',
        });

        document.querySelectorAll('.assignment-list').forEach(list => new Sortable(list, {
            group,
            animation: 150,
            draggable: '.operation-card:not(.cancelled-operation)',
            onMove: (event) => !event.dragged.classList.contains('cancelled-operation')
                && destinationAllows(event.to, event.dragged),
            onAdd(event) {
                if (event.item.classList.contains('cancelled-operation')) {
                    setMessage('Cancelled operations cannot be assigned.', true);
                    event.item.remove();
                    return;
                }

                if (!destinationAllows(event.to, event.item)) {
                    const type = event.to.closest('.resource-column')?.dataset.type ?? 'resource';
                    setMessage(`This operation is already assigned to another ${type}.`, true);
                    event.item.remove();
                    return;
                }

                addRemoveButton(event.item);

                const operationId = event.item.dataset.operationId;
                const column = event.to.closest('.resource-column');
                updateOperationColor(operationId);
                updateCapacity();

                if (column?.hasAttribute('data-capacity')) {
                    warnIfOverCapacity(column);
                }
            },
            onRemove(event) {
                updateCapacity();
                updateOperationColor(event.item.dataset.operationId);
            },
            onEnd: updateCapacity,
        }));

        // The page can load with saved assignments already in each boat.
        // Initialize the counters from those cards instead of leaving them at 0.
        updateCapacity();
        operationList.querySelectorAll('.operation-card').forEach(card => {
            updateOperationColor(card.dataset.operationId);
        });

        /* ----------------------------------------------------------
         * Ctrl-key tracking (used to force "clone" mode while dragging)
         * ---------------------------------------------------------- */
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Control') ctrlPressed = true;
        });
        document.addEventListener('keyup', (event) => {
            if (event.key === 'Control') ctrlPressed = false;
        });
        window.addEventListener('blur', () => { ctrlPressed = false; });

        /* ----------------------------------------------------------
         * Card removal
         * ---------------------------------------------------------- */
        planner.addEventListener('click', (event) => {
            const removeButton = event.target.closest('.remove-card');
            if (!removeButton) return;

            const card = event.target.closest('.operation-card');
            const operationId = card.dataset.operationId;

            card.remove();
            updateOperationColor(operationId);
            updateCapacity();
        });

        /* ----------------------------------------------------------
         * Save plan
         * ---------------------------------------------------------- */
        const collectAssignments = () =>
            [...document.querySelectorAll('.resource-column')].flatMap((column) => {
                const driverId = column.querySelector('.driver-select').value || null;

                return [...column.querySelectorAll('.operation-card')].map((card) => ({
                    operation_id: Number(card.dataset.operationId),
                    type: column.dataset.type,
                    resource_id: Number(column.dataset.resourceId),
                    driver_id: driverId ? Number(driverId) : null,
                }));
            });

        const savePlan = async () => {
            saveButton.disabled = true;
            setMessage('Saving…');

            try {
                const response = await fetch(planner.dataset.saveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({
                        date: planner.dataset.date,
                        assignments: collectAssignments(),
                    }),
                });

                const data = await response.json();

                if (!response.ok) {
                    throw new Error(data.message || 'Could not save the plan.');
                }

                setMessage('Plan saved successfully.');
            } catch (error) {
                setMessage(error.message || 'Could not save the plan.', true);
            } finally {
                saveButton.disabled = false;
            }
        };

        saveButton.addEventListener('click', savePlan);
    })();
