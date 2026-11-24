/**
 * ==========================================================
 * Online Job Portal System
 * Jobseeker — Find Jobs
 * File: assets/js/jobseeker/jobs.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var form = document.querySelector('.jobs-filter-form');
        if (!form) return;

        var searchInput   = form.querySelector('input[type="search"]');
        var debounceTimer = null;

        /* Debounced search */
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

        /* Auto-submit on dropdown change */
        form.querySelectorAll('select').forEach(function (s) {
            s.addEventListener('change', function () {
                submitForm(form);
            });
        });

        /* Ctrl+K / Cmd+K focuses search */
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