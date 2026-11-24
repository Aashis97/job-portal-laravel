/**
 * ==========================================================
 * Online Job Portal System
 * Job Seeker — Settings Page
 * File: assets/js/jobseeker/settings.js
 *
 * Progressive enhancement only. If the extra markup
 * (password toggles, strength meter, match message) is not
 * present, every feature quietly no-ops.
 * ==========================================================
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        var page = document.querySelector('.jobseeker-settings-page');
        if (!page) return;

        /* ==================================================
           Config
           ================================================== */
        var MIN_LENGTH = 8;

        var REQUIREMENTS = {
            length:    function (v) { return v.length >= MIN_LENGTH; },
            uppercase: function (v) { return /[A-Z]/.test(v); },
            lowercase: function (v) { return /[a-z]/.test(v); },
            number:    function (v) { return /[0-9]/.test(v); },
            special:   function (v) { return /[^A-Za-z0-9]/.test(v); }
        };

        /* ==================================================
           Password visibility toggles
           --------------------------------------------------
           Works with either:
             <button class="password-toggle" data-target="ID">
           or
             <button data-password-toggle="#ID">
           ================================================== */
        var toggles = page.querySelectorAll(
            '.password-toggle, [data-password-toggle]'
        );

        Array.prototype.forEach.call(toggles, function (btn) {
            btn.addEventListener('click', function () {

                var selector = btn.getAttribute('data-target')
                    ? '#' + btn.getAttribute('data-target')
                    : btn.getAttribute('data-password-toggle');

                var input = selector ? document.querySelector(selector) : null;

                /* Fallback: nearest password input in the same wrapper */
                if (!input) {
                    var wrapper = btn.closest('.password-field, .form-group');
                    if (wrapper) {
                        input = wrapper.querySelector('input[type="password"], input[type="text"]');
                    }
                }

                if (!input) return;

                var isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';

                var icon = btn.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-eye', !isPassword);
                    icon.classList.toggle('fa-eye-slash', isPassword);
                }

                btn.setAttribute(
                    'aria-label',
                    isPassword ? 'Hide password' : 'Show password'
                );

                /* Keep focus on the input for smoother UX */
                try { input.focus({ preventScroll: true }); } catch (e) { /* noop */ }
            });
        });

        /* ==================================================
           Password strength + requirements + match
           --------------------------------------------------
           Targets:
             #new_password             (strength source)
             #confirm_password         (match source)
             #strengthText             (optional)
             .password-strength        (optional wrapper)
             .strength-bars > span     (optional bars)
             [data-requirement]        (optional)
             #passwordMatchMessage     (optional)
           ================================================== */
        var newPassword     = document.getElementById('new_password');
        var confirmPassword = document.getElementById('confirm_password');
        var currentPassword = document.getElementById('current_password');

        var strengthWrap  = page.querySelector('.password-strength');
        var strengthText  = document.getElementById('strengthText');
        var strengthBars  = page.querySelectorAll('.strength-bars > span');
        var requirements  = page.querySelectorAll('[data-requirement]');
        var matchMessage  = document.getElementById('passwordMatchMessage');

        /* ---------------- Strength ------------- */
        function evaluateStrength(value) {
            var score = 0;
            var results = {};

            Object.keys(REQUIREMENTS).forEach(function (key) {
                var ok = REQUIREMENTS[key](value);
                results[key] = ok;
                if (ok) score++;
            });

            return { score: score, results: results };
        }

        function paintRequirements(results) {
            Array.prototype.forEach.call(requirements, function (item) {
                var key = item.getAttribute('data-requirement');
                var ok  = !!results[key];

                item.classList.toggle('valid', ok);

                var icon = item.querySelector('i');
                if (icon) {
                    icon.classList.toggle('fa-circle', !ok);
                    icon.classList.toggle('fa-circle-check', ok);
                }
            });
        }

        function paintStrengthBars(score) {
            Array.prototype.forEach.call(strengthBars, function (bar, index) {
                bar.classList.toggle('active', index < score);
            });
        }

        function paintStrengthText(value, score) {
            if (!strengthText) return;

            if (value === '') {
                strengthText.textContent = 'Enter a password';
                strengthText.className = 'strength-text';
                return;
            }

            var label, cls;

            if (score <= 2) {
                label = 'Weak password';
                cls   = 'strength-text weak';
            } else if (score <= 4) {
                label = 'Good password';
                cls   = 'strength-text medium';
            } else {
                label = 'Strong password';
                cls   = 'strength-text strong';
            }

            strengthText.textContent = label;
            strengthText.className   = cls;
        }

        function updateStrength() {
            if (!newPassword) return;

            var value = newPassword.value;
            var result = evaluateStrength(value);

            paintRequirements(result.results);
            paintStrengthBars(result.score);
            paintStrengthText(value, result.score);

            if (strengthWrap) {
                strengthWrap.setAttribute('data-level', String(result.score));
            }
        }

        /* ---------------- Match ------------- */
        function updateMatch() {
            if (!confirmPassword || !matchMessage) return;

            var a = newPassword ? newPassword.value : '';
            var b = confirmPassword.value;

            if (b === '') {
                matchMessage.textContent = '';
                matchMessage.className   = 'password-match-message';
                return;
            }

            if (a === b) {
                matchMessage.innerHTML =
                    '<i class="fa-solid fa-circle-check"></i> Passwords match';
                matchMessage.className = 'password-match-message match';
            } else {
                matchMessage.innerHTML =
                    '<i class="fa-solid fa-circle-xmark"></i> Passwords do not match';
                matchMessage.className = 'password-match-message no-match';
            }
        }

        if (newPassword) {
            newPassword.addEventListener('input', function () {
                updateStrength();
                updateMatch();
            });

            /* Initial paint (in case of autofill) */
            updateStrength();
        }

        if (confirmPassword) {
            confirmPassword.addEventListener('input', updateMatch);

            /* Re-check match when strength source changes */
            if (newPassword) {
                newPassword.addEventListener('input', updateMatch);
            }
        }

        /* ==================================================
           Client-side submit validation
           --------------------------------------------------
           We only PREVENT obvious mistakes. Server is still
           the source of truth.
           ================================================== */
        var forms = page.querySelectorAll('form.settings-form');

        Array.prototype.forEach.call(forms, function (form) {

            form.addEventListener('submit', function (event) {

                var errs = [];

                /* Required passwords */
                if (currentPassword && currentPassword.value === '') {
                    errs.push('Please enter your current password.');
                }

                if (newPassword) {
                    var value = newPassword.value;

                    if (value.length < MIN_LENGTH) {
                        errs.push('New password must be at least ' + MIN_LENGTH + ' characters.');
                    } else if (!REQUIREMENTS.uppercase(value)) {
                        errs.push('New password must contain at least one uppercase letter.');
                    } else if (!REQUIREMENTS.lowercase(value)) {
                        errs.push('New password must contain at least one lowercase letter.');
                    } else if (!REQUIREMENTS.number(value)) {
                        errs.push('New password must contain at least one number.');
                    } else if (!REQUIREMENTS.special(value)) {
                        errs.push('New password must contain at least one special character.');
                    }
                }

                if (confirmPassword && newPassword &&
                    confirmPassword.value !== newPassword.value) {
                    errs.push('New password and confirmation do not match.');
                }

                if (currentPassword && newPassword &&
                    currentPassword.value !== '' &&
                    currentPassword.value === newPassword.value) {
                    errs.push('Your new password must be different from your current password.');
                }

                if (errs.length > 0) {
                    event.preventDefault();

                    /* Prefer inline message if match message element exists */
                    if (matchMessage) {
                        matchMessage.innerHTML =
                            '<i class="fa-solid fa-circle-xmark"></i> ' + errs[0];
                        matchMessage.className = 'password-match-message no-match';
                        matchMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    } else {
                        alert(errs[0]);
                    }

                    return;
                }

                /* Lock the submit button to prevent double-post */
                var submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    var icon = submitBtn.querySelector('i');
                    submitBtn.dataset.originalHtml = submitBtn.innerHTML;

                    submitBtn.innerHTML =
                        '<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> Updating...';
                }
            });
        });

        /* ==================================================
           Reveal-on-scroll (subtle entrance)
           ================================================== */
        if ('IntersectionObserver' in window) {
            var cards = page.querySelectorAll('.settings-card');
            var io = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                        io.unobserve(entry.target);
                    }
                });
            }, { threshold: 0.08 });

            Array.prototype.forEach.call(cards, function (card, index) {
                card.style.opacity = '0';
                card.style.transform = 'translateY(12px)';
                card.style.transition =
                    'opacity 0.4s ease ' + (index * 0.05) + 's, ' +
                    'transform 0.4s ease ' + (index * 0.05) + 's, ' +
                    'box-shadow 0.2s ease, border-color 0.2s ease';
                io.observe(card);
            });
        }
    });
})();