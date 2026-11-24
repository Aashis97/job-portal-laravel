/* ==========================================================================
   Online Job Portal System
   Employer — Company Profile
   File: assets/js/employer/company_profile.js
   ========================================================================== */

(function () {
    'use strict';

    /* ----------------------------------------------------------------------
       Config
       ---------------------------------------------------------------------- */

    const MAX_LOGO_SIZE = 2 * 1024 * 1024; // 2 MB

    const ALLOWED_TYPES = [
        'image/jpeg',
        'image/png',
        'image/webp'
    ];

    const ALLOWED_EXTENSIONS = ['.jpg', '.jpeg', '.png', '.webp'];

    /* ----------------------------------------------------------------------
       1. Logo upload — validation + instant preview
       ---------------------------------------------------------------------- */

    function initLogoUpload() {

        const input     = document.getElementById('company_logo');
        const preview   = document.getElementById('logoPreview');
        const fileLabel = document.getElementById('logoFileName');
        const wrapper   = document.querySelector('.logo-preview-box');

        if (!input) {
            return;
        }

        input.addEventListener('change', function () {

            const file = this.files && this.files[0];

            if (!file) {
                if (fileLabel) fileLabel.textContent = '';
                return;
            }

            // --- Validate MIME ---
            if (file.type && ALLOWED_TYPES.indexOf(file.type) === -1) {
                showFieldError(input, 'Only JPG, JPEG, PNG or WEBP images are allowed.');
                resetFileInput(input, fileLabel);
                return;
            }

            // --- Validate extension as second layer ---
            const lowerName = file.name.toLowerCase();
            const extOk = ALLOWED_EXTENSIONS.some(function (ext) {
                return lowerName.endsWith(ext);
            });

            if (!extOk) {
                showFieldError(input, 'The file extension must be .jpg, .jpeg, .png or .webp.');
                resetFileInput(input, fileLabel);
                return;
            }

            // --- Validate size ---
            if (file.size > MAX_LOGO_SIZE) {
                showFieldError(input, 'Logo must be smaller than 2 MB.');
                resetFileInput(input, fileLabel);
                return;
            }

            clearFieldError(input);

            // --- Update label ---
            if (fileLabel) {
                fileLabel.textContent = file.name;
            }

            // --- Show preview ---
            if (preview) {
                const reader = new FileReader();

                reader.onload = function (event) {
                    preview.src = event.target.result;

                    if (wrapper) {
                        wrapper.classList.add('has-new-logo');
                    }
                };

                reader.readAsDataURL(file);
            }
        });
    }

    function resetFileInput(input, fileLabel) {
        input.value = '';
        if (fileLabel) fileLabel.textContent = '';
    }

    /* ----------------------------------------------------------------------
       2. Form submit — client-side validation + loading state
       ---------------------------------------------------------------------- */

    function initFormValidation() {

        const form         = document.getElementById('companyProfileForm');
        const companyName  = document.getElementById('company_name');
        const designation  = document.getElementById('designation');
        const department   = document.getElementById('department');
        const submitButton = document.getElementById('saveProfileBtn');

        if (!form) {
            return;
        }

        form.addEventListener('submit', function (event) {

            let valid = true;

            // --- Company name ---
            if (companyName) {
                const name = companyName.value.trim();

                if (name.length < 2) {
                    valid = false;
                    showFieldError(companyName, 'Please enter a valid company name (at least 2 characters).');
                } else if (name.length > 150) {
                    valid = false;
                    showFieldError(companyName, 'Company name cannot exceed 150 characters.');
                } else {
                    clearFieldError(companyName);
                }
            }

            // --- Designation ---
            if (designation && designation.value.length > 150) {
                valid = false;
                showFieldError(designation, 'Designation cannot exceed 150 characters.');
            }

            // --- Department ---
            if (department && department.value.length > 150) {
                valid = false;
                showFieldError(department, 'Department cannot exceed 150 characters.');
            }

            if (!valid) {
                event.preventDefault();

                // Focus the first invalid field
                const firstInvalid = form.querySelector('.field-error');
                if (firstInvalid) firstInvalid.focus();

                return;
            }

            // --- Loading state ---
            if (submitButton) {
                submitButton.disabled = true;
                submitButton.dataset.originalHtml = submitButton.innerHTML;
                submitButton.innerHTML =
                    '<i class="fa-solid fa-spinner fa-spin"></i> Saving…';
            }
        });

        // Live-clear errors
        [companyName, designation, department].forEach(function (field) {
            if (!field) return;

            field.addEventListener('input', function () {
                clearFieldError(field);
            });
        });
    }

    /* ----------------------------------------------------------------------
       3. Flash alert dismissal
       ---------------------------------------------------------------------- */

    function initAlertClose() {
        document.querySelectorAll('.profile-alert .alert-close').forEach(function (button) {

            button.addEventListener('click', function () {

                const alert = button.closest('.profile-alert');
                if (!alert) return;

                alert.style.transition = 'opacity .25s ease, transform .25s ease';
                alert.style.opacity    = '0';
                alert.style.transform  = 'translateY(-6px)';

                window.setTimeout(function () {
                    alert.remove();
                }, 260);
            });
        });
    }

    /* ----------------------------------------------------------------------
       4. Auto-dismiss success alerts after 6s
       ---------------------------------------------------------------------- */

    function autoDismissSuccess() {
        const success = document.querySelector('.profile-alert.success');
        if (!success) return;

        window.setTimeout(function () {
            success.style.transition = 'opacity .4s ease';
            success.style.opacity    = '0';

            window.setTimeout(function () {
                success.remove();
            }, 420);
        }, 6000);
    }

    /* ----------------------------------------------------------------------
       5. Field error helpers
       ---------------------------------------------------------------------- */

    function showFieldError(field, message) {

        clearFieldError(field);

        field.classList.add('field-error');
        field.setAttribute('aria-invalid', 'true');

        const wrapper = field.closest('.input-wrapper') || field.parentElement;
        if (!wrapper) return;

        const error = document.createElement('small');
        error.className = 'field-error-message';
        error.setAttribute('role', 'alert');
        error.textContent = message;

        wrapper.insertAdjacentElement('afterend', error);

        // Prevent layout jumps on repeated calls
        field.addEventListener('input', function handler() {
            clearFieldError(field);
            field.removeEventListener('input', handler);
        });
    }

    function clearFieldError(field) {

        if (!field) return;

        field.classList.remove('field-error');
        field.removeAttribute('aria-invalid');

        const wrapper = field.closest('.input-wrapper') || field.parentElement;
        if (!wrapper) return;

        const next = wrapper.nextElementSibling;

        if (next && next.classList.contains('field-error-message')) {
            next.remove();
        }
    }

    /* ----------------------------------------------------------------------
       6. Company status — animated badge on load
       ---------------------------------------------------------------------- */

    function animateStatusBadge() {
        const badge = document.querySelector('.company-status');
        if (!badge) return;

        badge.style.opacity   = '0';
        badge.style.transform = 'translateY(-4px)';

        window.setTimeout(function () {
            badge.style.transition = 'opacity .35s ease, transform .35s ease';
            badge.style.opacity    = '1';
            badge.style.transform  = 'translateY(0)';
        }, 120);
    }

    /* ----------------------------------------------------------------------
       7. Preview image fallback (default company logo)
       ---------------------------------------------------------------------- */

    function initPreviewFallback() {
        const preview = document.getElementById('logoPreview');
        if (!preview) return;

        preview.addEventListener('error', function () {
            preview.style.display = 'none';

            const wrapper = preview.parentElement;
            if (!wrapper || wrapper.querySelector('.logo-fallback')) return;

            const fallback = document.createElement('div');
            fallback.className = 'logo-fallback';
            fallback.innerHTML = '<i class="fa-solid fa-building"></i>';

            wrapper.appendChild(fallback);
        });
    }

    /* ----------------------------------------------------------------------
       8. Boot
       ---------------------------------------------------------------------- */

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    ready(function () {
        initLogoUpload();
        initFormValidation();
        initAlertClose();
        autoDismissSuccess();
        animateStatusBadge();
        initPreviewFallback();
    });

})();