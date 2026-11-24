/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Company Details Interactions
 * File: assets/js/admin/company_details.js
 * ==========================================================
 *
 * Responsibilities:
 *   - Wire data-action buttons to the hidden POST form
 *   - Confirm approve / suspend via native confirm()
 *   - Reject via modal with required reason textarea
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var root = document.querySelector('.admin-company-details');
        if (!root) {
            return;
        }

        var form = document.getElementById('actionForm');
        if (!form) {
            return;
        }

        var actionInput = form.querySelector('input[name="action"]');
        var reasonInput = form.querySelector('input[name="reason"]');

        /* -----------------------------------------------
         | Simple confirm actions
         * ----------------------------------------------- */

        document.querySelectorAll('[data-action]').forEach(function (button) {

            var action = button.getAttribute('data-action');
            if (action === 'reject') {
                return; // handled by the modal
            }

            button.addEventListener('click', function () {

                var confirmText = button.getAttribute('data-confirm') || '';

                if (confirmText && !window.confirm(confirmText)) {
                    return;
                }

                submitAction(action, '');
            });
        });

        /* -----------------------------------------------
         | Reject modal
         * ----------------------------------------------- */

        var rejectModal   = document.getElementById('rejectModal');
        var rejectTrigger = document.querySelector('[data-action="reject"]');
        var reasonField   = document.getElementById('rejectReason');
        var charCount     = document.getElementById('rejectCharCount');
        var rejectSubmit  = document.getElementById('rejectConfirmBtn');

        if (rejectModal && rejectTrigger && reasonField && rejectSubmit) {

            rejectTrigger.addEventListener('click', function () {
                rejectModal.hidden = false;
                reasonField.value = '';
                charCount.textContent = '0';
                rejectSubmit.disabled = true;
                setTimeout(function () { reasonField.focus(); }, 40);
            });

            rejectModal.querySelectorAll('[data-close-modal]').forEach(function (el) {
                el.addEventListener('click', function () {
                    rejectModal.hidden = true;
                });
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !rejectModal.hidden) {
                    rejectModal.hidden = true;
                }
            });

            reasonField.addEventListener('input', function () {
                var len = reasonField.value.trim().length;
                charCount.textContent = reasonField.value.length;
                rejectSubmit.disabled = len < 5;
            });

            rejectSubmit.addEventListener('click', function () {
                if (rejectSubmit.disabled) {
                    return;
                }
                var reason = reasonField.value.trim();
                rejectModal.hidden = true;
                submitAction('reject', reason);
            });
        }

        /* -----------------------------------------------
         | Submit helper
         * ----------------------------------------------- */

        function submitAction(action, reason) {

            if (!action || !actionInput) {
                return;
            }

            actionInput.value = action;
            if (reasonInput) {
                reasonInput.value = reason || '';
            }

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }

    });

})();