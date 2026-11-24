/**
 * ==========================================================
 * Online Job Portal System
 * Jobseeker — Saved Jobs
 * File: assets/js/jobseeker/saved_jobs.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var form         = document.querySelector('.saved-search-form');
        var searchInput  = form ? form.querySelector('input[type="search"]') : null;
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

        /* Ctrl+K */
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

        /* Confirm on remove */
        document.querySelectorAll('form[data-confirm]').forEach(function (f) {
            f.addEventListener('submit', function (event) {
                var msg = f.getAttribute('data-confirm') || '';
                if (msg && !window.confirm(msg)) {
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