#!/usr/bin/env bash
set -euo pipefail
APP_ROOT="${APP_ROOT:-$HOME/public_html}"
PATCH_DIR="$(cd "$(dirname "$0")" && pwd)"
rsync -av --exclude='patch_apply.sh' --exclude='UPLOAD_TO_CPANEL.txt' "$PATCH_DIR/" "$APP_ROOT/"
cd "$APP_ROOT"
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
