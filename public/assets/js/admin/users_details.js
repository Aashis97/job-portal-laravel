/**
 * ==========================================================
 * Online Job Portal System
 * Admin — User Details Interactions
 * File: assets/js/admin/user_details.js
 * ==========================================================
 *
 * Responsibilities:
 *   - Wire data-action buttons to the hidden POST form
 *   - Show native confirm() for simple actions
 *   - Show a typed-confirmation modal for delete
 *   - Handle role dropdown → change role submit
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var root = document.querySelector('.admin-user-details');
        if (!root) {
            return;
        }

        var form = document.getElementById('actionForm');
        if (!form) {
            return;
        }

        var actionInput = form.querySelector('input[name="action"]');
        var roleInput   = form.querySelector('input[name="role_id"]');

        /* ------------------------------------------------------
         | Simple actions — confirm + submit
         * ------------------------------------------------------ */

        document.querySelectorAll('[data-action]').forEach(function (button) {

            var action = button.getAttribute('data-action');

            /* Change role is handled separately below. */
            if (action === 'change_role' || action === 'change_role_apply') {
                return;
            }

            button.addEventListener('click', function () {

                var confirmText = button.getAttribute('data-confirm') || '';

                if (action === 'delete') {
                    /* Delete goes through the modal, not confirm(). */
                    return;
                }

                if (confirmText && !window.confirm(confirmText)) {
                    return;
                }

                submitAction(action);
            });
        });

        /* ------------------------------------------------------
         | Role change
         * ------------------------------------------------------ */

        var roleSelect      = document.getElementById('role-select');
        var roleApplyButton = document.querySelector('[data-action="change_role_apply"]');

        if (roleApplyButton && roleSelect) {

            roleApplyButton.addEventListener('click', function () {

                var newRole = roleSelect.value;
                var current = root.getAttribute('data-user-role') || '';

                if (!newRole) {
                    return;
                }

                var roleNames = { '1': 'Administrator', '2': 'Employer', '3': 'Job Seeker' };
                var label = roleNames[newRole] || ('Role ' + newRole);

                if (!window.confirm('Change this user\u2019s role to ' + label + '?')) {
                    return;
                }

                if (roleInput) {
                    roleInput.value = newRole;
                }

                submitAction('change_role');
            });
        }

        /* ------------------------------------------------------
         | Delete modal
         * ------------------------------------------------------ */

        var deleteModal  = document.getElementById('deleteModal');
        var deleteButton = document.querySelector('[data-action="delete"]');
        var deleteInput  = document.getElementById('deleteConfirmInput');
        var deleteSubmit = document.getElementById('deleteConfirmBtn');

        if (deleteModal && deleteButton && deleteInput && deleteSubmit) {

            var expectedName = (root.getAttribute('data-user-name') || '').trim();

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
                deleteSubmit.disabled = deleteInput.value.trim() !== expectedName;
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

        /* ------------------------------------------------------
         | Submit helper
         * ------------------------------------------------------ */

        function submitAction(action) {

            if (!action || !actionInput) {
                return;
            }

            /* Reset role input for actions that don't need it. */
            if (roleInput && action !== 'change_role') {
                roleInput.value = '';
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