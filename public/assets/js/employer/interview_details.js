'use strict';

document.addEventListener('DOMContentLoaded', () => {

    /*
    |--------------------------------------------------------------------------
    | Status Forms
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            'form.status-form, form[action*="process_interview_status"]'
        )
        .forEach((form) => {

            form.addEventListener('submit', (event) => {

                if (form.dataset.submitting === '1') {
                    event.preventDefault();
                    return;
                }

                const button = form.querySelector(
                    'button[type="submit"]'
                );

                if (button) {

                    button.disabled = true;

                    button.dataset.originalText =
                        button.innerHTML;

                    button.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin"></i> Updating...';
                }

                form.dataset.submitting = '1';
            });
        });

    /*
    |--------------------------------------------------------------------------
    | Interview Action Links
    |--------------------------------------------------------------------------
    |
    | These already use inline confirm() in the PHP page.
    | This section is intentionally left as a safe placeholder.
    |
    */
});