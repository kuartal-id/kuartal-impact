# Handoff log

## 2026-10-14: restyle to the kuartal.id website style (Grok Bot)
- Owner: scrap the custom sci-fi look; follow kuartal.id exactly, only recolour to the Kuartal Impact logos.
- `resources/css/app.css` is kuartal.id's stylesheet (via kuartal-id/kuartalgroup PR #7-#9) with the same token
  names, remapped: navy -> forest #1a4e41, green-400 -> neon #9eff1f (fills under black text only), green-500/600 ->
  forest (accent text on light), gray-bg -> cream #f3eee3, surface-dark -> black, -alt -> dark forest #0c2620.
  axe colour-contrast: 0 violations (home, Mangrow fund, about, contact; light + dark).
- Chrome as on kuartal.id: sticky see-through header, round search + dark-mode buttons, slide-over search
  (`resources/js/search-panel.js`, indexes nav links + funds), scrolling mobile menu (kuartal-id PR #5 fix),
  cream social strip + big-logo footer, centred title band (`x-page-hero`) on inner pages, Poppins/Arial.
- Theme: light/dark only, shared `kuartal-theme` key, first visit follows the OS (as kuartal.id). Removed the
  neon/dark/neutral switcher, `theme-boot.js`, contour canvas (`field.js`), `x-motif` line drawings, contour SVG and
  `fund_motifs` config. Logo: black in light, neon in dark, white over the hero footage.
- Home hero: kuartal.id's Rinjani footage, streamed from https://kuartal.id/videos/kuartal-hero.mp4, with the poster
  frame `public/images/hero-poster.jpg` (same file kuartalgroup uses). No stock media.
- Fund cards: kuartal.id-style rounded cards (fund photo from admin, or a plain forest panel with the name).
- Tests: `ThemeSwitcherTest` replaced by `ThemeToggleTest`.

## 2026-10-14: real logos, three themes, nature line work (Grok Bot)
- Logos: owner artwork (neon + black) trimmed to 1283×713, exported PNG + lossless WebP; white version = black
  recoloured. Old navy-derived wordmark and placeholder favicon removed. Favicons (ico/svg/16–512 png) are the
  circled X mark cropped from the logo, lime on black. Mark-only PNGs in `public/images/logo/kuartal-impact-mark-*`.
- Themes: `neon` (lime ground, black type/logo, white panels), `dark` (default, black canvas, lime), `neutral`
  (cream #f3eee3 + forest #1a4e41). Tokens in `resources/css/app.css` ([data-theme]). Switcher component
  `x-theme-switcher` in header, mobile menu and footer. Logic in `resources/js/theme-boot.js` (inlined before
  paint + bundled): stores `kuartal-impact-theme`, writes shared `kuartal-theme` light/dark; if another Kuartal
  site flips the shared mode, that wins (light -> last light theme here or neutral). axe colour-contrast: 0
  violations on 9 pages × 3 themes × 2 viewports.
- Nature: hero is a live topographic contour map (canvas, ~15 fps, paused off-screen, still frame on reduced
  motion); static tileable contour texture `public/images/motifs/contours.svg`; hand-drawn SVG motifs
  `x-motif` (mangrove / coral / energy / healing / roots) on fund cards, fund pages (mapped by slug in
  `config/kuartalimpact.php` fund_motifs) and page heroes; forest `.k-band` sections.
- Fixed pre-existing JS error: desktop dropdown script looked for the panel in the wrong parent, throwing on
  every click.
- Tests: `tests/Feature/ThemeSwitcherTest.php` (markup, logos, persistence via Node running theme-boot.js).

## 2026-10-14: initial build (Grok Bot)
- Rebuilt from kuartal-id/kuartalgroup (Kuartal ID login, newsroom/media admin, SiteEntry content, deploy pipeline);
  investor portal, documents, financials and funds-ledger code removed.
- Pages: /, /about, /focus-areas, /programmes, /programmes/{slug}, /portfolio, /newsroom, /contact. Old WP links
  /how-we-invest and /our-team redirect into /about.
- Data migration seeds the four running funds (one-line descriptions only) and the old site's founder quote and
  how-we-invest / team paragraphs (verbatim). Old portfolio (PHCHC, Ecoexplore.id, Kuartal Energy) NOT imported.
- Logo: no black/#9eff1f artwork existed; `public/images/logo/kuartal-impact-{lime,black}` are recolours of the old
  navy wordmark (alpha kept). Replace with owner artwork when available. Favicon is a placeholder ⊗ mark.
- Unfinished: Kuartal ID client + entitlement (owner), hPanel Git cutover (owner; see docs/DEPLOY.md), fund
  texts/figures/projects, team, partners, Kuartal Impact social accounts (footer uses Kuartal's).
