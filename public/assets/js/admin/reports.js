/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Reports Queue Interactions
 * File: assets/js/admin/reports.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var form = document.querySelector('.admin-reports-page .admin-filter-form');
        if (!form) return;

        var searchInput = form.querySelector('input[type="search"]');
        var debounceTimer = null;

        if (searchInput) {
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

        form.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () {
                submitForm(form);
            });
        });

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