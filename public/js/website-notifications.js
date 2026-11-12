document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-notification-delete]');
    if (!button) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();
    event.stopImmediatePropagation();

    const item = button.closest('[data-notification-item]');
    const menu = button.closest('.website-notification-menu');
    const error = menu?.querySelector('[data-notification-error]');
    if (!item || !menu) {
        return;
    }

    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    if (error) {
        error.hidden = true;
        error.textContent = '';
    }

    try {
        const response = await fetch(button.dataset.notificationDelete, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            throw new Error(`Penghapusan gagal (HTTP ${response.status}).`);
        }

        const result = await response.json();

        if (result.deleted !== true) {
            throw new Error('Server tidak mengonfirmasi penghapusan notifikasi.');
        }

        const count = menu.closest('.parent-dropdown, .gd-dropdown, .dropdown')
            ?.querySelector('[data-notification-count]');

        const remainingUnread = Array.from(
            menu.querySelectorAll('[data-notification-item]')
        ).filter(notification =>
            notification !== item &&
            notification.dataset.notificationUnread === 'true'
        ).length;

        if (count) {
            count.textContent = String(remainingUnread);
            count.hidden = remainingUnread === 0;
        }

        item.remove();

        const remainingItems = menu.querySelectorAll('[data-notification-item]');
        if (!remainingItems.length) {
            let empty = menu.querySelector('[data-notification-empty]');
            if (!empty) {
                empty = document.createElement('p');
                empty.className = 'website-notification-empty';
                empty.dataset.notificationEmpty = '';
                empty.textContent = 'Belum ada notifikasi';
                menu.querySelector('.website-notification-list')?.append(empty);
            }
            empty.hidden = false;
        }
    } catch (requestError) {
        if (error) {
            error.textContent = requestError instanceof Error
                ? requestError.message
                : 'Penghapusan notifikasi gagal. Silakan coba lagi.';
            error.hidden = false;
        }
        button.disabled = false;
        button.removeAttribute('aria-busy');
    }
}, true);
