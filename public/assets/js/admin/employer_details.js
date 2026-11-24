/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Employer Details
 * File: assets/js/admin/employer_details.js
 * ==========================================================
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var page = document.querySelector('.admin-employer-details-page');
        if (!page) return;

        var config = window.adminEmployerDetails || {};

        /* ==================================================
           Block / activate buttons
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

        function submitAction(btn, action, employerId) {

            var original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            var body = new URLSearchParams();
            body.append('action',      action);
            body.append('employer_id', employerId);
            body.append('csrf_token',  config.csrfToken || '');

            fetch(config.actionUrl, {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'X-Requested-With': 'XMLHttpRequest' },
                body:        body.toString()
            })
                .then(function (r) { return r.json().catch(function () { return {}; }); })
                .then(function (data) {

                    if (data && data.success) {
                        flashToast(data.message || 'Updated.', 'success');

                        /* Reload after a beat so the hero state refreshes */
                        setTimeout(function () {
                            window.location.reload();
                        }, 700);

                    } else {
                        flashToast(
                            (data && data.message) || 'Unable to complete the request.',
                            'error'
                        );
                        btn.disabled = false;
                        btn.innerHTML = original;
                    }
                })
                .catch(function () {
                    flashToast('Network error. Please try again.', 'error');
                    btn.disabled = false;
                    btn.innerHTML = original;
                });
        }

        /* ==================================================
           Toast host (shared style with employers.js)
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
                '<i class="fa-solid ' + icon + '"></i><span></span>';

            toast.querySelector('span').textContent = message;

            host.appendChild(toast);
            void toast.offsetWidth;
            toast.classList.add('is-visible');

            setTimeout(function () {
                toast.classList.remove('is-visible');
                setTimeout(function () { toast.remove(); }, 250);
            }, 3200);
        }

    });
})();