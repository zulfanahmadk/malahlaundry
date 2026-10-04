'use strict';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-ticket-upload]').forEach(group => {
        const input = group.querySelector('input[type="file"]');
        const list = group.querySelector('[data-ticket-selected]');
        input.addEventListener('change', () => {
            list.replaceChildren();
            const files = Array.from(input.files);
            const error = files.length > 5 ? 'Pilih maksimal 5 file.' : files.some(file => file.size > 10 * 1024 * 1024) ? 'Setiap lampiran maksimal 10 MB.' : '';
            input.setCustomValidity(error);
            files.forEach(file => {
                const item = document.createElement('li');
                const size = file.size < 1024 * 1024 ? `${Math.max(1, Math.ceil(file.size / 1024))} KB` : `${(file.size / 1024 / 1024).toFixed(2)} MB`;
                item.textContent = `${file.name} (${size})`;
                list.append(item);
            });
            list.hidden = files.length === 0;
            if (error) input.reportValidity();
        });
    });
    document.querySelectorAll('[data-ticket-status-form]').forEach(form => {
        const status = form.querySelector('[name="status"]');
        const group = form.querySelector('[data-ticket-progress]');
        const progress = form.querySelector('[name="progress"]');
        const update = () => {
            const working = status.value === 'IN_PROGRESS';
            group.hidden = !working;
            progress.disabled = !working;
            progress.required = working;
        };
        status.addEventListener('change', update);
        update();
    });
    document.querySelectorAll('[data-admin-user-form]').forEach(form => {
        const role = form.querySelector('[name="role"]');
        const branchGroup = form.querySelector('[data-staff-branch]');
        const branch = form.querySelector('[name="branch_id"]');
        const help = form.querySelector('[data-branch-access]');
        const updateRole = () => {
            const admin = role.value === 'admin';
            branchGroup.hidden = admin;
            branch.disabled = admin;
            branch.required = !admin;
            help.textContent = admin ? 'Admin mengelola sistem tanpa akses operasional cabang.' : (role.value === 'owner' ? 'Penempatan menjadi cabang awal. Owner tetap dapat mengakses seluruh cabang.' : 'Kasir hanya dapat mengakses cabang penempatan.');
        };
        role.addEventListener('change', updateRole);
        updateRole();
    });
    document.querySelectorAll('input[type="password"]').forEach(input => {
        const wrapper = document.createElement('span');
        wrapper.className = 'password-field';
        input.before(wrapper);
        wrapper.append(input);
        const button = document.createElement('button');
        button.type = 'button';
        button.className = 'password-toggle';
        const render = () => {
            const visible = input.type === 'text';
            button.setAttribute('aria-label', visible ? 'Sembunyikan password' : 'Tampilkan password');
            button.setAttribute('aria-pressed', String(visible));
            button.title = button.getAttribute('aria-label');
            button.disabled = input.disabled;
            button.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>' + (visible ? '<path d="m3 3 18 18"/>' : '') + '</svg>';
        };
        button.addEventListener('click', () => {
            input.type = input.type === 'password' ? 'text' : 'password';
            render();
        });
        render();
        wrapper.append(button);
    });
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

    document.querySelectorAll('.table-container').forEach(container => {
        const hint = document.createElement('p');
        hint.className = 'table-scroll-hint';
        hint.textContent = 'Geser tabel untuk melihat kolom lainnya.';
        hint.hidden = true;
        container.before(hint);
        const update = () => {
            const overflow = container.scrollWidth > container.clientWidth + 1;
            hint.hidden = !overflow;
            if (overflow) {
                container.tabIndex = 0;
                container.setAttribute('role', 'region');
                container.setAttribute('aria-label', 'Tabel dapat digulir horizontal');
            } else {
                container.removeAttribute('tabindex');
                container.removeAttribute('role');
                container.removeAttribute('aria-label');
            }
        };
        new ResizeObserver(update).observe(container);
        update();
    });

    const bell = document.getElementById('notification-toggle');
    const panel = document.getElementById('notification-popover');
    let refreshingAdminNotifications = false;
    const refreshAdminNotifications = async () => {
        if (!panel?.dataset.adminNotifications || document.hidden || refreshingAdminNotifications) return;
        refreshingAdminNotifications = true;
        const controller = new AbortController();
        const timeout = setTimeout(() => controller.abort(), 10000);
        try {
            const response = await fetch(panel.dataset.adminNotifications, {
                credentials: 'same-origin', redirect: 'error', cache: 'no-store',
                headers: { Accept: 'application/json' }, signal: controller.signal,
            });
            if (!response.ok) return;
            const data = await response.json();
            if (!Number.isInteger(data.unread) || !Array.isArray(data.notifications)) return;
            panel.querySelector('[data-notification-unread-count]').textContent = `${data.unread} baru`;
            bell.setAttribute('aria-label', data.unread > 0 ? `Buka pemberitahuan, ${data.unread} belum dibaca` : 'Buka pemberitahuan');
            const dot = bell.querySelector('.unread-dot');
            if (data.unread > 0 && !dot) {
                const marker = document.createElement('span');
                marker.className = 'unread-dot';
                marker.setAttribute('aria-label', 'Ada notifikasi belum dibaca');
                bell.append(marker);
            } else if (data.unread === 0) dot?.remove();
            const items = panel.querySelector('[data-notification-items]');
            const focusedUrl = items.contains(document.activeElement) ? document.activeElement.closest('a')?.href : null;
            items.replaceChildren();
            data.notifications.forEach(notification => {
                const url = new URL(notification.url, location.origin);
                if (url.origin !== location.origin) return;
                const row = document.createElement('a');
                row.className = `notification-row${notification.read ? '' : ' unread'}`;
                row.href = url.href;
                const icon = document.createElement('span');
                icon.className = 'stat-icon';
                const iconWrapper = document.createElement('span');
                iconWrapper.className = 'icon';
                const image = document.createElement('img');
                image.src = panel.dataset.notificationIcon;
                image.alt = '';
                iconWrapper.append(image);
                icon.append(iconWrapper);
                const text = document.createElement('span');
                const title = document.createElement('strong');
                title.textContent = notification.title;
                const time = document.createElement('small');
                time.textContent = notification.time;
                text.append(title, time);
                row.append(icon, text);
                if (!notification.read) {
                    const marker = document.createElement('span');
                    marker.className = 'notification-unread-marker';
                    marker.setAttribute('aria-label', 'Belum dibaca');
                    row.append(marker);
                }
                items.append(row);
            });
            if (!items.children.length) {
                const empty = document.createElement('p');
                empty.className = 'empty';
                empty.textContent = 'Belum ada pemberitahuan.';
                items.append(empty);
            }
            if (focusedUrl) {
                const focusedRow = [...items.querySelectorAll('a')].find(row => row.href === focusedUrl);
                (focusedRow ?? bell).focus({ preventScroll: true });
            }
            panel.querySelector('[name="through_ticket_id"]').value = data.through_ticket_id;
            const ticketLink = document.querySelector('[data-admin-ticket-link]');
            let badge = ticketLink?.querySelector('.nav-ticket-count');
            if (ticketLink && data.pending_tickets > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'nav-ticket-count';
                    ticketLink.append(badge);
                }
                badge.textContent = data.pending_tickets;
                badge.setAttribute('aria-label', `${data.pending_tickets} tiket menunggu keputusan`);
            } else badge?.remove();
            fitNotifications();
        } catch (_) {
            // Keep the existing notification state while offline or after the session expires.
        } finally {
            clearTimeout(timeout);
            refreshingAdminNotifications = false;
        }
    };
    if (panel?.dataset.adminNotifications) {
        setInterval(refreshAdminNotifications, 30000);
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) refreshAdminNotifications();
        });
    }
    const closeNotifications = () => {
        if (panel) panel.hidden = true;
        bell?.setAttribute('aria-expanded', 'false');
    };
    const fitNotifications = () => {
        if (!panel || panel.hidden) return;
        const top = panel.getBoundingClientRect().top;
        panel.style.maxHeight = `${Math.max(0, window.innerHeight - top - 16)}px`;
    };
    window.addEventListener('resize', fitNotifications);
    bell?.addEventListener('click', () => {
        if (!panel) return;
        panel.hidden = !panel.hidden;
        bell.setAttribute('aria-expanded', String(!panel.hidden));
        fitNotifications();
        if (!panel.hidden) refreshAdminNotifications();
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
    document.querySelectorAll('[data-template-reset]').forEach(button => button.addEventListener('click', () => {
        const input = document.getElementById(`template-${button.dataset.templateReset}`);
        if (!input) return;
        input.value = button.dataset.defaultTemplate;
        input.dispatchEvent(new Event('input', { bubbles: true }));
    }));
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
        preview.hidden = false;
        photo.showModal();
    }));
});
