#!/usr/bin/env bash
# kuartalimpact.org -> Laravel via Hostinger Git deploy: cutover helper (prepared 2026-10-14).
# Copy lives at ~/backups/kuartalimpact-cutover.sh on the server.
# Usage:  source ~/backups/kuartalimpact-cutover.sh
#         prepare             # non-destructive: ~/kuartalimpact-data/{.env,storage,private,database.sqlite}
#         backup_and_empty    # tar.gz of public_html (+ DB dump if WordPress is still there), then empty it
#         rollback [tarball]  # restore the latest (or given) public_html backup
#         post_deploy         # php artisan app:post-deploy (crons are broken account-wide; run by hand)
#         verify              # folder, data dir, live checks
# Nothing runs on `source`. Run backup_and_empty right before pressing "Deploy" in hPanel > Git.
# The owner plans to delete the old WordPress site first; backup_and_empty copes with an empty folder.
KI_HOST="kuartalimpact.org"
KI_DIR="$HOME/domains/$KI_HOST/public_html"
KI_DATA="$HOME/kuartalimpact-data"
KI_BACKUPS="$HOME/backups"
PHP=/opt/alt/php84/usr/bin/php

prepare() {
  umask 077
  mkdir -p "$KI_DATA"/private "$KI_DATA"/storage/{app/public,logs,framework/{cache/data,sessions,views}}
  chmod 700 "$KI_DATA" "$KI_DATA/private"
  [ -f "$KI_DATA/database.sqlite" ] || { touch "$KI_DATA/database.sqlite"; chmod 600 "$KI_DATA/database.sqlite"; }
  if [ ! -f "$KI_DATA/.env" ]; then
    cat > "$KI_DATA/.env" <<ENV
APP_NAME="Kuartal Impact"
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://$KI_HOST
LOG_CHANNEL=stack
LOG_LEVEL=warning
DB_CONNECTION=sqlite
DB_DATABASE=$KI_DATA/database.sqlite
FILESYSTEM_PRIVATE_ROOT=$KI_DATA/private
SESSION_DRIVER=file
SESSION_SECURE_COOKIE=true
CACHE_STORE=file
QUEUE_CONNECTION=sync
MAIL_MAILER=log
KUARTAL_ID_BASE_URL=https://id.kuartal.id
KUARTAL_ID_CLIENT_ID=
KUARTAL_ID_CLIENT_SECRET=
KUARTAL_ID_REDIRECT_URI=https://$KI_HOST/auth/kuartal-id/callback
KUARTAL_ID_SCOPES="openid profile email entitlements"
KUARTAL_ID_ADMIN_ENTITLEMENT=kuartalimpact.admin
ENV
    # APP_KEY generated locally, never echoed.
    key="base64:$(head -c 32 /dev/urandom | base64)"
    sed -i "s|^APP_KEY=$|APP_KEY=$key|" "$KI_DATA/.env"; unset key
    echo "created $KI_DATA/.env (fill KUARTAL_ID_CLIENT_ID / _SECRET by hand; never paste them in chat)"
  else
    echo "$KI_DATA/.env already exists (kept)"
  fi
  chmod 600 "$KI_DATA/.env"
  grep -q '^KUARTAL_ID_CLIENT_ID=.\+' "$KI_DATA/.env" || echo "WARN: KUARTAL_ID_CLIENT_ID is empty (admin sign-in will not work yet; public site is fine)"
  echo "prepare: ok"
}

backup_and_empty() {
  local ts tarball
  [ -d "$KI_DIR" ] || { echo "no $KI_DIR" >&2; return 1; }
  prepare || return 1
  ts=$(date -u +%Y%m%d-%H%M)
  mkdir -p "$KI_BACKUPS" && chmod 700 "$KI_BACKUPS"
  tarball="$KI_BACKUPS/$KI_HOST-public_html-$ts.tar.gz"
  tar -czf "$tarball" -C "$KI_DIR" . || { echo "backup FAILED, folder untouched" >&2; return 1; }
  chmod 600 "$tarball"
  tar -tzf "$tarball" >/dev/null || { echo "backup unreadable, folder untouched" >&2; return 1; }
  if [ -f "$KI_DIR/wp-config.php" ]; then
    # WordPress still present: dump its DB too (credentials read from wp-config.php, never printed).
    ( cd "$KI_DIR" && eval "$($PHP -r '$s=file_get_contents("wp-config.php");foreach(["DB_NAME","DB_USER","DB_PASSWORD","DB_HOST"] as $k){preg_match("/define\\(\\s*.$k.\\s*,\\s*.([^\x27\x22]*)./",$s,$m);echo "$k=".escapeshellarg($m[1]??"")."\n";}')" \
      && MYSQL_PWD="$DB_PASSWORD" mysqldump --single-transaction --no-tablespaces -h "$DB_HOST" -u "$DB_USER" "$DB_NAME" | gzip > "$KI_BACKUPS/$KI_HOST-wordpress-$ts.sql.gz" ) \
      && chmod 600 "$KI_BACKUPS/$KI_HOST-wordpress-$ts.sql.gz" || { echo "DB dump FAILED, folder untouched" >&2; return 1; }
  fi
  find "$KI_DIR" -mindepth 1 -delete
  echo "$tarball" > "$KI_BACKUPS/$KI_HOST-public_html-LATEST"
  echo "backed up to $tarball and emptied $KI_DIR. Now press Deploy in hPanel > Git, then run: post_deploy"
}

rollback() {
  local tarball="${1:-$(cat "$KI_BACKUPS/$KI_HOST-public_html-LATEST" 2>/dev/null)}"
  [ -f "$tarball" ] || { echo "no backup tarball ($tarball)" >&2; return 1; }
  echo "Disconnect hPanel Git for $KI_HOST FIRST, or its next deploy overwrites this."
  find "$KI_DIR" -mindepth 1 -delete
  tar -xzf "$tarball" -C "$KI_DIR"
  echo "restored $KI_DIR from $tarball (WordPress DB was never touched)"
}

post_deploy() {
  [ -f "$KI_DIR/artisan" ] || { echo "no Laravel release in $KI_DIR yet" >&2; return 1; }
  (cd "$KI_DIR" && $PHP artisan app:post-deploy)
}

verify() {
  echo "== $KI_HOST ($KI_DIR)"
  ls -A "$KI_DIR" | head -20 | tr '\n' ' '; echo
  [ -f "$KI_DIR/bootstrap/deploy-build.json" ] && echo "release: $(cat "$KI_DIR/bootstrap/deploy-build.json")"
  [ -f "$KI_DIR/.env" ] && echo "WARN: $KI_DIR/.env exists (should live in $KI_DATA)"
  [ -f "$KI_DIR/artisan" ] && (cd "$KI_DIR" && $PHP artisan about --only=environment 2>/dev/null | head -8)
  for p in / /up /programmes /programmes/mangrow-fund /newsroom /deploy-release.txt /.env /storage/logs/laravel.log /database/database.sqlite /vendor/autoload.php; do
    printf '  %s %s\n' "$(curl -s -o /dev/null -w '%{http_code}' "https://$KI_HOST$p?v=$RANDOM")" "$p"
  done
  echo "== data: $(ls -la "$KI_DATA" 2>/dev/null | awk 'NR>1{print $1, $NF}' | tr '\n' ';')"
}
