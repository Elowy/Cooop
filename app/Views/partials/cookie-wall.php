<?php
/** @var array{set: bool, necessary: bool, analytics: bool, marketing: bool} $consent */
$consent = $consent ?? ['set' => false, 'necessary' => true, 'analytics' => false, 'marketing' => false];
?>
<div class="cookie-wall<?= !empty($consent['set']) ? ' is-dismissed' : '' ?>" data-cookie-wall<?= !empty($consent['set']) ? ' data-consent-set="1"' : '' ?>>
    <div class="cookie-card">
        <div class="cookie-scene" aria-hidden="true">
            <svg viewBox="0 0 360 140" xmlns="http://www.w3.org/2000/svg" preserveAspectRatio="xMidYMid slice">
                <defs>
                    <linearGradient id="cwsky" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0" stop-color="#f2e3b6"/><stop offset="1" stop-color="#e3cf93"/>
                    </linearGradient>
                </defs>
                <rect width="360" height="140" fill="url(#cwsky)"/>
                <circle cx="305" cy="32" r="17" fill="#f6cf6e"/>
                <rect y="104" width="360" height="36" fill="#6f5a2f"/>
                <rect y="99" width="360" height="8" fill="#7e8a3f"/>

                <g class="cw-tree"><rect x="58" y="70" width="6" height="34" fill="#5b3f1a"/><circle cx="61" cy="63" r="18" fill="#4d6a2e"/></g>
                <g class="cw-tree cw-tree-b"><rect x="120" y="74" width="6" height="30" fill="#5b3f1a"/><circle cx="123" cy="68" r="15" fill="#5a7a37"/></g>
                <g class="cw-tree"><rect x="246" y="72" width="6" height="32" fill="#5b3f1a"/><circle cx="249" cy="66" r="16" fill="#4d6a2e"/></g>

                <g class="cw-digger">
                    <circle cx="150" cy="62" r="5.5" fill="#2a1c0c"/>
                    <path d="M150 67 l0 18 M150 72 l10 -4 M150 85 l-4 14 M150 85 l4 14" stroke="#2a1c0c" stroke-width="3.5" fill="none" stroke-linecap="round"/>
                    <path d="M160 68 l9 19" stroke="#2a1c0c" stroke-width="2.5"/>
                    <rect x="166" y="85" width="6" height="9" rx="1" fill="#444" transform="rotate(22 169 90)"/>
                </g>

                <ellipse cx="201" cy="104" rx="11" ry="3.4" fill="#5b3f1a"/>
                <g class="cw-sapling">
                    <rect x="199.5" y="92" width="3" height="12" fill="#5b3f1a"/>
                    <circle cx="201" cy="89" r="6" fill="#6b8e4e"/>
                    <circle cx="205.5" cy="93" r="4" fill="#5a7a37"/>
                    <circle cx="196.5" cy="93" r="4" fill="#5a7a37"/>
                </g>

                <g class="cw-planter">
                    <circle cx="182" cy="62" r="6" fill="#2a1c0c"/>
                    <path d="M182 68 q-3 12 -9 20" stroke="#2a1c0c" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <path d="M182 72 q9 7 15 9" stroke="#2a1c0c" stroke-width="3" fill="none" stroke-linecap="round"/>
                    <path d="M173 88 l-4 16 M173 88 l3 16" stroke="#2a1c0c" stroke-width="4" fill="none" stroke-linecap="round"/>
                </g>
            </svg>
        </div>

        <div class="cookie-body">
            <h3>Sütiket használunk 🌱</h3>
            <p>A működéshez szükséges sütiket mindig használunk. A forgalommérési és marketing sütikhez a te hozzájárulásodat kérjük — kategóriánként eldöntheted, mit engedélyezel. Részletek az <a href="/adatkezeles">adatkezelési tájékoztatóban</a>.</p>

            <div class="cookie-prefs" data-cookie-prefs hidden>
                <label class="cookie-cat is-locked">
                    <span class="cookie-cat-info">
                        <span class="cookie-cat-name">Szükséges</span>
                        <span class="cookie-cat-desc">A bejelentkezéshez, kosárhoz és a biztonsághoz nélkülözhetetlen. Mindig aktív.</span>
                    </span>
                    <input type="checkbox" checked disabled aria-label="Szükséges sütik (mindig aktív)">
                    <span class="cookie-switch" aria-hidden="true"></span>
                </label>
                <label class="cookie-cat">
                    <span class="cookie-cat-info">
                        <span class="cookie-cat-name">Statisztika</span>
                        <span class="cookie-cat-desc">Google Analytics – névtelen forgalommérés, hogy lássuk, mi működik jól az oldalon.</span>
                    </span>
                    <input type="checkbox" data-cookie-cat="analytics"<?= !empty($consent['analytics']) ? ' checked' : '' ?> aria-label="Statisztika sütik engedélyezése">
                    <span class="cookie-switch" aria-hidden="true"></span>
                </label>
                <label class="cookie-cat">
                    <span class="cookie-cat-info">
                        <span class="cookie-cat-name">Marketing</span>
                        <span class="cookie-cat-desc">Közösségi és hirdetési sütik (pl. Facebook), a releváns ajánlatok megjelenítéséhez.</span>
                    </span>
                    <input type="checkbox" data-cookie-cat="marketing"<?= !empty($consent['marketing']) ? ' checked' : '' ?> aria-label="Marketing sütik engedélyezése">
                    <span class="cookie-switch" aria-hidden="true"></span>
                </label>
            </div>

            <div class="cookie-actions">
                <button type="button" class="btn btn--gold" data-cookie-accept="all">Összes elfogadása</button>
                <button type="button" class="btn btn--outline" data-cookie-accept="necessary">Csak a szükségesek</button>
                <button type="button" class="btn btn--ghost" data-cookie-prefs-toggle>Beállítások</button>
                <button type="button" class="btn btn--gold" data-cookie-save hidden>Kiválasztottak mentése</button>
            </div>
        </div>
    </div>
</div>
