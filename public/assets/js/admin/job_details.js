/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Job Details Interactions
 * File: assets/js/admin/job_details.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var root = document.querySelector('.admin-job-details');
        if (!root) {
            return;
        }

        var form = document.getElementById('actionForm');
        if (!form) {
            return;
        }

        var actionInput = form.querySelector('input[name="action"]');

        document.querySelectorAll('[data-action]').forEach(function (button) {

            var action = button.getAttribute('data-action');
            if (action === 'delete') {
                return; // handled by modal
            }

            button.addEventListener('click', function () {

                var confirmText = button.getAttribute('data-confirm') || '';

                if (confirmText && !window.confirm(confirmText)) {
                    return;
                }

                submitAction(action);
            });
        });

        var deleteModal  = document.getElementById('deleteModal');
        var deleteButton = document.querySelector('[data-action="delete"]');
        var deleteInput  = document.getElementById('deleteConfirmInput');
        var deleteSubmit = document.getElementById('deleteConfirmBtn');

        if (deleteModal && deleteButton && deleteInput && deleteSubmit) {

            var expectedTitle = (root.getAttribute('data-job-title') || '').trim();

            deleteButton.addEventListener('click', function () {
                deleteModal.hidden = false;
                deleteInput.value = '';
                deleteSubmit.disabled = true;
                setTimeout(function () { deleteInput.focus(); }, 30);
            });

            deleteModal.querySelectorAll('[data-close-modal]').forEach(function (el) {
                el.addEventListener('click', function () {
                    deleteModal.hidden = true;
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !deleteModal.hidden) {
                    deleteModal.hidden = true;
                }
            });

            deleteInput.addEventListener('input', function () {
                deleteSubmit.disabled = deleteInput.value.trim() !== expectedTitle;
            });

            deleteInput.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' && !deleteSubmit.disabled) {
                    event.preventDefault();
                    confirmDelete();
                }
            });

            deleteSubmit.addEventListener('click', confirmDelete);

            function confirmDelete() {
                if (deleteSubmit.disabled) {
                    return;
                }
                deleteModal.hidden = true;
                submitAction('delete');
            }
        }

        function submitAction(action) {

            if (!action || !actionInput) {
                return;
            }

            actionInput.value = action;

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }

    });

})();