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

// Public result photo details modal.
const resultPhotoModal = document.getElementById('result-photo-modal');
document.querySelectorAll('[data-result-photo]').forEach(button => button.addEventListener('click', () => {
    if (!resultPhotoModal) return;
    const set = (selector, value) => { const el = resultPhotoModal.querySelector(selector); if (el) el.textContent = value || '—'; };
    const image = resultPhotoModal.querySelector('#result-modal-image');
    image.src = button.dataset.image;
    image.alt = button.dataset.player || 'Winner';
    set('#result-modal-medal', `${button.dataset.medal} medal`);
    set('#result-modal-title', button.dataset.player);
    set('#result-modal-team', button.dataset.team);
    set('#result-modal-event', button.dataset.event);
    set('#result-modal-sport', button.dataset.sport);
    set('#result-modal-venue', button.dataset.venue);
    resultPhotoModal.showModal();
}));
resultPhotoModal?.addEventListener('click', event => {
    if (event.target === resultPhotoModal) resultPhotoModal.close();
});

// Search, paging, and show/hide controls for public content lists.
document.querySelectorAll('[data-content-controls]').forEach(controls => {
    const list = document.querySelector(controls.dataset.target);
    if (!list) return;
    const items = Array.from(list.querySelectorAll(controls.dataset.item));
    const search = controls.querySelector('input[type="search"]');
    const toggle = controls.querySelector('[data-list-toggle]');
    const count = controls.querySelector('[data-content-count]');
    const pageSize = parseInt(controls.dataset.pageSize, 10) || 8;
    const pagination = document.createElement('nav');
    pagination.className = 'pagination';
    pagination.setAttribute('aria-label', 'List pages');
    controls.insertAdjacentElement('afterend', pagination);
    let page = 1;
    let open = true;

    const render = () => {
        const term = (search?.value || '').trim().toLowerCase();
        const matched = items.filter(item => item.textContent.toLowerCase().includes(term));
        const pages = Math.max(1, Math.ceil(matched.length / pageSize));
        page = Math.min(page, pages);
        list.hidden = !open;
        pagination.hidden = !open || pages <= 1;
        items.forEach(item => { item.hidden = !open || !matched.includes(item) || matched.indexOf(item) < (page - 1) * pageSize || matched.indexOf(item) >= page * pageSize; });
        if (count) count.textContent = open ? `${matched.length} item${matched.length === 1 ? '' : 's'} shown` : 'List hidden';
        if (toggle) {
            toggle.textContent = open ? `Hide ${toggle.dataset.label || 'list'}` : `Show ${toggle.dataset.label || 'list'}`;
            toggle.setAttribute('aria-expanded', String(open));
        }
        pagination.replaceChildren();
        if (!pagination.hidden) {
            const addButton = (label, target, disabled = false, active = false) => {
                const button = document.createElement('button');
                button.type = 'button'; button.textContent = label; button.disabled = disabled; button.classList.toggle('active', active);
                button.addEventListener('click', () => { page = target; render(); list.scrollIntoView({ behavior: 'smooth', block: 'start' }); });
                pagination.append(button);
            };
            addButton('Previous', page - 1, page === 1);
            for (let number = 1; number <= pages; number++) addButton(String(number), number, false, number === page);
            addButton('Next', page + 1, page === pages);
        }
    };
    search?.addEventListener('input', () => { page = 1; render(); });
    controls.querySelector('[data-search-button]')?.addEventListener('click', () => { page = 1; render(); });
    toggle?.addEventListener('click', () => { open = !open; render(); });
    render();
});
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
const navToggle = document.querySelector('.nav-toggle');
const siteNavigation = document.getElementById('site-navigation');
if (navToggle && siteNavigation) {
    navToggle.addEventListener('click', () => {
        const open = siteNavigation.classList.toggle('is-open');
        navToggle.classList.toggle('is-open', open);
        navToggle.setAttribute('aria-expanded', String(open));
        navToggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
    });
    siteNavigation.querySelectorAll('a').forEach(link => link.addEventListener('click', () => {
        siteNavigation.classList.remove('is-open');
        navToggle.classList.remove('is-open');
        navToggle.setAttribute('aria-expanded', 'false');
        navToggle.setAttribute('aria-label', 'Open navigation menu');
    }));
}

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
