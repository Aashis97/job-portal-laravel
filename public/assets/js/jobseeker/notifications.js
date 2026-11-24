/**
 * ==========================================================
 * Online Job Portal System
 * Jobseeker — Notifications
 * File: assets/js/jobseeker/notifications.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* Confirm before destructive actions */
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                var msg = form.getAttribute('data-confirm') || '';
                if (msg && !window.confirm(msg)) {
                    event.preventDefault();
                }
            });
        });

        /* Fade flash messages after 5s */
        var flashAlerts = document.querySelectorAll('.jobseeker-notifications-page .alert');
        flashAlerts.forEach(function (alert) {
            setTimeout(function () {
                alert.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-6px)';
                setTimeout(function () { alert.remove(); }, 400);
            }, 5000);
        });

        /* Auto-submit tab clicks after marking read so the redirect works.
           (Actually tabs are plain links, nothing to do here.) */
    });

})();