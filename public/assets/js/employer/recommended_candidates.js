(function () {
    'use strict';

    function initComparison() {
        const bar = document.querySelector('[data-compare-bar]');
        const count = document.querySelector('[data-compare-count]');
        const compareButton = document.querySelector('[data-compare-go]');
        const clearButton = document.querySelector('[data-compare-clear]');
        const checkboxes = Array.from(
            document.querySelectorAll('[data-compare-candidate]')
        );

        if (!bar || !count || !compareButton || !clearButton || checkboxes.length === 0) {
            return;
        }

        function updateComparison() {
            const selected = checkboxes.filter(function (checkbox) {
                return checkbox.checked;
            });
            const atLimit = selected.length >= 4;

            count.textContent = String(selected.length);
            bar.hidden = selected.length === 0;
            compareButton.disabled = selected.length < 2 || selected.length > 4;

            checkboxes.forEach(function (checkbox) {
                checkbox.disabled = atLimit && !checkbox.checked;
            });
        }

        checkboxes.forEach(function (checkbox) {
            checkbox.addEventListener('change', updateComparison);
        });

        clearButton.addEventListener('click', function () {
            checkboxes.forEach(function (checkbox) {
                checkbox.checked = false;
            });
            updateComparison();
        });

        compareButton.addEventListener('click', function () {
            const selectedIds = checkboxes
                .filter(function (checkbox) {
                    return checkbox.checked;
                })
                .map(function (checkbox) {
                    return checkbox.value;
                });

            if (selectedIds.length < 2 || selectedIds.length > 4) {
                return;
            }

            const comparisonUrl = new URL(bar.dataset.compareUrl, window.location.href);
            comparisonUrl.searchParams.set('ids', selectedIds.join(','));
            window.location.assign(comparisonUrl.toString());
        });

        updateComparison();
    }

    function initFilterForm() {
        const form = document.querySelector('.rc-filter-form');
        if (!form) {
            return;
        }

        const search = form.querySelector('input[type="search"]');
        if (search) {
            search.addEventListener('keydown', function (event) {
                if (event.key === 'Enter') {
                    form.requestSubmit();
                }
            });
        }
    }

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    ready(function () {
        initComparison();
        initFilterForm();
    });
})();
