/**
 * ==========================================================
 * Online Job Portal System
 * Landing Page Interactions
 * File: assets/js/landing.js
 * ==========================================================
 */

(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ------------------------------------------------------
         | Mobile nav toggle
         * ------------------------------------------------------ */

        var toggle = document.getElementById('siteToggle');
        var menu   = document.getElementById('siteMobileMenu');

        if (toggle && menu) {

            toggle.addEventListener('click', function (event) {

                event.stopPropagation();

                var isOpen = !menu.hidden;

                if (isOpen) {
                    menu.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
                } else {
                    menu.hidden = false;
                    toggle.setAttribute('aria-expanded', 'true');
                    toggle.innerHTML = '<i class="fa-solid fa-xmark" aria-hidden="true"></i>';
                }
            });

            /* Close on link click */
            menu.querySelectorAll('a').forEach(function (link) {
                link.addEventListener('click', function () {
                    menu.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
                });
            });

            /* Close on outside click */
            document.addEventListener('click', function (event) {
                if (menu.hidden) return;
                if (menu.contains(event.target) || toggle.contains(event.target)) return;

                menu.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
                toggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
            });

            /* Close on Escape */
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && !menu.hidden) {
                    menu.hidden = true;
                    toggle.setAttribute('aria-expanded', 'false');
                    toggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
                }
            });
        }

        /* ------------------------------------------------------
         | Smooth scroll for same-page anchors
         * ------------------------------------------------------ */

        document.querySelectorAll('a[href*="#"]').forEach(function (link) {

            var href = link.getAttribute('href') || '';
            var hashIndex = href.indexOf('#');

            if (hashIndex === -1) return;

            var hash = href.substring(hashIndex);
            if (hash.length < 2) return;

            var base = href.substring(0, hashIndex);
            var page = window.location.pathname.split('/').pop() || 'index.php';

            /* Only intercept when the anchor points to the current page */
            if (base === '' || base.endsWith(page)) {

                link.addEventListener('click', function (event) {

                    var target = document.querySelector(hash);
                    if (!target) return;

                    event.preventDefault();

                    window.scrollTo({
                        top: target.getBoundingClientRect().top + window.scrollY - 80,
                        behavior: 'smooth'
                    });

                    history.replaceState(null, '', hash);
                });
            }
        });

        /* ------------------------------------------------------
         | Sticky header shadow on scroll
         * ------------------------------------------------------ */

        var header = document.getElementById('siteHeader');

        if (header) {
            var ticking = false;

            function updateShadow() {
                if (window.scrollY > 8) {
                    header.style.boxShadow = '0 8px 24px rgba(15, 23, 42, 0.05)';
                } else {
                    header.style.boxShadow = 'none';
                }
                ticking = false;
            }

            window.addEventListener('scroll', function () {
                if (!ticking) {
                    window.requestAnimationFrame(updateShadow);
                    ticking = true;
                }
            }, { passive: true });

            updateShadow();
        }

    });

})();