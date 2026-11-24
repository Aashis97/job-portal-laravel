'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const config = window.employerNotifications || {};
    const notificationList = document.querySelector('.notification-list');

    if (!notificationList) {
        return;
    }

    function showMessage(message, isError = false) {
        let status = document.querySelector('[data-notification-status]');

        if (!status) {
            status = document.createElement('div');
            status.dataset.notificationStatus = '';
            status.setAttribute('role', 'status');
            status.className = 'notification-status-message';
            document.querySelector('.notifications-toolbar')?.after(status);
        }

        status.textContent = message;
        status.classList.toggle('is-error', isError);
    }

    function updateUnreadCount(count) {
        const unreadCount = Math.max(0, Number(count) || 0);
        config.unreadCount = unreadCount;

        const headerCount = document.querySelector('.notification-stat-number');
        if (headerCount) {
            headerCount.textContent = String(unreadCount);
            headerCount.closest('.notification-header-stat')?.classList.toggle('is-hidden', unreadCount === 0);
        }

        document.querySelectorAll('[data-unread-count]').forEach((badge) => {
            badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            badge.classList.toggle('is-hidden', unreadCount === 0);
            badge.setAttribute(
                'aria-label',
                unreadCount === 1 ? '1 unread notification' : `${unreadCount} unread notifications`
            );
        });

        const markAllButton = document.querySelector('[data-action="mark-all-read"]');
        if (unreadCount === 0) {
            markAllButton?.remove();
        }
    }

    async function postAction(url, fields) {
        if (!url) {
            throw new Error('Notification action is not configured.');
        }

        const formData = new FormData();
        formData.append('csrf_token', config.csrfToken || '');
        Object.entries(fields).forEach(([name, value]) => formData.append(name, value));

        const response = await fetch(url, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': config.csrfToken || ''
            }
        });

        let data;
        try {
            data = await response.json();
        } catch {
            throw new Error('The notification request returned an invalid response.');
        }

        if (!response.ok || !data.success) {
            throw new Error(data.message || 'Unable to update notification.');
        }

        return data;
    }

    notificationList.addEventListener('click', async (event) => {
        const button = event.target.closest('button[data-action]');
        if (!button || button.disabled) {
            return;
        }

        const card = button.closest('.notification-card');
        const notificationId = button.dataset.notificationId;
        if (!card || !notificationId) {
            return;
        }

        if (button.dataset.action === 'mark-read') {
            button.disabled = true;
            try {
                const data = await postAction(config.markReadUrl, {
                    notification_id: notificationId
                });
                card.classList.remove('is-unread');
                card.classList.add('is-read');
                card.querySelector('.unread-dot')?.remove();
                button.outerHTML = '<span class="read-status"><i class="fa-solid fa-check-double"></i> Read</span>';
                updateUnreadCount(data.unread_count);
                showMessage(data.message || 'Notification marked as read.');
            } catch (error) {
                button.disabled = false;
                showMessage(error.message, true);
            }
        }

        if (button.dataset.action === 'delete-notification') {
            if (!window.confirm('Are you sure you want to delete this notification?')) {
                return;
            }

            button.disabled = true;
            try {
                await postAction(config.deleteUrl, {
                    notification_id: notificationId
                });
                window.location.reload();
            } catch (error) {
                button.disabled = false;
                showMessage(error.message, true);
            }
        }
    });

    document.querySelector('[data-action="mark-all-read"]')?.addEventListener('click', async (event) => {
        const button = event.currentTarget;
        if (button.disabled) {
            return;
        }

        button.disabled = true;
        try {
            const data = await postAction(config.markAllReadUrl, {});
            document.querySelectorAll('.notification-card.is-unread').forEach((card) => {
                card.classList.remove('is-unread');
                card.classList.add('is-read');
                card.querySelector('.unread-dot')?.remove();

                const markReadButton = card.querySelector('[data-action="mark-read"]');
                if (markReadButton) {
                    markReadButton.outerHTML = '<span class="read-status"><i class="fa-solid fa-check-double"></i> Read</span>';
                }
            });

            updateUnreadCount(data.unread_count);
            showMessage(data.message || 'All notifications marked as read.');
        } catch (error) {
            button.disabled = false;
            showMessage(error.message, true);
        }
    });

    updateUnreadCount(config.unreadCount);
});
