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

    // Süti-hozzájárulás (kategória-alapú)
    var cookieWall = document.querySelector('[data-cookie-wall]');
    if (cookieWall) {
        var cookiePrefs = cookieWall.querySelector('[data-cookie-prefs]');
        var cookieSave = cookieWall.querySelector('[data-cookie-save]');
        var cookiePrefsToggle = cookieWall.querySelector('[data-cookie-prefs-toggle]');

        var setConsentCookie = function (value) {
            document.cookie = 'nt_consent=' + value + ';path=/;max-age=' + (60 * 60 * 24 * 180) + ';samesite=lax';
        };
        var encodeConsent = function (analytics, marketing) {
            var t = ['necessary'];
            if (analytics) { t.push('analytics'); }
            if (marketing) { t.push('marketing'); }
            return t.join('-');
        };
        var loadGa = function () {
            if (!window.NT_GA || window.__ntGa) { return; }
            window.__ntGa = true;
            var s = document.createElement('script');
            s.async = true;
            s.src = 'https://www.googletagmanager.com/gtag/js?id=' + window.NT_GA;
            document.head.appendChild(s);
            window.dataLayer = window.dataLayer || [];
            window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
            window.gtag('js', new Date());
            window.gtag('config', window.NT_GA);
        };
        var loadFbq = function () {
            if (!window.NT_FBQ || window.__ntFbq) { return; }
            window.__ntFbq = true;
            !function (f, b, e, v, n, t, s) { if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments); }; if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = []; t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s); }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
            window.fbq('init', window.NT_FBQ);
            window.fbq('track', 'PageView');
        };
        // A frissen adott hozzájárulást azonnal érvényesítjük; a megvont
        // kategóriák a böngésző-tárból a következő oldalbetöltéskor tűnnek el.
        var applyConsent = function (analytics, marketing) {
            if (analytics) { loadGa(); }
            if (marketing) { loadFbq(); }
        };
        var closeWall = function () {
            cookieWall.classList.add('is-hidden');
            setTimeout(function () {
                cookieWall.classList.add('is-dismissed');
                cookieWall.classList.remove('is-hidden');
            }, 300);
        };
        var catChecked = function (cat) {
            var el = cookieWall.querySelector('[data-cookie-cat="' + cat + '"]');
            return !!(el && el.checked);
        };
        var openPrefs = function () {
            if (cookiePrefs) { cookiePrefs.hidden = false; }
            if (cookieSave) { cookieSave.hidden = false; }
            if (cookiePrefsToggle) { cookiePrefsToggle.hidden = true; }
        };

        if (cookiePrefsToggle) {
            cookiePrefsToggle.addEventListener('click', openPrefs);
        }

        // Gyors választás: összes / csak a szükségesek.
        cookieWall.querySelectorAll('[data-cookie-accept]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var all = btn.getAttribute('data-cookie-accept') === 'all';
                setConsentCookie(encodeConsent(all, all));
                applyConsent(all, all);
                closeWall();
            });
        });

        // Granuláris mentés a kapcsolók alapján.
        if (cookieSave) {
            cookieSave.addEventListener('click', function () {
                var a = catChecked('analytics');
                var m = catChecked('marketing');
                setConsentCookie(encodeConsent(a, m));
                applyConsent(a, m);
                closeWall();
            });
        }

        // Lábléc / bárhol: "Cookie-beállítások" – a panel újranyitása.
        document.querySelectorAll('[data-cookie-open]').forEach(function (link) {
            link.addEventListener('click', function (e) {
                e.preventDefault();
                cookieWall.classList.remove('is-dismissed', 'is-hidden');
                openPrefs();
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
            var linkEl = refModal.querySelector('[data-ref-link]');
            if (linkEl) {
                if (r.url) { linkEl.href = r.url; linkEl.hidden = false; }
                else { linkEl.removeAttribute('href'); linkEl.hidden = true; }
            }
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

    // Webshop rendezés: választáskor automatikus beküldés (no-JS esetén marad a „Rendez" gomb)
    document.querySelectorAll('select[data-autosubmit]').forEach(function (sel) {
        sel.addEventListener('change', function () {
            if (sel.form) { sel.form.submit(); }
        });
    });

    // Kosárba rakás AJAX-szal: a vásárló az oldalon marad, buborék + kosár-jelvény frissül
    var toastTimer = null;
    var showToast = function (text) {
        var toast = document.querySelector('[data-toast]');
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast';
            toast.setAttribute('data-toast', '');
            toast.setAttribute('role', 'status');
            toast.setAttribute('aria-live', 'polite');
            document.body.appendChild(toast);
        }
        toast.textContent = text;
        void toast.offsetWidth; // reflow a belépő animációhoz
        toast.classList.add('is-visible');
        if (toastTimer) { window.clearTimeout(toastTimer); }
        toastTimer = window.setTimeout(function () { toast.classList.remove('is-visible'); }, 2600);
    };
    var updateCartBadge = function (count) {
        document.querySelectorAll('[data-cart-badge]').forEach(function (b) {
            b.textContent = String(count);
            b.hidden = count <= 0;
        });
        document.querySelectorAll('[data-cart-link]').forEach(function (l) {
            l.classList.toggle('has-items', count > 0);
        });
    };
    document.querySelectorAll('form.add-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!window.fetch) { return; } // fetch hiányában marad a hagyományos beküldés
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            if (btn) { btn.disabled = true; }
            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            }).then(function (r) { return r.json(); }).then(function (data) {
                if (data && data.ok) {
                    updateCartBadge(data.count);
                    showToast((data.name || 'Termék') + ' a kosárban');
                } else {
                    showToast('A kosárba helyezés nem sikerült.');
                }
                if (btn) { btn.disabled = false; }
            }).catch(function () {
                form.submit(); // hálózati hiba: vissza a normál beküldésre
            });
        });
    });

    // Termékoldal galéria: bélyegkép kattintásra kicseréli a fő képet
    var galleryMain = document.getElementById('gallery-main-img');
    if (galleryMain) {
        document.querySelectorAll('[data-gallery-thumb]').forEach(function (thumb) {
            thumb.addEventListener('click', function () {
                var src = thumb.getAttribute('data-gallery-thumb');
                if (src) { galleryMain.src = src; }
                document.querySelectorAll('.gallery-thumb').forEach(function (t) { t.classList.remove('is-active'); });
                thumb.classList.add('is-active');
            });
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
