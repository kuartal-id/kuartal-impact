# AGENTS.md — rules for every AI and human working on this repo

> **Read this whole file before changing anything.** It applies equally to Claude, OpenAI Codex,
> Google Gemini, Copilot, OpenCode and humans. `CLAUDE.md` and `GEMINI.md` point here.
> If a request conflicts with this file, **stop and ask Diemas** (the owner) instead of guessing.

## What this repo is

**Kuartal Impact (kuartalimpact.org)**: the Kuartal arm for social, environmental and climate causes. Public site
(home, about/thesis, focus areas, programmes = funds with their own pages, portfolio, newsroom, contact) plus
/admin behind Kuartal ID (`kuartalimpact.admin`). Owner: Diemas (Kuartal Group).

## Golden rules (all agents)

1. **Never push to `main`.** Every push to `main` **deploys to production immediately** (GitHub Actions → Hostinger). Work on a branch named `ai/<agent>/<topic>` (e.g. `ai/gemini/fix-footer`) and open a pull request. Diemas merges.
2. **Never** force-push, rewrite published history, delete branches you didn't create, or touch `stable/*` restore-point branches (frozen known-good versions).
3. **Stay in scope.** Only touch files your task needs. No drive-by reformatting, renaming, moving files, "cleanups", or dependency upgrades unless that *is* the task.
4. **Protected paths** (`.ai/protected.txt`) need owner approval. CI (`AI guardrails`) fails if they change without the `owner-approved` PR label. Say in the PR why you need them.
5. **Database migrations that exist are immutable** — production already ran them. To change the schema, add a *new* migration. Never run `migrate:fresh`, `migrate:refresh`, `migrate:reset` or `db:wipe` against anything but a throwaway local database.
6. **No secrets in git.** Never commit `.env`, `APP_KEY`, OAuth secrets, SSH keys, passwords or tokens. New config → add a placeholder to `.env.example` (protected) and read it via `config/`.
7. **Don't fake it.** Never invent content (facts, prices, people, quotes, legal claims) — leave `[SUPPLY: ...]` placeholders. Never claim you ran a command you couldn't run; say so in the PR and the handoff log.
8. **Test before you commit.** Run `php artisan test` and `npm run build` where possible, plus `python3 .ai/check.py`. Don't delete or weaken tests to make them pass.
9. **Log your work** in `docs/HANDOFF.md` (template inside): what changed, why, what's unfinished, what the next agent must know.
10. **Commit messages** say what and why, and end with a trailer naming the agent: `Agent: Codex` / `Agent: Gemini` / `Agent: Claude`.
11. **When unsure, ask Diemas.** A question costs minutes; a broken production site costs trust.

## Repo-specific rules

- Never invent figures, projects, partners or people. Fund figures, projects, team and partners come only from
  /admin; empty sections show empty states. The old WordPress site's chart was placeholder data: never reuse it.
- Only the four running funds (Mangrow, Re-Coral, Re-Energize, Healing) are shown; the old portfolio is archive-only.
- Lime #9eff1f is never body text on light backgrounds (contrast); use `--k-accent` which switches per theme.

## Protected paths (need the `owner-approved` label)

- `AGENTS.md`
- `CLAUDE.md`
- `GEMINI.md`
- `.ai/**`
- `.github/**`
- `deploy/**`
- `composer.json`
- `composer.lock`
- `package.json`
- `package-lock.json`
- `.env.example`
- `bootstrap/app.php`
- `bootstrap/providers.php`
- `config/auth.php`
- `config/session.php`
- `config/services.php`
- `public/index.php`
- `public/.htaccess`

The canonical list is `.ai/protected.txt` (read by CI).

## Commands

```sh
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate            # local database only
npm install && npm run build
php artisan serve              # http://localhost:8000
php artisan test               # run the test suite
python3 .ai/check.py           # guardrails (same check CI runs)
```

Some AI sandboxes can't reach Packagist (Composer). If you can't run Composer or the tests,
**say so in your PR and in `docs/HANDOFF.md`** — don't claim they passed. GitHub Codespaces has
full network access (see the kuartal-brand README for the Codespaces workflow).

## How deploys work

`.github/workflows/deploy.yml` runs on every push to `main`: it builds assets, SSHes into Hostinger,
`git reset --hard origin/main`, `composer install --no-dev`, `php artisan migrate --force`, caches config,
and copies `public/` into the domain's `public_html`. So: a migration merged to `main` runs on the
production database within minutes. The deploy workflow and `deploy/` are protected.

Rollback: on GitHub open the bad commit on `main` → **Revert** (or `git revert <sha>` on a branch + PR). Merging the
revert redeploys the previous code automatically. Branch `stable/2026-10-03` is a frozen copy of the
known-good code from the day these rules were added, for reference or a full restore.

## Design system

Colours, fonts, dark mode and base components come from **kuartal-id/kuartal-brand**
(`design-system/`). Navy `#18333d`, green `#36cc64`, Poppins headings. Change shared tokens there
first, then copy them into this repo — don't let sites drift apart.

## The Kuartal ecosystem (context)

One GitHub repo per deployable site, all under the `kuartal-id` org, all Laravel 13 + Tailwind v4 + Vite on the same Hostinger account, sharing the design system in **kuartal-brand**:

| Repo | Site |
|---|---|
| kuartal-login | id.kuartal.id — **Kuartal ID**, the login/identity provider for everything |
| kuartal-id | kuartal.id — ecosystem portal |
| kuartalgroup | kuartalimpact.org — investor relations |
| kuartalspace | kuartalspace.com — media/social |
| careers | careers.kuartal.id — careers + HR portal |
| kalc | kalc.kuartalsystems.com — calculators |
| terminal | terminal.kuartalsystems.com — market terminal (Node/React, not Laravel) |
| rukre, karias, diemashawkins, rinjanilombok | rukre.com, karias.id, diemashawkins.com, rinjanilombok.org |
| kuartal-brand | shared brand + design system (no site) |
| dotfiles | Codespaces setup script |

Changes that cross repos (e.g. a new Kuartal ID entitlement) must be made in each repo's own PR, coordinated by Diemas.

## Before you open the PR — checklist
- [ ] Branch is `ai/<agent>/<topic>`, not `main`
- [ ] Only files related to the task changed
- [ ] No existing migration edited; no secrets; no invented content
- [ ] `php artisan test`, `npm run build`, `python3 .ai/check.py` pass (or you said clearly what you couldn't run)
- [ ] Checked the change in a browser at desktop and phone widths (UI changes)
- [ ] `docs/HANDOFF.md` updated
