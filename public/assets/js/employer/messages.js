'use strict';

document.addEventListener('DOMContentLoaded', () => {

    /*
    |--------------------------------------------------------------------------
    | Conversation Auto Scroll
    |--------------------------------------------------------------------------
    */

    const thread = document.getElementById('conversationThread');

    if (thread) {

        const scrollToBottom = () => {
            thread.scrollTop = thread.scrollHeight;
        };

        scrollToBottom();

        window.addEventListener('load', scrollToBottom);
    }

    /*
    |--------------------------------------------------------------------------
    | Textarea Auto Resize
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.reply-form textarea, .compose-form textarea')
        .forEach((textarea) => {

            const resize = () => {

                textarea.style.height = 'auto';

                textarea.style.height =
                    Math.min(textarea.scrollHeight, 240) + 'px';
            };

            textarea.addEventListener('input', resize);

            resize();
        });

    /*
    |--------------------------------------------------------------------------
    | Submit Protection
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll('.reply-form, .compose-form')
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
                        '<i class="fa-solid fa-spinner fa-spin"></i> Sending...';
                }

                form.dataset.submitting = '1';
            });
        });
});