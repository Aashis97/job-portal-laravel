/* ==========================================================================
   Online Job Portal System
   Employer — Applicant Details
   File: assets/js/employer/applicant_details.js
   ========================================================================== */

(function () {
    'use strict';

    /* ----------------------------------------------------------------------
       1. Application Status Form
       ---------------------------------------------------------------------- */

    function initStatusForm() {
        const form = document.querySelector('.application-status-form');
        const select = document.getElementById('applicationStatus');
        const submitBtn = document.getElementById('updateStatusButton');

        if (!form || !select || !submitBtn) {
            return;
        }

        // Store the original status so we can detect "no change"
        const originalStatus = select.dataset.original || select.value;

        // Highlight the select when the user changes it
        select.addEventListener('change', function () {
            if (select.value !== originalStatus) {
                submitBtn.classList.add('has-changes');
            } else {
                submitBtn.classList.remove('has-changes');
            }
        });

        // Intercept submit → confirmation + loading state
        form.addEventListener('submit', function (event) {

            const newStatus = select.value;

            // Nothing changed → just bounce back with a friendly notice
            if (newStatus === originalStatus) {
                event.preventDefault();
                showInlineNotice(
                    'info',
                    'The application is already marked as "' + newStatus + '".'
                );
                return;
            }

            // Destructive or irreversible statuses → confirm
            const confirmRequired = ['Rejected', 'Withdrawn', 'Selected'];

            if (confirmRequired.indexOf(newStatus) !== -1) {
                const ok = window.confirm(
                    'Change application status to "' + newStatus + '"?\n\n' +
                    'This action will be visible to the candidate.'
                );

                if (!ok) {
                    event.preventDefault();
                    return;
                }
            }

            // Loading state
            submitBtn.disabled = true;
            submitBtn.dataset.originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML =
                '<i class="fa-solid fa-spinner fa-spin"></i> <span>Updating…</span>';
        });
    }

    /* ----------------------------------------------------------------------
       2. Resume View — guard against popup blockers / accidental double-click
       ---------------------------------------------------------------------- */

    function initResumeLinks() {
        const resumeLinks = document.querySelectorAll(
            '.candidate-header-actions a[href*="view_resume.php"]'
        );

        resumeLinks.forEach(function (link) {
            link.addEventListener('click', function () {
                link.classList.add('is-loading');

                // Remove the visual state after a moment — the new tab
                // will already be opening
                window.setTimeout(function () {
                    link.classList.remove('is-loading');
                }, 1200);
            });
        });
    }

    /* ----------------------------------------------------------------------
       3. Copy-to-Clipboard for Email / Application ID
       ---------------------------------------------------------------------- */

    function initCopyFields() {
        const candidates = document.querySelectorAll('[data-copy]');

        candidates.forEach(function (el) {
            el.style.cursor = 'pointer';
            el.setAttribute('title', 'Click to copy');

            el.addEventListener('click', function () {
                const value = el.getAttribute('data-copy') || el.textContent.trim();
                copyToClipboard(value).then(function (ok) {
                    if (ok) {
                        flashCopied(el);
                    }
                });
            });
        });
    }

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text)
                .then(function () { return true; })
                .catch(function () { return legacyCopy(text); });
        }

        return Promise.resolve(legacyCopy(text));
    }

    function legacyCopy(text) {
        try {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'absolute';
            ta.style.left = '-9999px';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            return true;
        } catch (e) {
            return false;
        }
    }

    function flashCopied(el) {
        const original = el.getAttribute('data-original-text') || el.textContent;

        if (!el.hasAttribute('data-original-text')) {
            el.setAttribute('data-original-text', original);
        }

        el.classList.add('copied');

        // Small floating badge
        const badge = document.createElement('span');
        badge.className = 'copy-badge';
        badge.textContent = 'Copied';

        if (getComputedStyle(el).position === 'static') {
            el.style.position = 'relative';
        }

        el.appendChild(badge);

        window.setTimeout(function () {
            el.classList.remove('copied');
            if (badge.parentNode) {
                badge.parentNode.removeChild(badge);
            }
        }, 1400);
    }

    /* ----------------------------------------------------------------------
       4. Skill tag hover → reveal full name for long tags
       ---------------------------------------------------------------------- */

    function initSkillTags() {
        const tags = document.querySelectorAll(
            '.large-skill-tag, .comparison-tag'
        );

        tags.forEach(function (tag) {
            const text = tag.textContent.trim();

            if (text.length > 18) {
                tag.setAttribute('title', text);
            }
        });
    }

    /* ----------------------------------------------------------------------
       5. Match score circle — animate on scroll into view
       ---------------------------------------------------------------------- */

    function initScoreAnimation() {
        const circle = document.querySelector('.large-score-circle');
        if (!circle) {
            return;
        }

        const score = parseFloat(circle.style.getPropertyValue('--score'));

        if (!Number.isFinite(score)) {
            return;
        }

        if (!('IntersectionObserver' in window)) {
            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                animateScore(circle, score);
                observer.unobserve(circle);
            });
        }, { threshold: 0.35 });

        observer.observe(circle);
    }

    function animateScore(circle, target) {
        const start = 0;
        const duration = 900;
        const startTime = performance.now();

        // Temporarily override the CSS var, but restore at the end.
        circle.style.setProperty('--score', String(start));

        function step(now) {
            const elapsed = now - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // easeOutQuart
            const eased = 1 - Math.pow(1 - progress, 4);
            const current = start + (target - start) * eased;

            circle.style.setProperty('--score', current.toFixed(2));

            if (progress < 1) {
                window.requestAnimationFrame(step);
            } else {
                circle.style.setProperty('--score', String(target));
            }
        }

        window.requestAnimationFrame(step);
    }

    /* ----------------------------------------------------------------------
       6. Inline notice helper
       ---------------------------------------------------------------------- */

    function showInlineNotice(type, message) {
        const existing = document.querySelector('.applicant-details-page .inline-notice');
        if (existing) {
            existing.remove();
        }

        const notice = document.createElement('div');
        notice.className = 'inline-notice inline-notice-' + type;
        notice.innerHTML =
            '<i class="fa-solid ' +
            (type === 'info' ? 'fa-circle-info' : 'fa-circle-exclamation') +
            '"></i>' +
            '<span></span>' +
            '<button type="button" class="inline-notice-close" aria-label="Close">' +
            '<i class="fa-solid fa-xmark"></i>' +
            '</button>';

        notice.querySelector('span').textContent = message;

        const container = document.querySelector('.applicant-details-page');
        if (container) {
            container.insertBefore(notice, container.firstChild);
        }

        notice.querySelector('.inline-notice-close').addEventListener('click', function () {
            notice.remove();
        });

        window.setTimeout(function () {
            if (notice.parentNode) {
                notice.remove();
            }
        }, 4200);
    }

    /* ----------------------------------------------------------------------
       7. Status select keyboard shortcut
          (Up / Down arrows already work natively — this just helps Enter)
       ---------------------------------------------------------------------- */

    function initSelectKeyboard() {
        const select = document.getElementById('applicationStatus');
        const submitBtn = document.getElementById('updateStatusButton');

        if (!select || !submitBtn) {
            return;
        }

        select.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitBtn.click();
            }
        });
    }

    /* ----------------------------------------------------------------------
       8. Disable "double-click" on the progress steps
          (purely decorative — nothing to submit)
       ---------------------------------------------------------------------- */

    function initProgressSteps() {
        const steps = document.querySelectorAll('.progress-step');
        steps.forEach(function (step) {
            step.addEventListener('click', function (event) {
                event.preventDefault();
            });
        });
    }

    /* ----------------------------------------------------------------------
       9. Boot
       ---------------------------------------------------------------------- */

    function ready(callback) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', callback, { once: true });
        } else {
            callback();
        }
    }

    ready(function () {
        initStatusForm();
        initResumeLinks();
        initCopyFields();
        initSkillTags();
        initScoreAnimation();
        initSelectKeyboard();
        initProgressSteps();
    });

})();