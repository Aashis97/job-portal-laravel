/**
 * ==========================================================
 * Online Job Portal System
 * Admin — User List Interactions
 * File: assets/js/admin/users.js
 * ==========================================================
 *
 * Behavior:
 *   - Auto-submit the filter form when a dropdown changes
 *   - Debounced auto-submit while typing in the search box
 *   - Ctrl+K / Cmd+K focuses the search input
 *   - Enter clears the debounce and submits immediately
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var form = document.querySelector('.admin-filter-form');
        if (!form) {
            return;
        }

        /* ------------------------------------------------------
         | 1. Auto-submit on select change
         * ------------------------------------------------------ */

        form.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () {
                submitForm(form);
            });
        });

        /* ------------------------------------------------------
         | 2. Debounced auto-submit on search input
         * ------------------------------------------------------ */

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

            /* Enter clears the pending debounce and submits right away. */
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

        /* ------------------------------------------------------
         | 3. Ctrl+K / Cmd+K focuses the search input
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
    });

    /* ----------------------------------------------------------
     | Helpers
     * ---------------------------------------------------------- */

    function submitForm(form) {
        /*
         * requestSubmit() runs native HTML validation and triggers
         * the submit event, whereas form.submit() bypasses both.
         */
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

})();