/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Report Details Interactions
 * File: assets/js/admin/report_details.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* Confirm before submitting any form marked with data-confirm */
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var msg = form.getAttribute('data-confirm') || '';
                if (msg && !window.confirm(msg)) {
                    event.preventDefault();
                }
            });
        });

        /* Confirm on the resolve form's submit button */
        var resolveForm = document.getElementById('resolveForm');
        if (resolveForm) {
            resolveForm.addEventListener('submit', function (event) {
                var button = resolveForm.querySelector('button[type="submit"]');
                var confirmMsg = button ? button.getAttribute('data-confirm') : '';

                if (confirmMsg && !window.confirm(confirmMsg)) {
                    event.preventDefault();
                    return;
                }

                if (button) {
                    button.disabled = true;
                    button.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Resolving...';
                }
            });
        }

        /* Disable notes submit to prevent double-submit */
        var notesForm = document.querySelector('.notes-form');
        if (notesForm) {
            notesForm.addEventListener('submit', function () {
                var btn = notesForm.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Saving...';
                }
            });
        }
    });

})();