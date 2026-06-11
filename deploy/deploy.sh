#!/usr/bin/env bash
# Build + release on the server. Run from /var/www/gatto.
# Assumes layout:  /var/www/gatto/api  (Laravel)   /var/www/gatto/src-dashboard (Angular source)
set -euo pipefail

ROOT=/var/www/gatto

echo "== API (Laravel) =="
cd "$ROOT/api"
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan erp:sync                 # seed the demo ledger
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "== Dashboard (Angular) =="
cd "$ROOT/src-dashboard"
npm ci
npm run build                        # outputs to dist/<project>/browser
# publish the build to the nginx root
rm -rf "$ROOT/dashboard"
cp -r dist/*/browser "$ROOT/dashboard"

echo "== Reload =="
sudo nginx -t && sudo systemctl reload nginx
echo "Done. https://gatto-piccolo.com"
