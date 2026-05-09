#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# make_patch.sh  –  Baidoos POS incremental patch builder
#
# Usage:
#   ./make_patch.sh              # auto-detects last deploy tag
#   ./make_patch.sh v1.0.0       # compare from a specific tag / commit
#
# Output:
#   ../patches/patch_YYYYMMDD_HHMMSS.zip   (relative to pos/ directory)
#
# What it does:
#   1. Finds all files changed since the last "deploy/*" tag (or given ref)
#   2. Excludes vendor/, node_modules/, .env, storage/logs, etc.
#   3. Bundles them into a zip preserving directory structure
#   4. Appends a patch_apply.sh and a migrations.list inside the zip
#
# On cPanel:
#   1. Upload the zip to your public_html (or wherever the app lives)
#   2. SSH / Terminal: bash patch_apply.sh
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
cd "$SCRIPT_DIR"

# ── 1. Resolve base ref ───────────────────────────────────────────────────────
BASE_REF="${1:-}"
if [ -z "$BASE_REF" ]; then
    # Use the most recent deploy/* tag if one exists
    BASE_REF=$(git tag --list 'deploy/*' --sort=-version:refname | head -n 1 || true)
fi

if [ -z "$BASE_REF" ]; then
    echo "No deploy/* tag found. Using the very first commit as base."
    BASE_REF=$(git rev-list --max-parents=0 HEAD)
fi

echo "▶ Base ref  : $BASE_REF"
echo "▶ HEAD      : $(git rev-parse --short HEAD) ($(git log -1 --format='%s'))"

# ── 2. Get changed files ──────────────────────────────────────────────────────
CHANGED=$(git diff --name-only "$BASE_REF"...HEAD -- \
    ':!vendor/' \
    ':!node_modules/' \
    ':!.env' \
    ':!.env.*' \
    ':!storage/logs/' \
    ':!storage/framework/cache/' \
    ':!storage/framework/sessions/' \
    ':!storage/framework/views/' \
    ':!public/hot' \
    ':!*.log' \
    ':!make_patch.sh' \
)

if [ -z "$CHANGED" ]; then
    echo "✔ No changes since $BASE_REF. Nothing to patch."
    exit 0
fi

echo ""
echo "Changed files:"
echo "$CHANGED" | sed 's/^/  /'

# ── 3. Identify new migrations ────────────────────────────────────────────────
NEW_MIGRATIONS=$(echo "$CHANGED" | grep '^database/migrations/' || true)

# ── 4. Create output dir & zip ───────────────────────────────────────────────
PATCHES_DIR="$SCRIPT_DIR/../patches"
mkdir -p "$PATCHES_DIR"

TIMESTAMP=$(date '+%Y%m%d_%H%M%S')
HEAD_SHORT=$(git rev-parse --short HEAD)
PATCH_NAME="patch_${TIMESTAMP}_${HEAD_SHORT}"
WORK_DIR=$(mktemp -d)
PATCH_DIR="$WORK_DIR/$PATCH_NAME"
mkdir -p "$PATCH_DIR"

# Copy changed files preserving structure
while IFS= read -r f; do
    if [ -f "$f" ]; then
        TARGET_DIR="$PATCH_DIR/$(dirname "$f")"
        mkdir -p "$TARGET_DIR"
        cp "$f" "$TARGET_DIR/"
    fi
done <<< "$CHANGED"

# ── 5. Write migrations list ──────────────────────────────────────────────────
cat > "$PATCH_DIR/migrations.list" <<EOF
# New migrations in this patch (run: php artisan migrate)
$(echo "$NEW_MIGRATIONS")
EOF

# ── 6. Write patch_apply.sh ───────────────────────────────────────────────────
cat > "$PATCH_DIR/patch_apply.sh" <<'APPLY'
#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────────────────
# patch_apply.sh  –  Run this on the cPanel server after unzipping the patch
#
# Assumes you are inside the unzipped patch directory when you run it,
# or that APP_ROOT is set to your Laravel app root.
# ─────────────────────────────────────────────────────────────────────────────
set -euo pipefail

# ── Adjust this to your cPanel app path ──────────────────────────────────────
APP_ROOT="${APP_ROOT:-$HOME/public_html}"
# ─────────────────────────────────────────────────────────────────────────────

PATCH_DIR="$(cd "$(dirname "$0")" && pwd)"
echo "Applying patch from : $PATCH_DIR"
echo "App root            : $APP_ROOT"

# Copy all patched files (exclude control files)
rsync -av \
    --exclude='patch_apply.sh' \
    --exclude='migrations.list' \
    "$PATCH_DIR/" "$APP_ROOT/"

echo ""
echo "Running migrations..."
cd "$APP_ROOT"
php artisan migrate --force

echo ""
echo "Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

echo ""
echo "✔ Patch applied successfully."
APPLY

chmod +x "$PATCH_DIR/patch_apply.sh"

# ── 7. Zip it up ─────────────────────────────────────────────────────────────
OUTFILE="$PATCHES_DIR/${PATCH_NAME}.zip"
(cd "$WORK_DIR" && zip -r "$OUTFILE" "$PATCH_NAME/")
rm -rf "$WORK_DIR"

# ── 8. Tag this deploy ────────────────────────────────────────────────────────
TAG_NAME="deploy/$TIMESTAMP"
git tag "$TAG_NAME"
echo ""
echo "✔ Patch created  : $OUTFILE"
echo "✔ Deploy tag set : $TAG_NAME"
echo ""
echo "Files in patch:"
unzip -l "$OUTFILE" | awk 'NR>3 && $0 !~ /^--------/ && $0 !~ /files$|file$/ {print "  " $NF}'
echo ""
echo "Next steps:"
echo "  1. Upload $OUTFILE to your cPanel server"
echo "  2. SSH in, unzip, cd into the folder, run: bash patch_apply.sh"
echo "     (or set APP_ROOT=/path/to/your/app before running)"
