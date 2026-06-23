/* Net-Trade Hungary – kliensoldali interakciók */
(function () {
    'use strict';

    // Mobil menü
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

    // Fejléc háttér görgetéskor
    var header = document.querySelector('[data-header]');
    if (header) {
        var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 10); };
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });
    }

    // Hero parallax (erdő háttér)
    var parallax = document.querySelector('[data-parallax]');
    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (parallax && !reduceMotion) {
        var ticking = false;
        var applyParallax = function () {
            parallax.style.transform = 'translate3d(0,' + ((window.scrollY || 0) * 0.35) + 'px,0)';
            ticking = false;
        };
        applyParallax();
        window.addEventListener('scroll', function () {
            if (!ticking) { ticking = true; window.requestAnimationFrame(applyParallax); }
        }, { passive: true });
    }

    // Lebegő kapcsolati gomb
    var fab = document.querySelector('[data-fab]');
    var fabToggle = document.querySelector('[data-fab-toggle]');
    if (fab && fabToggle) {
        fabToggle.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = fab.classList.toggle('is-open');
            fabToggle.setAttribute('aria-expanded', String(open));
        });
        document.addEventListener('click', function (e) {
            if (!fab.contains(e.target)) {
                fab.classList.remove('is-open');
                fabToggle.setAttribute('aria-expanded', 'false');
            }
        });
    }

    // Referencia modal (doboz külön oldal helyett)
    var refModal = document.querySelector('#reference-modal');
    if (refModal && Array.isArray(window.NT_REFS)) {
        var refs = {};
        window.NT_REFS.forEach(function (r) { refs[r.id] = r; });
        var rEsc = function (s) { return String(s).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); };
        var openRef = function (r) {
            var logo = refModal.querySelector('[data-ref-logo]');
            logo.innerHTML = r.logo
                ? '<img src="/uploads/references/' + rEsc(r.logo) + '" alt="">'
                : '<span class="reference-monogram reference-monogram--sm">' + rEsc((r.name || '?').charAt(0).toUpperCase()) + '</span>';
            refModal.querySelector('[data-ref-name]').textContent = r.name || '';
            var shortEl = refModal.querySelector('[data-ref-short]');
            shortEl.textContent = r.short || '';
            shortEl.style.display = r.short ? '' : 'none';
            var longEl = refModal.querySelector('[data-ref-long]');
            longEl.innerHTML = r.long ? rEsc(r.long).replace(/\n/g, '<br>') : (r.short ? '' : 'Ehhez a referenciához még nincs bővebb leírás.');
            refModal.hidden = false;
            document.body.classList.add('modal-open');
        };
        var closeRef = function () { refModal.hidden = true; document.body.classList.remove('modal-open'); };
        document.querySelectorAll('[data-ref-open]').forEach(function (el) {
            el.addEventListener('click', function (e) {
                e.preventDefault();
                var r = refs[el.getAttribute('data-ref-open')];
                if (r) { openRef(r); }
            });
        });
        refModal.querySelectorAll('[data-modal-close]').forEach(function (b) { b.addEventListener('click', closeRef); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !refModal.hidden) { closeRef(); } });
    }

    // Pénztár: szállítási mezők ki/be
    var shipToggle = document.querySelector('[data-ship-toggle]');
    var shipFields = document.querySelector('[data-ship-fields]');
    if (shipToggle && shipFields) {
        shipToggle.addEventListener('change', function () {
            shipFields.hidden = !shipToggle.checked;
        });
    }

    // Belépő animációk
    var reveals = document.querySelectorAll('.reveal');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
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
})();
