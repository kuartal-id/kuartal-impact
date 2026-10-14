#!/usr/bin/env bash
# Builds the tree that goes on the `deploy` branch (Hostinger Git deploy), from a clean
# checkout of main. Used by .github/workflows/build-deploy-branch.yml; runnable locally.
#
#   deploy/build-release.sh <source-checkout> <output-dir> <source-sha> <run-id>
#
# Output = the repository + public/build (Vite) + vendor/ (composer --no-dev, optimized),
# with require-dev stripped from composer.json/composer.lock so the `composer install`
# Hostinger runs on every deploy finds nothing to do. Fails on any surprise.
set -euo pipefail
SRC="$(cd "$1" && pwd)"; OUT="$2"; SHA="$3"; RUN="$4"

rm -rf "$OUT"; mkdir -p "$OUT"
# Tracked files only: never anything local (.env, node_modules, storage contents...).
git -C "$SRC" archive --format=tar HEAD | tar -x -C "$OUT"
cd "$OUT"

echo "::group::Frontend (Vite)"
npm ci --no-audit --no-fund
npm run build
test -f public/build/manifest.json
rm -rf node_modules
echo "::endgroup::"

echo "::group::Composer (production only)"
jq -r '[.packages[]|.name+"@"+.version]|sort|.[]' composer.lock > /tmp/prod-before.txt
dev=$(jq -r '(."require-dev" // {}) | keys | join(" ")' composer.json)
if [ -n "$dev" ]; then
  # Removes the dev packages from composer.json AND composer.lock; production packages keep
  # their exact locked versions (checked right below).
  composer remove --dev $dev --no-install --no-scripts --no-interaction --no-audit
fi
jq -r '[.packages[]|.name+"@"+.version]|sort|.[]' composer.lock > /tmp/prod-after.txt
diff -u /tmp/prod-before.txt /tmp/prod-after.txt || { echo "::error::Production package versions changed while stripping dev packages"; exit 1; }
test "$(jq '."packages-dev"|length' composer.lock)" = 0
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts --no-progress
# This is exactly what Hostinger runs on every deploy. It must be a no-op.
out=$(composer install --prefer-dist --no-interaction --dry-run 2>&1)
echo "$out"
grep -q "Nothing to install, update or remove" <<<"$out" || { echo "::error::Hostinger's composer install would change vendor/"; exit 1; }
php artisan --version
php artisan list app | grep -q "app:post-deploy"
rm -f bootstrap/cache/*.php
echo "::endgroup::"

# The deploy branch commits vendor/ and public/build; keep ignoring runtime data.
sed -i -e '\#^/vendor$#d' -e '\#^/public/build$#d' .gitignore
grep -q '^/database/\*\.sqlite' .gitignore || printf '/database/*.sqlite*\n' >> .gitignore

# Not needed on the server. Removing .github also means no workflow ever runs for `deploy`.
rm -rf .github tests phpunit.xml

printf '{"source_sha":"%s","run_id":"%s","built_at":"%s"}\n' "$SHA" "$RUN" "$(date -u +%Y-%m-%dT%H:%M:%SZ)" > bootstrap/deploy-build.json
# Public release marker: the workflow polls it to confirm Hostinger published this release
# (and re-pushes if not). Run id + source sha only; nothing secret.
printf 'run_id=%s\nsource_sha=%s\n' "$RUN" "$SHA" > public/deploy-release.txt

# Paranoia: nothing secret or runtime may be in the tree.
bad=$(find . -path ./vendor -prune -o \( -name '.env' -o -name '.env.*' ! -name '.env.example' -o -name '*.sqlite' -o -name '*.key' -o -name '*.log' \) -print)
[ -z "$bad" ] || { echo "::error::Refusing to publish: $bad"; exit 1; }
echo "release tree ready: $(du -sh . | cut -f1)"
