/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Job Seeker Details
 * File: assets/js/admin/jobseeker_details.js
 * ==========================================================
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var page = document.querySelector('.admin-jobseeker-details-page');
        if (!page) return;

        var config = window.adminJobseekerDetails || {};

        /* ==================================================
           Block / activate buttons
           ================================================== */
        var actionButtons = page.querySelectorAll('.js-seeker-action');

        Array.prototype.forEach.call(actionButtons, function (btn) {

            btn.addEventListener('click', function () {

                var action   = btn.getAttribute('data-action');
                var seekerId = btn.getAttribute('data-seeker-id');
                var name     = btn.getAttribute('data-name') || 'this job seeker';

                if (!action || !seekerId) return;

                var prompt = action === 'block'
                    ? 'Block ' + name + '? They will not be able to log in.'
                    : 'Activate ' + name + '? They will regain access.';

                if (!window.confirm(prompt)) return;

                submitAction(btn, action, seekerId);
            });

        });

        function submitAction(btn, action, seekerId) {

            var original = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';

            var body = new URLSearchParams();
            body.append('action',        action);
            body.append('job_seeker_id', seekerId);
            body.append('csrf_token',    config.csrfToken || '');

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
                        setTimeout(function () { window.location.reload(); }, 700);
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
           Toast — reuses the styles defined in employers.css
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

            toast.innerHTML = '<i class="fa-solid ' + icon + '"></i><span></span>';
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