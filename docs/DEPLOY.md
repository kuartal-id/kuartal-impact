# Deploying kuartalimpact.org (Hostinger Git deploy)

Same pattern as kuartalgroup / careers / kuartal.id: ONE `deploy` branch pulled by hPanel Git into
`~/domains/kuartalimpact.org/public_html`.

- push to `main` → `build-deploy-branch.yml` builds the release (Vite + vendor/, require-dev stripped) and force-pushes
  it to `deploy` as one orphan commit. With repo variable `HOSTINGER_GIT_DEPLOY=true` it then polls
  `/deploy-release.txt` and re-pushes (≤3×) until the site serves the run.
- Runtime state lives OUTSIDE public_html, in `~/kuartalimpact-data/`:
  `.env` (mode 600), `database.sqlite`, `private/` (uploads), `storage/` (logs, sessions, cache, views).
  `bootstrap/app.php` finds `kuartalimpact-data/.env` up to 4 levels above the app dir and uses it plus
  `kuartalimpact-data/storage`. Local dev/CI are unaffected.
- `php artisan app:post-deploy` migrates + rebuilds caches once per release. **Crons are broken account-wide**, so
  run it by hand after each deploy: `source ~/backups/kuartalimpact-cutover.sh && post_deploy`.
- Cutover/rollback helper: `deploy/server/kuartalimpact-cutover.sh` (installed at `~/backups/kuartalimpact-cutover.sh`).

## First cutover (owner)
1. Kuartal ID (id.kuartal.id, repo kuartal-id/kuartal-login): create confidential client **"Kuartal Impact"**
   (application `kuartalimpact-web`), redirect URI `https://kuartalimpact.org/auth/kuartal-id/callback`,
   scopes `openid profile email entitlements`; create entitlement **`kuartalimpact.admin`** and grant it to
   `kuartalgroup@gmail.com`.
2. SSH: `source ~/backups/kuartalimpact-cutover.sh && prepare`, then put the client id/secret into
   `~/kuartalimpact-data/.env` with an editor (never paste secrets in chat or git).
3. `backup_and_empty` (backs up whatever is left in public_html, dumps the WordPress DB if still present, empties it).
4. hPanel → Websites → kuartalimpact.org → Advanced → Git: repository `git@github.com:kuartal-id/kuartal-impact.git`
   (or HTTPS + deploy key), branch **`deploy`**, directory empty (= public_html). Add the deploy key hPanel shows to the
   GitHub repo (read-only). Enable auto-deploy webhook; press **Deploy**.
5. `post_deploy`, then `verify` (expects 200 for /, /up, /programmes…, 403/404 for /.env, logs, sqlite, vendor).
6. Set repo variable `HOSTINGER_GIT_DEPLOY=true` so CI waits for each release.
7. Point PHP for the domain at 8.4 in hPanel (server CLI default is 8.3; the app needs ^8.3, 8.4 matches CI).
Rollback: disconnect hPanel Git, then `rollback`.
