document.addEventListener('DOMContentLoaded', function () {
    const selector = document.querySelector('[data-language-selector]');
    if (!selector) return;

    const button = selector.querySelector('[data-language-button]');
    const options = selector.querySelectorAll('[data-language-option]');

    const close = () => {
        selector.classList.remove('open');
        if (button) button.setAttribute('aria-expanded', 'false');
    };

    button?.addEventListener('click', function (event) {
        event.stopPropagation();
        const isOpen = selector.classList.toggle('open');
        button.setAttribute('aria-expanded', String(isOpen));
    });

    document.addEventListener('click', close);
    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') close();
    });

    options.forEach(function (option) {
        option.addEventListener('click', function () {
            const language = option.dataset.languageOption;
            if (!['en', 'ne'].includes(language)) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const form = new FormData();
            form.append('language', language);
            if (csrfToken) form.append('csrf_token', csrfToken);

            fetch(document.documentElement.dataset.languageEndpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: form
            }).then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok || !data.success) {
                        throw new Error(data.message || 'Unable to change language.');
                    }
                    window.location.reload();
                });
            }).catch(function (error) {
                window.alert(error.message);
            });
        });
    });
});