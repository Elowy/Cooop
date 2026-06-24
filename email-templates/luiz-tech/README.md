# Luiz-Tech &mdash; HTML e-mail sablon és aláírás

A [luiz-tech.hu](https://luiz-tech.hu) arculatára épülő, e-mail kliensekben
megbízhatóan megjelenő HTML eszközök.

| Fájl | Mire való |
|---|---|
| `email-template.html` | Újrahasználható **hírlevél / kampány sablon** (600px, sötét téma) |
| `signature.html` | **E-mail aláírás**, fehér szerkesztő-háttérre optimalizálva |

## Arculati elemek (a forrás-CSS-ből)

- **Háttér:** `#0a0e17`, `#0d1320`, `#111827`
- **Szöveg:** `#e6edf3` (fő), `#9aa7b8` (halvány), `#6b7889` (dim)
- **Akcentusok:** cián `#38e1ff`, indigó `#6c7bff`, lila `#b46bff`, zöld `#00ffa3`
- **Gradiens:** `linear-gradient(135deg, #38e1ff, #6c7bff, #b46bff)`
- **Betűk:** Inter (szöveg) + JetBrains Mono (logó/kód)
- **Logó:** `<Luiz-Tech/>` &middot; **Szlogen:** „Kódoljuk a vállalkozásod jövőjét”

---

## E-mail sablon használata

1. Nyisd meg az `email-template.html`-t és töltsd ki a tartalmat:
   - a fő cím, bevezető szöveg és a CTA gomb felirata/linkje,
   - a három szolgáltatás-blokk (szabadon átírható vagy törölhető),
   - a `{{leiratkozas_url}}` és `{{webes_verzio_url}}` helyőrzők a hírlevélküldő
     rendszer (pl. Mailchimp, MailerLite, Listmonk) megfelelő tokenjeire.
2. A teljes fájlt töltsd fel a hírlevélküldőbe **HTML forrásként**.

**Miért így épült:** táblázat-alapú elrendezés, *inline* stílusok, Outlook (MSO)
feltételes kód a gombhoz (VML „bulletproof button”), `600px` szélesség,
mobil töréspont `620px`-nél, rejtett előnézeti (preheader) szöveg, valamint
`color-scheme` meta a sötét mód kezeléséhez. Ezek nélkül a Gmail/Outlook
szétesne a komplex CSS-en.

> Tipp: éles küldés előtt teszteld több kliensben (Gmail, Outlook, Apple Mail,
> mobil). Erre jó pl. a [Litmus](https://litmus.com) vagy az
> [Email on Acid](https://www.emailonacid.com).

## Aláírás használata

### Gmail
Beállítások (fogaskerék) → **Összes beállítás megtekintése** → *Általános* →
**Aláírás** → *Létrehozás*. Nyisd meg a `signature.html`-t böngészőben,
jelöld ki a megjelenített aláírást (Ctrl/Cmd+A), másold, és illeszd be a mezőbe.

### Outlook (asztali)
Fájl → Beállítások → Levél → **Aláírások**. Az Outlook nem fogad el közvetlen
HTML-importot, ezért nyisd meg a fájlt böngészőben, és a megjelenített aláírást
másold be. (Vagy szerkeszd a `Signatures` mappa `.htm` fájlját.)

### Cse* helyőrzők
- `{{NEV}}` &rarr; a munkatárs neve
- `{{POZICIO}}` &rarr; pozíció / titulus

A többi adat (telefon, e-mail, web) fix, az arculathoz igazítva.

**Miért így épült:** az aláírás fehér szerkesztő-háttérre készült (oda kerül
beillesztésre), külső kép **nélkül** (a céges logót kód-stílusú szöveg adja,
így sosem törik el és nem blokkolja a kliens), és minden stílus *inline*, mert
a beillesztéskor a `<style>` blokkok elvesznek.

## Helyi előnézet

```bash
# a repo gyökeréből
python3 -m http.server 8080
# majd: http://localhost:8080/email-templates/luiz-tech/email-template.html
#       http://localhost:8080/email-templates/luiz-tech/signature.html
```
