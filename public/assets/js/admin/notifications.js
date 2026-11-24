/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Notifications Inbox Interactions
 * File: assets/js/admin/notifications.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ------------------------------------------------------
         | 1. Debounced search auto-submit
         * ------------------------------------------------------ */

        var form = document.querySelector('.admin-notifications-page .admin-filter-form');
        var searchInput = form ? form.querySelector('input[type="search"]') : null;
        var debounceTimer = null;

        if (form && searchInput) {

            searchInput.addEventListener('input', function () {
                if (debounceTimer) clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    submitForm(form);
                }, 600);
            });

            searchInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    if (debounceTimer) {
                        clearTimeout(debounceTimer);
                        debounceTimer = null;
                    }
                    submitForm(form);
                }
            });
        }

        /* Auto-submit when the type dropdown changes */
        if (form) {
            form.querySelectorAll('select').forEach(function (select) {
                select.addEventListener('change', function () {
                    submitForm(form);
                });
            });
        }

        /* ------------------------------------------------------
         | 2. Ctrl+K / Cmd+K focuses search
         * ------------------------------------------------------ */

        document.addEventListener('keydown', function (event) {
            var isMac = navigator.platform.toUpperCase().indexOf('MAC') >= 0;
            var meta = isMac ? event.metaKey : event.ctrlKey;

            if (meta && event.key.toLowerCase() === 'k') {
                event.preventDefault();
                if (searchInput) {
                    searchInput.focus();
                    searchInput.select();
                }
            }
        });

        /* ------------------------------------------------------
         | 3. Confirm dialogs for inline forms with data-confirm
         * ------------------------------------------------------ */

        document.querySelectorAll('form[data-confirm]').forEach(function (f) {
            f.addEventListener('submit', function (event) {
                var message = f.getAttribute('data-confirm') || '';
                if (message && !window.confirm(message)) {
                    event.preventDefault();
                }
            });
        });
    });

    function submitForm(form) {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

})();