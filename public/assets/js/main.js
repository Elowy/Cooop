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

    // Görgetés-állapotjelző csík (a fejléc felett)
    var progress = document.querySelector('[data-scroll-progress]');
    if (progress) {
        var progTicking = false;
        var updateProgress = function () {
            var doc = document.documentElement;
            var max = (doc.scrollHeight - window.innerHeight);
            var pct = max > 0 ? Math.min((window.scrollY || doc.scrollTop || 0) / max, 1) : 0;
            progress.style.width = (pct * 100) + '%';
            progTicking = false;
        };
        updateProgress();
        window.addEventListener('scroll', function () {
            if (!progTicking) { progTicking = true; window.requestAnimationFrame(updateProgress); }
        }, { passive: true });
        window.addEventListener('resize', updateProgress, { passive: true });
    }

    // Vissza a tetejére gomb
    var toTop = document.querySelector('[data-to-top]');
    if (toTop) {
        var onToTop = function () { toTop.classList.toggle('is-visible', (window.scrollY || 0) > 400); };
        onToTop();
        window.addEventListener('scroll', onToTop, { passive: true });
        toTop.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
    }

    // Cookie hozzájárulás (soft wall)
    var cookieWall = document.querySelector('[data-cookie-wall]');
    if (cookieWall) {
        cookieWall.querySelectorAll('[data-cookie-accept]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var v = btn.getAttribute('data-cookie-accept');
                document.cookie = 'nt_consent=' + v + ';path=/;max-age=' + (60 * 60 * 24 * 180) + ';samesite=lax';
                cookieWall.classList.add('is-hidden');
                setTimeout(function () { if (cookieWall.parentNode) { cookieWall.parentNode.removeChild(cookieWall); } }, 300);
                if (v === 'all' && window.NT_GA && !window.__ntGa) {
                    window.__ntGa = true;
                    var s = document.createElement('script');
                    s.async = true;
                    s.src = 'https://www.googletagmanager.com/gtag/js?id=' + window.NT_GA;
                    document.head.appendChild(s);
                    window.dataLayer = window.dataLayer || [];
                    window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
                    window.gtag('js', new Date());
                    window.gtag('config', window.NT_GA);
                }
            });
        });
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

    // Szám-számláló (statisztika)
    var animateCount = function (el) {
        if (el.dataset.counted) { return; }
        var m = (el.textContent || '').trim().match(/^(\d+)(.*)$/);
        if (!m) { return; }
        el.dataset.counted = '1';
        var target = parseInt(m[1], 10), suffix = m[2] || '';
        if (reduceMotion || target === 0) { el.textContent = target + suffix; return; }
        var start = performance.now(), dur = 1100;
        var step = function (now) {
            var p = Math.min((now - start) / dur, 1);
            var val = Math.round(target * (0.5 - Math.cos(p * Math.PI) / 2));
            el.textContent = val + suffix;
            if (p < 1) { window.requestAnimationFrame(step); }
        };
        window.requestAnimationFrame(step);
    };

    // Belépő animációk (lépcsőzetes)
    ['.card-grid', '.reference-grid', '.team-grid', '.kpi-grid'].forEach(function (sel) {
        document.querySelectorAll(sel).forEach(function (grid) {
            grid.querySelectorAll('.reveal').forEach(function (el, i) {
                el.style.transitionDelay = Math.min(i * 70, 420) + 'ms';
            });
        });
    });

    var reveals = document.querySelectorAll('.reveal');
    var reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (reduce || !('IntersectionObserver' in window)) {
        reveals.forEach(function (el) {
            el.classList.add('is-visible');
            el.querySelectorAll('.stat-n').forEach(animateCount);
        });
    } else {
        var io = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    entry.target.querySelectorAll('.stat-n').forEach(animateCount);
                    var t = entry.target;
                    setTimeout(function () { t.style.transitionDelay = ''; }, 700);
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.12 });
        reveals.forEach(function (el) { io.observe(el); });
    }
})();
