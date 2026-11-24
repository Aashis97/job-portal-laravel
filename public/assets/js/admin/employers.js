/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Employers List
 * File: assets/js/admin/employers.js
 *
 * Progressive enhancements only. If a feature is missing
 * its markup, the module quietly no-ops.
 * ==========================================================
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var page = document.querySelector('.admin-employers-page');
        if (!page) return;

        var config = window.adminEmployers || {};

        /* ==================================================
           1. Search debounce — auto-submit after typing
           ================================================== */
        var searchInput = document.getElementById('adminSearch');
        var filterForm  = document.getElementById('adminEmployersFilter');

        if (searchInput && filterForm) {
            var debounceTimer = null;

            searchInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(function () {
                    filterForm.submit();
                }, 500);
            });

            /* Preserve caret at the end after a reload with a value */
            if (searchInput.value && document.activeElement !== searchInput) {
                searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
            }
        }

        /* ==================================================
           2. Filter selects — auto-submit on change
           ================================================== */
        var autoSubmitSelects = page.querySelectorAll(
            '#adminAccountFilter, #adminCompanyFilter'
        );

        Array.prototype.forEach.call(autoSubmitSelects, function (select) {
            select.addEventListener('change', function () {
                if (filterForm) filterForm.submit();
            });
        });

        /* ==================================================
           3. Keyboard shortcut — "/" focuses the search
           ================================================== */
        document.addEventListener('keydown', function (e) {
            if (e.key !== '/') return;
            if (e.ctrlKey || e.metaKey || e.altKey) return;

            var tag = (e.target && e.target.tagName) || '';
            if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;

            if (searchInput) {
                e.preventDefault();
                searchInput.focus();
                searchInput.select();
            }
        });

        /* ==================================================
           4. Row actions — block / activate
           ================================================== */
        var actionButtons = page.querySelectorAll('.js-employer-action');

        Array.prototype.forEach.call(actionButtons, function (btn) {
            btn.addEventListener('click', function () {

                var action     = btn.getAttribute('data-action');
                var employerId = btn.getAttribute('data-employer-id');
                var name       = btn.getAttribute('data-name') || 'this employer';

                if (!action || !employerId) return;

                var prompt = action === 'block'
                    ? 'Block ' + name + '? They will not be able to log in.'
                    : 'Activate ' + name + '? They will regain access.';

                if (!window.confirm(prompt)) return;

                submitAction(btn, action, employerId);
            });
        });

        /* ==================================================
           5. AJAX submit for row actions
           ================================================== */
        function submitAction(btn, action, employerId) {

            /* Disable immediately to prevent double-clicks */
            var originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            var body = new URLSearchParams();
            body.append('action',       action);
            body.append('employer_id',  employerId);
            body.append('csrf_token',   config.csrfToken || '');

            fetch(config.actionUrl, {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'X-Requested-With': 'XMLHttpRequest' },
                body:        body.toString()
            })
                .then(function (r) { return r.json().catch(function () { return {}; }); })
                .then(function (data) {

                    if (data && data.success) {
                        flashToast(data.message || 'Updated successfully.', 'success');

                        /* Reload to reflect new state */
                        setTimeout(function () { window.location.reload(); }, 700);

                    } else {
                        flashToast((data && data.message) || 'Unable to complete the request.', 'error');

                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    }
                })
                .catch(function () {
                    flashToast('Network error. Please try again.', 'error');

                    btn.disabled = false;
                    btn.innerHTML = originalHtml;
                });
        }

        /* ==================================================
           6. Toast notifications
           ================================================== */
        function flashToast(message, variant) {
            var host = document.getElementById('adminToastHost');

            if (!host) {
                host = document.createElement('div');
                host.id = 'adminToastHost';
                host.className = 'admin-toast-host';
                document.body.appendChild(host);
            }

            var toast = document.createElement('div');
            toast.className = 'admin-toast admin-toast--' + (variant || 'info');
            toast.setAttribute('role', 'status');

            var icon = variant === 'success'
                ? 'fa-circle-check'
                : variant === 'error'
                    ? 'fa-circle-exclamation'
                    : 'fa-circle-info';

            toast.innerHTML =
                '<i class="fa-solid ' + icon + '"></i>' +
                '<span></span>';

            toast.querySelector('span').textContent = message;

            host.appendChild(toast);

            /* Force reflow so the transition applies */
            void toast.offsetWidth;
            toast.classList.add('is-visible');

            setTimeout(function () {
                toast.classList.remove('is-visible');
                setTimeout(function () { toast.remove(); }, 250);
            }, 3200);
        }

    });
})();