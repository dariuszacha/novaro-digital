/* Novaro Digital – slapukų sutikimas (Google Consent Mode v2) ir paspaudimų įvykiai Google Tag Manager.
   Numatytasis „denied“ nustatomas kiekvieno puslapio <head>, prieš GTM. Šis failas valdo slapukų juostą
   ir siunčia įvykius į dataLayer. GA4 ir Meta Pixel įkeliami tik per GTM. */
(function () {
  'use strict';

  var KEY = 'novaro_cookie_consent'; // tas pats raktas kaip <head> skripte
  window.dataLayer = window.dataLayer || [];
  var gtag = window.gtag || function () { window.dataLayer.push(arguments); };

  function read() { try { return localStorage.getItem(KEY); } catch (e) { return null; } }
  function save(value) { try { localStorage.setItem(KEY, value); } catch (e) {} }

  // „Sutinku“ – visi keturi signalai „granted“, „Atsisakyti“ – lieka „denied“
  function apply(choice, previous) {
    var state = choice === 'granted' ? 'granted' : 'denied';
    var before = previous === 'granted' ? 'granted' : 'denied';
    gtag('consent', 'update', { ad_storage: state, ad_user_data: state, ad_personalization: state, analytics_storage: state });
    // GTM žymos, laukusios sutikimo, suveikia nuo šio įvykio (žr. gtm/README.md).
    // Siunčiame tik kai sutikimas tikrai pasikeitė – pakartotinis „Sutinku“ nedubliuoja page_view / PageView.
    if (state !== before) window.dataLayer.push({ event: 'cookie_consent_update', consent_choice: state });
  }

  var banner = document.getElementById('cookie');
  if (banner) {
    banner.querySelectorAll('[data-consent]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var choice = btn.getAttribute('data-consent');
        var previous = read();
        save(choice);
        apply(choice, previous);
        banner.classList.remove('show');
      });
    });
    // „Slapukų nustatymai“ poraštėje – vėl parodo juostą, kad sutikimą būtų galima pakeisti ar atšaukti
    document.querySelectorAll('[data-cookie-settings]').forEach(function (btn) {
      btn.hidden = false;
      btn.addEventListener('click', function () { banner.classList.add('show'); });
    });
    if (!read()) banner.classList.add('show');
  }

  // Paspaudimai ant tel: ir mailto: nuorodų
  document.addEventListener('click', function (e) {
    var link = e.target.closest && e.target.closest('a[href^="tel:"], a[href^="mailto:"]');
    if (!link) return;
    var href = link.getAttribute('href');
    window.dataLayer.push({ event: href.indexOf('tel:') === 0 ? 'phone_click' : 'email_click', link_url: href });
  });
})();
