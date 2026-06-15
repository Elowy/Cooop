/* Net-Trade Hungary – kliensoldali interakciók */
(function () {
    'use strict';

    /* ---------- Mobil menü nyitás/zárás ---------- */
    var toggle = document.querySelector('[data-nav-toggle]');
    var nav = document.querySelector('[data-nav]');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('is-open');
            toggle.setAttribute('aria-expanded', String(open));
        });
        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                nav.classList.remove('is-open');
                toggle.setAttribute('aria-expanded', 'false');
            });
        });
    }

    /* ---------- Árnyék a fejlécre görgetéskor ---------- */
    var header = document.querySelector('[data-header]');
    if (header) {
        var onScroll = function () {
            header.classList.toggle('is-scrolled', window.scrollY > 8);
        };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    /* ---------- Sötét / világos mód ---------- */
    var themeBtn = document.querySelector('[data-theme-toggle]');
    if (themeBtn) {
        themeBtn.addEventListener('click', function () {
            var root = document.documentElement;
            var dark = root.getAttribute('data-theme') === 'dark';
            root.setAttribute('data-theme', dark ? 'light' : 'dark');
            try { localStorage.setItem('theme', dark ? 'light' : 'dark'); } catch (e) {}
        });
    }

    /* ---------- Belépő animációk (reveal) ---------- */
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var reveals = document.querySelectorAll('.reveal');
    if (reduce || !('IntersectionObserver' in window)) {
        reveals.forEach(function (el) { el.classList.add('is-visible'); });
    } else {
        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        reveals.forEach(function (el) { io.observe(el); });
    }

    /* ---------- Galéria lightbox ---------- */
    var modal = document.querySelector('[data-lightbox-modal]');
    if (modal) {
        var lbImg = modal.querySelector('[data-lightbox-img]');
        var lbCap = modal.querySelector('[data-lightbox-cap]');
        var open = function (src, caption) {
            lbImg.setAttribute('src', src);
            lbImg.setAttribute('alt', caption || '');
            lbCap.textContent = caption || '';
            modal.removeAttribute('hidden');
            document.body.classList.add('no-scroll');
        };
        var close = function () {
            modal.setAttribute('hidden', '');
            document.body.classList.remove('no-scroll');
        };
        document.querySelectorAll('[data-lightbox]').forEach(function (item) {
            item.addEventListener('click', function () {
                open(item.getAttribute('data-src'), item.getAttribute('data-caption'));
            });
        });
        modal.addEventListener('click', function (e) {
            if (e.target === modal || e.target.hasAttribute('data-lightbox-close')) {
                close();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !modal.hasAttribute('hidden')) { close(); }
        });
    }

    /* ---------- Árkalkulátor ---------- */
    var calc = document.querySelector('[data-calc]');
    if (calc) {
        var currency = calc.getAttribute('data-currency') || 'Ft';
        var productSel = calc.querySelector('[data-calc-product]');
        var qtyInput = calc.querySelector('[data-calc-qty]');
        var sizeSel = calc.querySelector('[data-calc-size]');
        var unitOut = document.querySelector('[data-calc-unitprice]');
        var totalOut = document.querySelector('[data-calc-total]');

        var fmt = function (value) {
            return Math.round(value).toLocaleString('hu-HU') + ' ' + currency;
        };

        var update = function () {
            var opt = productSel.options[productSel.selectedIndex];
            var unitPrice = parseFloat(opt.value) || 0;
            var unit = opt.getAttribute('data-unit') || 'db';
            var qty = Math.max(1, parseInt(qtyInput.value, 10) || 0);
            var factor = parseFloat(sizeSel.value) || 1;
            var effectiveUnit = unitPrice * factor;

            unitOut.textContent = fmt(effectiveUnit) + ' / ' + unit;
            totalOut.textContent = fmt(effectiveUnit * qty);
        };

        [productSel, qtyInput, sizeSel].forEach(function (el) {
            el.addEventListener('input', update);
            el.addEventListener('change', update);
        });
        update();
    }
})();
