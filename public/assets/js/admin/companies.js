/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Companies List Interactions
 * File: assets/js/admin/companies.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var form = document.querySelector('.admin-companies-page .admin-filter-form');
        if (!form) {
            return;
        }

        /* -----------------------------------------------
         | Debounced search auto-submit
         * ----------------------------------------------- */

        var searchInput = form.querySelector('input[type="search"]');
        var debounceTimer = null;

        if (searchInput) {

            searchInput.addEventListener('input', function () {
                if (debounceTimer) {
                    clearTimeout(debounceTimer);
                }
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

        /* -----------------------------------------------
         | Ctrl+K / Cmd+K focuses search
         * ----------------------------------------------- */

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
    });

    function submitForm(form) {
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

})();