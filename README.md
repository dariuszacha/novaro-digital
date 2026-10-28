# Svetainės failai

| Failas | Paskirtis |
|---|---|
| `index.html` | Visas puslapis: HTML, CSS (`<style>`) ir JavaScript (`<script>`) |
| `contact.php` | Kontaktų forma – patikrina duomenis ir išsiunčia laišką |
| `.htaccess` | Apache nustatymai: HTTPS, saugumo antraštės, podėlis |
| `favicon.svg` | Naršyklės skirtuko ikona |
| `apple-touch-icon.png`, `icon-192.png`, `icon-512.png` | Ikonos telefonams (pradžios ekranas) |
| `og-image.png` | Paveikslėlis, rodomas dalinantis nuoroda (1200×630) |
| `brand/novaro-logo-horizontal.png` | Logotipas el. pašto parašui |
| `site.webmanifest` | Svetainės pavadinimas ir spalvos telefonams |

## Ką pakeisti prieš įkeliant

> **Tik tikri duomenys.** Skaičiai, rezultatai ir kontaktai svetainėje – tikri (atnaujinta 2026-10-28). Nepridėkite skaičių ar klientų atvejų, kurių negalite įrodyti.

1. **Kontaktai** – `index.html`: telefonas „+370 606 79982“ (`tel:+37060679982`), el. paštas info@novarodigital.lt; tas pats `<head>` JSON-LD bloke. Rekvizitai (MB Wellbeing solutions) – poraštėje ir `privatumo-politika/index.html`.
2. **Skaičiai ir rezultatai** – pradžios statistika (27 užklausos, 2 d. d., 0, 100 %) ir viena „Pirmieji rezultatai“ kortelė. Naujus rezultatus skelbkite tik kliento leidimu.
3. **Kainos** – sekcija `#kainos`, tekstas patvirtintas `novaro-kainos-sekcija.md` (v1); keisti tik su nauja patvirtinta versija. Kainos rodomos be PVM. Bendrajame DUK kainų nebėra, kad nesikirstų su kainų sekcija; `<head>` JSON-LD bloke – tik bendro DUK klausimai.
4. **Spalvos** – `index.html` viršuje, `:root { --lime: …; --pink: …; --blue: … }`.
5. **Paslėpta nuo Google** – kol duomenys pavyzdiniai, svetainė pažymėta `noindex`. Paleidžiant: `index.html` pakeiskite `<meta name="robots">` į `index,follow` ir ištrinkite `X-Robots-Tag` eilutę iš `.htaccess`.
4. **`contact.php`** – viršuje įrašykite `$TO_EMAIL` ir `$FROM_EMAIL` (jūsų domeno adresas).
5. **Google Analytics (nebūtina)** – `index.html` apačioje `const GA_ID = 'G-...'`. Kol tuščia, analitika ir slapukų juosta neįjungiamos.
6. **`og-image.png`** – 1200×630 px, sukurtas iš prekės ženklo šablono `templates/og-image.html`. Jame skaičiai „27“ ir „0“ – pakeitus statistiką, atnaujinkite ir jį.

## Įkėlimas

1. Nusipirkite hostingą su PHP ir domeną.
2. Įkelkite visus failus į `public_html` (failų tvarkyklė arba FileZilla). `.htaccess` failas paslėptas – įjunkite „rodyti paslėptus failus“.
3. Hostingo valdymo skydelyje įjunkite nemokamą SSL (Let's Encrypt).
4. Išbandykite kontaktų formą – laiškas turi ateiti į `$TO_EMAIL`.
