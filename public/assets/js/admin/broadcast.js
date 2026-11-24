/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Broadcast Interactions
 * File: assets/js/admin/broadcast.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var form = document.getElementById('broadcastForm');
        if (!form) {
            return;
        }

        var audienceRadios = form.querySelectorAll('input[name="audience"]');
        var specificField  = document.getElementById('specificEmailField');
        var specificInput  = document.getElementById('specific_email');

        var typeSelect = form.querySelector('select[name="notif_type"]');
        var titleInput = form.querySelector('input[name="title"]');
        var msgInput   = form.querySelector('textarea[name="message"]');

        var titleCount   = document.getElementById('titleCount');
        var messageCount = document.getElementById('messageCount');

        var previewTitle     = document.getElementById('previewTitle');
        var previewMessage   = document.getElementById('previewMessage');
        var previewCount     = document.getElementById('previewCount');
        var previewType      = document.getElementById('previewType');
        var previewTypeBadge = document.getElementById('previewTypeBadge');
        var previewIcon      = document.getElementById('previewIcon');

        var counts = (window.broadcastData && window.broadcastData.counts) || {};

        /* ------------------------------------------------------
         | Audience
         * ------------------------------------------------------ */

        function currentAudience() {
            for (var i = 0; i < audienceRadios.length; i++) {
                if (audienceRadios[i].checked) {
                    return audienceRadios[i].value;
                }
            }
            return '';
        }

        function updateAudienceUI() {
            var val = currentAudience();

            if (val === 'specific') {
                if (specificField) {
                    specificField.hidden = false;
                    if (specificInput) {
                        specificInput.required = true;
                    }
                }
                if (previewCount) {
                    previewCount.textContent = '1';
                }
            } else {
                if (specificField) {
                    specificField.hidden = true;
                    if (specificInput) {
                        specificInput.required = false;
                    }
                }
                if (previewCount) {
                    var n = counts[val] || 0;
                    previewCount.textContent = n.toLocaleString();
                }
            }
        }

        audienceRadios.forEach(function (radio) {
            radio.addEventListener('change', updateAudienceUI);
        });

        /* Init */
        updateAudienceUI();

        /* ------------------------------------------------------
         | Type → preview icon + label
         * ------------------------------------------------------ */

        var typeIcons = {
            'System':      'fa-solid fa-bell',
            'Application': 'fa-solid fa-file-circle-plus',
            'Interview':   'fa-solid fa-calendar-check',
            'Job':         'fa-solid fa-briefcase',
            'Message':     'fa-solid fa-message',
            'Reminder':    'fa-solid fa-clock'
        };

        var typeClasses = {
            'System':      'notif-system',
            'Application': 'notif-application',
            'Interview':   'notif-interview',
            'Job':         'notif-job',
            'Message':     'notif-message',
            'Reminder':    'notif-reminder'
        };

        function updateTypeUI() {
            var t = typeSelect ? typeSelect.value : 'System';

            if (previewType) {
                previewType.textContent = t;
            }

            if (previewTypeBadge) {
                previewTypeBadge.textContent = t;
                previewTypeBadge.className = 'notification-type-badge ' + (typeClasses[t] || 'notif-system');
            }

            if (previewIcon) {
                var iconCls = typeIcons[t] || 'fa-solid fa-bell';
                var colorCls = typeClasses[t] || 'notif-system';
                previewIcon.className = 'notification-icon ' + colorCls;
                previewIcon.innerHTML = '<i class="' + iconCls + '" aria-hidden="true"></i>';
            }
        }

        if (typeSelect) {
            typeSelect.addEventListener('change', updateTypeUI);
            updateTypeUI();
        }

        /* ------------------------------------------------------
         | Title + message preview
         * ------------------------------------------------------ */

        function updateTitlePreview() {
            var v = (titleInput && titleInput.value.trim()) || '';
            if (previewTitle) {
                previewTitle.textContent = v || 'Your title will appear here';
            }
        }

        function updateMessagePreview() {
            var v = (msgInput && msgInput.value.trim()) || '';
            if (previewMessage) {
                previewMessage.textContent = v || 'Your message will appear here once you start typing.';
            }
        }

        /* ------------------------------------------------------
         | Character counters
         * ------------------------------------------------------ */

        function updateCounters() {
            if (titleInput && titleCount) {
                titleCount.textContent = titleInput.value.length;
            }
            if (msgInput && messageCount) {
                messageCount.textContent = msgInput.value.length;
            }
        }

        if (titleInput) {
            titleInput.addEventListener('input', function () {
                updateTitlePreview();
                updateCounters();
            });
        }

        if (msgInput) {
            msgInput.addEventListener('input', function () {
                updateMessagePreview();
                updateCounters();
            });
        }

        updateTitlePreview();
        updateMessagePreview();
        updateCounters();

        /* ------------------------------------------------------
         | Submit — sanity check + confirm
         * ------------------------------------------------------ */

        form.addEventListener('submit', function (event) {

            var titleVal = (titleInput && titleInput.value.trim()) || '';
            var msgVal   = (msgInput && msgInput.value.trim()) || '';
            var aud      = currentAudience();

            if (!aud) {
                event.preventDefault();
                alert('Please select an audience.');
                return;
            }

            if (aud === 'specific') {
                var email = (specificInput && specificInput.value.trim()) || '';
                if (!email) {
                    event.preventDefault();
                    alert('Please enter a recipient email address.');
                    if (specificInput) specificInput.focus();
                    return;
                }
            }

            if (!titleVal) {
                event.preventDefault();
                alert('Please enter a title.');
                if (titleInput) titleInput.focus();
                return;
            }

            if (!msgVal) {
                event.preventDefault();
                alert('Please enter a message.');
                if (msgInput) msgInput.focus();
                return;
            }

            var recipientCount = aud === 'specific'
                ? 1
                : (counts[aud] || 0);

            var confirmMsg = 'Send this broadcast to '
                + recipientCount.toLocaleString()
                + ' recipient' + (recipientCount === 1 ? '' : 's')
                + '?\n\nThis cannot be undone.';

            if (!window.confirm(confirmMsg)) {
                event.preventDefault();
                return;
            }

            var btn = document.getElementById('sendBroadcastBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
            }
        });

    });

})();