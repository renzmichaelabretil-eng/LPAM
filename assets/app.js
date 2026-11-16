document.querySelectorAll('[data-confirm]').forEach(button => button.addEventListener('click', event => { if (!confirm(button.dataset.confirm)) event.preventDefault(); }));

// Every same-origin form submission carries the session-bound CSRF token.
document.querySelectorAll('form[method="post"]').forEach(form => {
    if (!form.querySelector('input[name="csrf_token"]') && window.csrfToken) {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'csrf_token';
        input.value = window.csrfToken;
        form.prepend(input);
    }
});

document.querySelectorAll('[data-password-toggle]').forEach(button => button.addEventListener('click', () => {
    const input = document.querySelector(button.dataset.passwordToggle);
    if (!input) return;
    const showing = input.type === 'text';
    input.type = showing ? 'password' : 'text';
    button.textContent = showing ? 'Show' : 'Hide';
    button.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
}));

// Reusable administrator CRUD dialogs.
const administratorModal = document.getElementById('administrator-modal');
const deleteAdministratorModal = document.getElementById('delete-administrator-modal');
document.querySelectorAll('[data-modal-close]').forEach(button => button.addEventListener('click', () => button.closest('dialog')?.close()));
document.querySelectorAll('[data-admin-modal]').forEach(button => button.addEventListener('click', () => {
    if (!administratorModal) return;
    const edit = button.dataset.adminModal === 'edit';
    administratorModal.querySelector('#administrator-action').value = edit ? 'update' : 'create';
    administratorModal.querySelector('#administrator-id').value = edit ? button.dataset.id : '';
    administratorModal.querySelector('#administrator-name').value = edit ? button.dataset.name : '';
    administratorModal.querySelector('#administrator-email').value = edit ? button.dataset.email : '';
    administratorModal.querySelector('#administrator-role').value = edit ? button.dataset.role : 'sport_admin';
    administratorModal.querySelector('#administrator-sport').value = edit ? button.dataset.sport : '';
    const areaSelect = administratorModal.querySelector('#administrator-area');
    if (areaSelect) areaSelect.value = edit ? (button.dataset.area || '') : '';
    const password = administratorModal.querySelector('#administrator-password');
    password.value = '';
    password.type = 'password';
    const passwordToggle = administratorModal.querySelector('[data-password-toggle]');
    if (passwordToggle) {
        passwordToggle.textContent = 'Show';
        passwordToggle.setAttribute('aria-label', 'Show password');
    }
    password.required = !edit;
    administratorModal.querySelector('#administrator-kicker').textContent = edit ? 'Account settings' : 'New account';
    administratorModal.querySelector('#administrator-title').textContent = edit ? 'Edit administrator' : 'Create administrator';
    administratorModal.querySelector('#administrator-help').textContent = edit ? 'Leave the password blank to keep the current password.' : 'Create the account, then share the temporary password privately.';
    administratorModal.querySelector('#administrator-password-label').textContent = edit ? 'New password (optional)' : 'Temporary password';
    administratorModal.querySelector('#administrator-submit').textContent = edit ? 'Save changes' : 'Create administrator';
    administratorModal.showModal();
}));
document.querySelectorAll('[data-admin-delete]').forEach(button => button.addEventListener('click', () => {
    if (!deleteAdministratorModal) return;
    deleteAdministratorModal.querySelector('#delete-administrator-id').value = button.dataset.adminDelete;
    deleteAdministratorModal.querySelector('#delete-administrator-name').textContent = button.dataset.name;
    deleteAdministratorModal.showModal();
}));

const sportModal = document.getElementById('sport-modal');
document.querySelectorAll('[data-sport-modal]').forEach(button => button.addEventListener('click', () => {
    if (!sportModal) return;
    const edit = button.dataset.sportModal === 'edit';
    sportModal.querySelector('#sport-action').value = edit ? 'update_sport' : 'sport';
    sportModal.querySelector('#sport-id').value = edit ? button.dataset.id : '';
    sportModal.querySelector('#sport-name').value = edit ? button.dataset.name : '';
    sportModal.querySelector('#sport-icon').value = edit ? button.dataset.icon : '🏅';
    sportModal.querySelector('#sport-coordinator').value = edit ? button.dataset.coordinator : '';
    sportModal.querySelector('#sport-description').value = edit ? button.dataset.description : '';
    sportModal.querySelector('#sport-kicker').textContent = edit ? 'Sport settings' : 'New sport';
    sportModal.querySelector('#sport-title').textContent = edit ? 'Edit sport' : 'Add sport';
    sportModal.querySelector('#sport-submit').textContent = edit ? 'Save changes' : 'Add sport';
    sportModal.showModal();
}));

const eventModal = document.getElementById('event-modal');
document.querySelectorAll('[data-event-modal]').forEach(button => button.addEventListener('click', () => {
    if (!eventModal) return;
    const edit = button.dataset.eventModal === 'edit';
    eventModal.querySelector('#event-action').value = edit ? 'update_event' : 'event';
    eventModal.querySelector('#event-id').value = edit ? button.dataset.id : '';
    eventModal.querySelector('#event-sport').value = edit ? button.dataset.sport : '';
    eventModal.querySelector('#event-name').value = edit ? button.dataset.name : '';
    eventModal.querySelector('#event-division').value = edit ? button.dataset.division : '';
    eventModal.querySelector('#event-venue').value = edit ? button.dataset.venue : '';
    eventModal.querySelector('#event-scheduled').value = edit ? button.dataset.scheduled : '';
    eventModal.querySelector('#event-kicker').textContent = edit ? 'Event settings' : 'New event';
    eventModal.querySelector('#event-title').textContent = edit ? 'Edit event' : 'Add event';
    eventModal.querySelector('#event-submit').textContent = edit ? 'Save changes' : 'Add event';
    eventModal.showModal();
}));

const sportsEventDeleteModal = document.getElementById('delete-sport-event-modal');
document.querySelectorAll('[data-sport-delete], [data-event-delete]').forEach(button => button.addEventListener('click', () => {
    if (!sportsEventDeleteModal) return;
    const deletingSport = Boolean(button.dataset.sportDelete);
    const name = button.dataset.name;
    sportsEventDeleteModal.querySelector('#delete-sport-event-action').value = deletingSport ? 'delete_sport' : 'delete_event';
    sportsEventDeleteModal.querySelector('#delete-sport-event-id').value = deletingSport ? button.dataset.sportDelete : button.dataset.eventDelete;
    sportsEventDeleteModal.querySelector('#delete-sport-event-title').textContent = `Delete ${deletingSport ? 'sport' : 'event'}?`;
    sportsEventDeleteModal.querySelector('#delete-sport-event-message').textContent = `Remove “${name}”? This action cannot be undone.`;
    sportsEventDeleteModal.showModal();
}));

const areaModal = document.getElementById('area-modal');
document.querySelectorAll('[data-area-modal]').forEach(button => button.addEventListener('click', () => {
    if (!areaModal) return;
    const edit = button.dataset.areaModal === 'edit';
    areaModal.querySelector('#area-id').value = edit ? button.dataset.id : '';
    areaModal.querySelector('#area-name').value = edit ? button.dataset.name : '';
    areaModal.querySelector('#area-coordinator').value = edit ? button.dataset.coordinator : '';
    areaModal.querySelector('#area-districts').value = edit ? button.dataset.districts : '';
    areaModal.querySelector('#area-kicker').textContent = edit ? 'Delegation settings' : 'New delegation';
    areaModal.querySelector('#area-title').textContent = edit ? 'Edit area' : 'Add area';
    areaModal.querySelector('#area-submit').textContent = edit ? 'Save changes' : 'Add area';
    areaModal.showModal();
}));

const entryModal = document.getElementById('entry-modal');
document.querySelectorAll('[data-entry-modal]').forEach(button => button.addEventListener('click', () => {
    if (!entryModal) return;
    entryModal.querySelector('#entry-event-id').value = button.dataset.event;
    entryModal.querySelector('#entry-event-label').textContent = button.dataset.label;
    entryModal.showModal();
}));

// Games countdown: switches to a live message automatically once the meet starts.
document.querySelectorAll('[data-games-start]').forEach(widget => {
    const start = new Date(widget.dataset.gamesStart).getTime();
    const updateCountdown = () => {
        const remaining = start - Date.now();
        if (remaining <= 0) {
            widget.innerHTML = '<span class="countdown-label">Leyte Provincial Athletic Meet</span><strong>The Games are underway</strong>';
            return;
        }
        const totalMinutes = Math.floor(remaining / 60000);
        const values = { days: Math.floor(totalMinutes / 1440), hours: Math.floor(totalMinutes % 1440 / 60), minutes: totalMinutes % 60 };
        Object.entries(values).forEach(([unit, value]) => {
            const target = widget.querySelector(`[data-unit="${unit}"]`);
            if (target) target.textContent = String(value).padStart(2, '0');
        });
    };
    updateCountdown();
    setInterval(updateCountdown, 30000);
});

// Count-up animation for leaderboard numbers (skipped when the visitor prefers reduced motion).
if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    document.querySelectorAll('[data-count]').forEach(el => {
        const target = parseInt(el.dataset.count, 10) || 0;
        if (target === 0) return;
        const row = el.closest('tr');
        const delay = row ? (parseInt(getComputedStyle(row).getPropertyValue('--i'), 10) || 0) * 70 + 250 : 250;
        const start = performance.now() + delay;
        el.textContent = '0';
        const tick = now => {
            const t = Math.min(Math.max((now - start) / 900, 0), 1);
            el.textContent = Math.round(target * (1 - Math.pow(1 - t, 3)));
            if (t < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    });
}
