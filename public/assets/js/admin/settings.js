/**
 * ==========================================================
 * Online Job Portal System
 * Admin — Settings Interactions
 * File: assets/js/admin/settings.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var form = document.getElementById('settingsForm');
        if (!form) {
            return;
        }

        var submitBtn = document.getElementById('saveSettingsBtn');

        form.addEventListener('submit', function () {
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Saving...';
            }
        });

        /* ------------------------------------------------------
         | Warn before leaving unsaved changes
         * ------------------------------------------------------ */

        var initialState = serializeForm(form);

        window.addEventListener('beforeunload', function (event) {
            if (serializeForm(form) !== initialState) {
                event.preventDefault();
                event.returnValue = '';
                return '';
            }
        });

        form.addEventListener('submit', function () {
            /* Disable the guard after a legitimate submit */
            initialState = serializeForm(form);
        });

        function serializeForm(formEl) {
            var data = [];
            var elements = formEl.elements;

            for (var i = 0; i < elements.length; i++) {
                var el = elements[i];
                if (!el.name) continue;
                if (el.type === 'checkbox') {
                    data.push(el.name + '=' + (el.checked ? '1' : '0'));
                } else if (el.type !== 'submit') {
                    data.push(el.name + '=' + (el.value || ''));
                }
            }

            return data.join('&');
        }
    });

})();