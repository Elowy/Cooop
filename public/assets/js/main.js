/* Net Trade – kliensoldali interakciók */
(function () {
    'use strict';

    // Mobil menü nyitás/zárás
    const toggle = document.querySelector('[data-nav-toggle]');
    const nav = document.querySelector('[data-nav]');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            const open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(open));
        });
        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    // Árnyék a fejlécre görgetéskor
    const header = document.querySelector('[data-header]');
    if (header) {
        const onScroll = function () {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }
})();
