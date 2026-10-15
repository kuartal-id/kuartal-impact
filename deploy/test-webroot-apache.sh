#!/usr/bin/env bash
# Serves a release tree with real Apache + the tree's own .htaccess files, and runs
# bin/check-webroot.sh against it. Needs root (CI runner / disposable box).
#
#   sudo deploy/test-webroot-apache.sh <release-tree>
#
# Every denied path is first created as a real file in a scratch copy, so a missing rule
# shows up as "file served". public/index.php is replaced by a stub that answers
# "APP-STUB" (no PHP needed): reaching it means the request went to the front controller.
set -euo pipefail
TREE="$(cd "$1" && pwd)"
SITE=/var/www/webroot-test
PORT=8089

if ! command -v apache2 >/dev/null; then
  export DEBIAN_FRONTEND=noninteractive
  apt-get update -qq && apt-get install -y -qq apache2 >/dev/null
fi

rm -rf "$SITE"; mkdir -p "$SITE"
cp -a "$TREE/." "$SITE/"
printf 'APP-STUB' > "$SITE/public/index.php"

# Make every denied path exist (files get a recognisable body).
grep -oE '^  /[^ ]+( /[^ ]+)*' "$TREE/bin/check-webroot.sh" | tr ' ' '\n' | grep '^/' | while read -r p; do
  case "$p" in *%*) continue;; esac
  if [[ "$p" == */ ]]; then mkdir -p "$SITE$p"; else mkdir -p "$(dirname "$SITE$p")"; [ -e "$SITE$p" ] || printf 'SECRET-%s' "$p" > "$SITE$p"; fi
done
chown -R www-data:www-data "$SITE" 2>/dev/null || true

cat > /etc/apache2/sites-available/webroot-test.conf <<CONF
Listen $PORT
<VirtualHost *:$PORT>
    DocumentRoot $SITE
    <Directory $SITE>
        AllowOverride All
        Require all granted
    </Directory>
    ErrorLog \${APACHE_LOG_DIR}/webroot-test-error.log
</VirtualHost>
CONF
a2enmod -q rewrite headers >/dev/null
a2ensite -q webroot-test >/dev/null
apache2ctl configtest
(apache2ctl restart || apache2ctl start)
sleep 1

STUB_MARKER=APP-STUB "$TREE/bin/check-webroot.sh" "http://127.0.0.1:$PORT"
