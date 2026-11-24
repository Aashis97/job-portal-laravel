/**
 * ==========================================================
 * Online Job Portal System
 * Jobseeker — Recommendations
 * File: assets/js/jobseeker/recommendations.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* Confirm before regenerating */
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {

                var msg = form.getAttribute('data-confirm') || '';
                if (msg && !window.confirm(msg)) {
                    event.preventDefault();
                    return;
                }

                var btn = form.querySelector('button[type="submit"]');
                if (btn) {
                    btn.disabled = true;
                    btn.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Working...';
                }
            });
        });

        /* Auto-dismiss flash after 5 seconds */
        var alerts = document.querySelectorAll('.jobseeker-recommendations-page .alert');
        alerts.forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-6px)';
                setTimeout(function () { alert.remove(); }, 400);
            }, 5000);
        });
    });

})();