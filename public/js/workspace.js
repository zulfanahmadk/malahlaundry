'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const readPreference = key => {
        try {
            return localStorage.getItem(`laundry.${key}`);
        } catch {
            return null;
        }
    };
    const savePreference = (key, value) => {
        try {
            localStorage.setItem(`laundry.${key}`, value);
        } catch {
            // Navigation also works when the browser disables local storage.
        }
    };

    const toggle = document.querySelector('[data-sidebar-toggle]');
    const sidebar = document.getElementById('sidebar');
    const main = document.querySelector('.main-wrapper');
    const mobile = window.matchMedia('(max-width: 900px)');
    const setSidebar = open => {
        document.body.classList.toggle('sidebar-open', mobile.matches && open);
        document.body.classList.toggle('sidebar-collapsed', !mobile.matches && !open);
        if (main) main.inert = mobile.matches && open;
        if (sidebar) {
            if (!open && sidebar.contains(document.activeElement)) toggle?.focus();
            sidebar.inert = !open;
            sidebar.setAttribute('aria-hidden', String(!open));
            if (mobile.matches && open && !sidebar.contains(document.activeElement)) {
                (sidebar.querySelector('[aria-current="page"]') ?? sidebar.querySelector('a'))?.focus();
            }
        }
        toggle?.setAttribute('aria-expanded', String(open));
        const label = open ? 'Tutup menu' : 'Buka menu';
        toggle?.setAttribute('aria-label', label);
        toggle?.setAttribute('title', label);
    };
    const restoreSidebar = () => setSidebar(!mobile.matches && readPreference('sidebar') !== 'closed');
    restoreSidebar();
    toggle?.addEventListener('click', () => {
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        setSidebar(open);
        if (!mobile.matches) savePreference('sidebar', open ? 'open' : 'closed');
    });
    const closeSidebar = () => {
        setSidebar(false);
        if (!mobile.matches) savePreference('sidebar', 'closed');
    };
    document.querySelectorAll('[data-sidebar-close]').forEach(button => {
        button.addEventListener('click', closeSidebar);
    });
    mobile.addEventListener('change', restoreSidebar);
    sidebar?.querySelectorAll('.nav-link').forEach(link => link.addEventListener('click', () => {
        if (mobile.matches) closeSidebar();
    }));

    document.querySelectorAll('[data-nav-group]').forEach(group => {
        const key = `menu.${group.dataset.navGroup}`;
        // Keep the current page visible when navigating into a group.
        group.open = group.classList.contains('is-active') || readPreference(key) !== 'closed';
        group.addEventListener('toggle', () => savePreference(key, group.open ? 'open' : 'closed'));
    });

    document.querySelector('[data-branch-select]')?.addEventListener('change', event => {
        event.currentTarget.form?.requestSubmit();
    });

    const bell = document.getElementById('notification-toggle');
    const panel = document.getElementById('notification-popover');
    const closeNotifications = () => {
        if (panel) panel.hidden = true;
        bell?.setAttribute('aria-expanded', 'false');
    };
    bell?.addEventListener('click', () => {
        if (!panel) return;
        panel.hidden = !panel.hidden;
        bell.setAttribute('aria-expanded', String(!panel.hidden));
    });
    document.addEventListener('click', event => {
        if (!panel?.contains(event.target) && !bell?.contains(event.target)) closeNotifications();
    });
    document.addEventListener('keydown', event => {
        if (event.key === 'Tab' && sidebar && mobile.matches && document.body.classList.contains('sidebar-open')) {
            const items = [...sidebar.querySelectorAll('a, button, summary')].filter(item => item.getClientRects().length && !item.disabled);
            const first = items[0];
            const last = items[items.length - 1];
            if (first && ((event.shiftKey && document.activeElement === first) || (!event.shiftKey && document.activeElement === last))) {
                event.preventDefault();
                (event.shiftKey ? last : first).focus();
            }
        }
        if (event.key !== 'Escape') return;
        if (mobile.matches) closeSidebar();
        if (panel && !panel.hidden) {
            if (panel.contains(document.activeElement)) bell?.focus();
            closeNotifications();
        }
    });

    const showDialog = id => {
        const dialog = document.getElementById(id);
        if (dialog?.tagName === 'DIALOG' && !dialog.open) dialog.showModal();
    };
    document.querySelectorAll('[data-open-dialog]').forEach(button => button.addEventListener('click', () => {
        showDialog(button.dataset.openDialog);
    }));
    document.querySelectorAll('[data-close-dialog]').forEach(button => button.addEventListener('click', () => {
        button.closest('dialog')?.close();
    }));
    const failedForm = document.querySelector('[data-reopen-dialog]');
    if (failedForm?.dataset.reopenDialog) {
        const dialog = document.getElementById(failedForm.dataset.reopenDialog);
        if (dialog?.tagName === 'DIALOG') {
            const errors = failedForm.cloneNode(true);
            errors.removeAttribute('data-reopen-dialog');
            dialog.querySelector('form')?.prepend(errors);
        }
        showDialog(failedForm.dataset.reopenDialog);
    }
    document.querySelectorAll('[data-template-input]').forEach(input => input.addEventListener('input', () => {
        const preview = document.querySelector(`[data-template-preview="${input.dataset.templateInput}"]`);
        if (preview) preview.textContent = input.value;
    }));
    const photo = document.getElementById('photo-dialog');
    document.querySelectorAll('[data-photo]').forEach(link => link.addEventListener('click', event => {
        const preview = photo?.querySelector('img');
        if (!preview) return;
        event.preventDefault();
        preview.src = link.href;
        photo.showModal();
    }));
});
