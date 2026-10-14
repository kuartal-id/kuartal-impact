# Handoff log

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
