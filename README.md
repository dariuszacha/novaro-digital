# Svetainės failai

| Failas | Paskirtis |
|---|---|
| `index.html` | Visas puslapis: HTML, CSS (`<style>`) ir JavaScript (`<script>`) |
| `contact.php` | Kontaktų forma – patikrina duomenis ir išsiunčia laišką |
| `.htaccess` | Apache nustatymai: HTTPS, saugumo antraštės, podėlis |
| `favicon.svg` | Naršyklės skirtuko ikona |
| `site.webmanifest` | Svetainės pavadinimas ir spalvos telefonams |

## Ką pakeisti prieš įkeliant

> **Visi duomenys yra pavyzdiniai.** „Novaro Digital“ – išgalvotas pavadinimas, kontaktai, skaičiai, klientų atvejai ir kainos – sugalvoti. Prieš skelbiant pakeiskite juos tikrais.

1. **`index.html`** – ieškokite „Novaro Digital“, „novarodigital.lt“, „+370 612 34567“ / „+37061234567“, logotipo raidės „N“; skyrelis `<head>` pažymėtas `KEISTI`.
2. **Skaičiai ir rezultatai** – pradžios statistika (85+, €2,3 mln., 4,8×, 24 val.) ir „Rezultatai“ kortelės pažymėtos `PAVYZDINIAI DUOMENYS`. Įrašykite tik tikrus savo duomenis.
3. **DUK** – kainos (nuo 390 €, 690 €, 1 490 €, 450 €) ir minimalus biudžetas – pavyzdžiai; tie patys klausimai yra ir `<head>` JSON-LD bloke.
4. **Spalvos** – `index.html` viršuje, `:root { --accent: … }`.
4. **`contact.php`** – viršuje įrašykite `$TO_EMAIL` ir `$FROM_EMAIL` (jūsų domeno adresas).
5. **Google Analytics (nebūtina)** – `index.html` apačioje `const GA_ID = 'G-...'`. Kol tuščia, analitika ir slapukų juosta neįjungiamos.
6. **`og-image.png`** – 1200×630 px paveikslėlis, rodomas dalinantis nuoroda socialiniuose tinkluose.

## Įkėlimas

1. Nusipirkite hostingą su PHP ir domeną.
2. Įkelkite visus failus į `public_html` (failų tvarkyklė arba FileZilla). `.htaccess` failas paslėptas – įjunkite „rodyti paslėptus failus“.
3. Hostingo valdymo skydelyje įjunkite nemokamą SSL (Let's Encrypt).
4. Išbandykite kontaktų formą – laiškas turi ateiti į `$TO_EMAIL`.
