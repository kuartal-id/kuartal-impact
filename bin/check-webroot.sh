#!/usr/bin/env bash
# Checks that a kuartalimpact deployment only serves public/ and refuses everything else.
#
#   bin/check-webroot.sh https://kuartalimpact.org         # live, after every cutover/deploy change
#   bin/check-webroot.sh http://127.0.0.1:8080           # CI: real Apache with the repo's .htaccess
#
# Denied paths must answer 403 or 404 (never 200/3xx). Public paths must answer 200.
# Exit code 1 on any mismatch. Prints only paths and status codes, never response bodies.
set -u
BASE="${1:?usage: bin/check-webroot.sh <base-url>}"
BASE="${BASE%/}"
fail=0

denied=(
  /.env /.env.save /.env.backup /.env.example /.env.production
  /.git/config /.git/HEAD /.git/ /.github/workflows/deploy.yml /.ai/check.py /.gitignore /.htaccess
  /composer.json /composer.lock /package.json /package-lock.json /artisan /phpunit.xml /vite.config.js
  /README.md /AGENTS.md /CLAUDE.md /docs/HANDOFF.md /docs/DEPLOY.md
  /app/Models/User.php /app/ /bootstrap/app.php /bootstrap/deploy-build.json /bootstrap/cache/config.php
  /config/app.php /config/ /database/database.sqlite /database/migrations/ /database/
  /routes/web.php /resources/views/ /tests/TestCase.php /bin/check-webroot.sh
  /storage/logs/laravel.log /storage/app/private/ /storage/app/deployed_sha /storage/framework/sessions/ /storage/
  /vendor/autoload.php /vendor/composer/installed.json /vendor/
  /public/.htaccess /laravel.log /error_log /backup.sql /db.sqlite /x.env /public/x.log
  /APP/Models/User.php /Vendor/autoload.php /%2e%65nv /.%65nv
)
public=(/ /up /favicon.ico /jobs)

# STUB_MARKER (CI only): a 200 whose body is exactly the marker means the request reached
# the front-controller stub, i.e. no file was served. Live, the app answers 404 instead.
code() {
  local body c
  body=$(mktemp)
  c=$(curl -sk -o "$body" -w '%{http_code}' --max-time 20 "$BASE$1")
  if [[ "${2:-}" == denied && -n "${STUB_MARKER:-}" && "$c" == "200" ]] && grep -qx -- "$STUB_MARKER" "$body"; then c="app"; fi
  rm -f "$body"; echo "$c"
}

for p in "${denied[@]}"; do
  c=$(code "$p" denied)
  if [[ "$c" == "403" || "$c" == "404" || "$c" == "400" || "$c" == "app" ]]; then
    printf 'ok    %s %s\n' "$c" "$p"
  else
    printf 'FAIL  %s %s  (must be 403/404)\n' "$c" "$p"; fail=1
  fi
done

for p in "${public[@]}"; do
  c=$(code "$p")
  if [[ "$c" == "200" ]]; then printf 'ok    %s %s\n' "$c" "$p"; else printf 'FAIL  %s %s  (must be 200)\n' "$c" "$p"; fail=1; fi
done

# One built asset from the Vite manifest, if there is one.
asset=$(curl -sk --max-time 20 "$BASE/build/manifest.json" | python3 -c 'import sys,json
try:
    m=json.load(sys.stdin); print("/build/"+next(iter(m.values()))["file"])
except Exception: pass' 2>/dev/null)
if [[ -n "${asset:-}" ]]; then
  c=$(code "$asset"); [[ "$c" == "200" ]] && printf 'ok    %s %s\n' "$c" "$asset" || { printf 'FAIL  %s %s  (must be 200)\n' "$c" "$asset"; fail=1; }
fi

[[ $fail == 0 ]] && echo "web root OK" || echo "web root check FAILED"
exit $fail
