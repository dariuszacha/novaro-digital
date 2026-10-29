# Novaro Digital – GTM setup (GTM-WCJP88J8)

This folder is blocked from the public website (`.htaccess`). It holds the importable container
`novaro-gtm-container.json` and these instructions.

IDs: **GTM** GTM-WCJP88J8 · **GA4** G-Y5Y06G1R8K · **Meta Pixel** 2429915440872171

## What the website already does

- **Consent Mode v2 defaults** (in `<head>`, before GTM): `ad_storage`, `ad_user_data`, `ad_personalization`,
  `analytics_storage` = `denied`. A saved "Sutinku" choice is re-applied as `granted` on later visits
  (localStorage key `novaro_cookie_consent`).
- **Cookie banner** (`tracking.js`): "Sutinku" → all four `granted`; "Atsisakyti" → stay `denied`. Both push
  `{ event: 'cookie_consent_update', consent_choice: 'granted' | 'denied' }`. "Slapukų nustatymai" in the footer
  reopens the banner.
- **dataLayer events**

  | Event | When | Parameters |
  |---|---|---|
  | `generate_lead` | after the audit form is sent successfully and the thank-you message shows | `form_id: 'nemokamas-auditas'` |
  | `phone_click` | click on any `tel:` link | `link_url` |
  | `email_click` | click on any `mailto:` link | `link_url` |
  | `calculator_used` | first change of any calculator input (once per page load) | – |

## Import the container (recommended)

1. GTM → container **GTM-WCJP88J8** → **Admin** → **Import Container**.
2. Choose `novaro-gtm-container.json`.
3. Workspace: **New** (e.g. "Novaro tracking v1"). Import option: **Merge → Rename conflicting tags, triggers and variables**.
4. Confirm. Check the 8 tags, 6 triggers and 4 variables below, then test (see below) and **Submit → Publish**.

## What the container contains (also the manual build steps)

**Variables**
- `Const - GA4 Measurement ID` (Constant) = `G-Y5Y06G1R8K`
- `Const - Meta Pixel ID` (Constant) = `2429915440872171`
- `DLV - form_id` (Data Layer Variable, name `form_id`)
- `DLV - link_url` (Data Layer Variable, name `link_url`)

**Triggers** (all Custom Event)
- `CE - cookie_consent_update` – event name `cookie_consent_update`
- `CE - generate_lead`, `CE - phone_click`, `CE - email_click`, `CE - calculator_used` – event names as written
- `CE - phone_click or email_click` – event name `^(phone_click|email_click)$`, "Use regex matching" on

**Tags**

| Tag | Type | Fires on | Consent (Advanced Settings → Consent Settings → Require additional consent) | Firing option |
|---|---|---|---|---|
| Google tag - G-Y5Y06G1R8K | Google tag, Tag ID `{{Const - GA4 Measurement ID}}` | All Pages + CE - cookie_consent_update | `analytics_storage` | **Unlimited** |
| GA4 event - generate_lead | GA4 Event, Measurement ID `{{Const - GA4 Measurement ID}}`, event `generate_lead`, param `form_id` = `{{DLV - form_id}}` | CE - generate_lead | `analytics_storage` | Once per event |
| GA4 event - phone_click | GA4 Event, event `phone_click`, param `link_url` = `{{DLV - link_url}}` | CE - phone_click | `analytics_storage` | Once per event |
| GA4 event - email_click | GA4 Event, event `email_click`, param `link_url` = `{{DLV - link_url}}` | CE - email_click | `analytics_storage` | Once per event |
| GA4 event - calculator_used | GA4 Event, event `calculator_used` | CE - calculator_used | `analytics_storage` | Once per event |
| Meta Pixel - Base | Custom HTML: Meta base code with `fbq('init', '{{Const - Meta Pixel ID}}')` + `PageView` | All Pages + CE - cookie_consent_update | `ad_storage` | **Unlimited** |
| Meta Pixel - Lead | Custom HTML: `fbq('track', 'Lead')` | CE - generate_lead | `ad_storage` | Once per event; no tag sequencing |
| Meta Pixel - Contact | Custom HTML: `fbq('track', 'Contact')` | CE - phone_click or email_click | `ad_storage` | Once per event; no tag sequencing |

Why the extra `CE - cookie_consent_update` trigger: if a visitor clicks "Sutinku" after the page has loaded,
the Google tag and Meta base code (blocked on page load) fire immediately instead of waiting for the next page.

Why **Unlimited** (not "Once per page") on those two tags: GTM counts the consent-blocked attempt on All Pages
as the tag's one firing, so with "Once per page" they would not fire on `cookie_consent_update`. Unlimited does
not cause duplicates: on All Pages they are blocked until consent, and the site pushes `cookie_consent_update`
only when consent actually changes to granted (not on later visits, not on a repeated "Sutinku").
For the same reason Lead/Contact must **not** use tag sequencing with the base tag (it would re-send PageView);
by the time a visitor can trigger them, the base code has already loaded.

**Consent mode choice: basic.** Every tag requires consent, so nothing is sent to Google or Meta before "Sutinku".
This matches the privacy policy ("only with consent"). If you later want GA4 *advanced* consent mode
(cookieless pings + modelling for visitors who decline), remove the `analytics_storage` requirement from the
Google tag and GA4 event tags, keep the All Pages trigger, and update the privacy policy accordingly.

## GA4: mark generate_lead as a key event

Key events are set in GA4, not in GTM:
GA4 → **Admin** → **Data display** → **Events** → find `generate_lead` → **Mark as key event**.
If it is not listed yet (it appears after the first one is received), go to **Key events** → **New key event**
→ name `generate_lead` → Save.

Optional: register `form_id` and `link_url` as event-scoped custom dimensions (Admin → Custom definitions)
to see them in reports.

## Testing

**GTM Preview / Tag Assistant**
1. In the workspace click **Preview**, enter `https://novarodigital.lt/`, Connect. The site opens in a new tab
   (allow pop-ups); Tag Assistant shows "Connected".
2. Fresh state: in the site tab, clear the site data (or use a private window) so the banner shows.
3. Before clicking the banner, select the **Consent** tab on the first event: Default = all four `denied`.
   Tags: Google tag and Meta Pixel - Base are **Not fired** (blocked by consent).
4. Click **Sutinku** → a `cookie_consent_update` event appears; Consent tab shows **Update = granted**;
   Google tag and Meta Pixel - Base **fired**.
5. Change a calculator slider → `calculator_used`; GA4 event tag fired (only once).
6. Click the phone and email links → `phone_click` / `email_click`; GA4 tags + Meta Pixel - Contact fired.
7. Send the audit form → after the thank-you message, `generate_lead`; GA4 tag + Meta Pixel - Lead fired.
8. Repeat with **Atsisakyti** in a new private window: no Google/Meta tags fire on any event.
9. GA4 → **Admin → DebugView** shows the events from the preview session.

**Meta Events Manager → Test events**
1. Events Manager → dataset/pixel **2429915440872171** → **Test events** → enter `https://novarodigital.lt/` → **Open website**.
2. In that window click **Sutinku**. Within seconds Test events shows **PageView**.
3. Click the phone or email link → **Contact**. Send the form → **Lead**.
4. Decline instead → nothing should appear.
5. The Meta Pixel Helper browser extension can also confirm the pixel and events.
