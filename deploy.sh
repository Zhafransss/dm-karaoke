#!/usr/bin/env bash
# Deploy to the Hostinger server: build assets locally, push, sync public/, update the app over SSH.
# Usage: ./deploy.sh            (real deploy)
#        DRY_RUN=1 ./deploy.sh  (show what would happen; nothing is changed on the server)
#        SKIP_TESTS=1 ./deploy.sh
# Server settings live in .deploy.env (not committed); copy .deploy.env.example.
set -euo pipefail
cd "$(dirname "$0")"

[ -f .deploy.env ] || { echo "Buat .deploy.env dulu (salin dari .deploy.env.example)." >&2; exit 1; }
# shellcheck disable=SC1091
source .deploy.env
: "${DEPLOY_SSH:?isi DEPLOY_SSH}" "${DEPLOY_PORT:?isi DEPLOY_PORT}" "${DEPLOY_PUBLIC:?isi DEPLOY_PUBLIC}" "${DEPLOY_APP:?isi DEPLOY_APP}"

DRY=${DRY_RUN:-0}
SSH=(ssh -p "$DEPLOY_PORT" "$DEPLOY_SSH")
RSYNC_SSH="ssh -p $DEPLOY_PORT"
step() { printf '\n\033[1;33m==> %s\033[0m\n' "$*"; }

step "Cek git"
[ "$(git rev-parse --abbrev-ref HEAD)" = "main" ] || { echo "Deploy hanya dari branch main." >&2; exit 1; }
if [ -n "$(git status --porcelain --untracked-files=no)" ]; then
    echo "Ada perubahan yang belum di-commit. Commit dulu." >&2
    exit 1
fi

if [ "${SKIP_TESTS:-0}" != "1" ]; then
    step "Tes"
    php artisan test tests/Feature
fi

step "Build aset"
npm run build

step "Push ke GitHub"
if [ "$DRY" = "1" ]; then
    git push --dry-run
else
    git push
fi

step "Kirim public/ (index.php, .htaccess, uploads tidak disentuh)"
RSYNC_OPTS=(-rltz --itemize-changes -e "$RSYNC_SSH")
[ "$DRY" = "1" ] && RSYNC_OPTS+=(--dry-run)
# build/ is fully replaced so stale hashed files disappear; the rest is only added/updated.
rsync "${RSYNC_OPTS[@]}" --delete public/build/ "$DEPLOY_SSH:$DEPLOY_PUBLIC/build/"
rsync "${RSYNC_OPTS[@]}" --exclude=build/ --exclude=uploads/ --exclude=index.php --exclude=.htaccess \
    --exclude=.gitignore --exclude=hot --exclude=storage public/ "$DEPLOY_SSH:$DEPLOY_PUBLIC/"

if [ "$DRY" = "1" ]; then
    step "Dry run selesai (server tidak diubah)."
    exit 0
fi

step "Update aplikasi di server"
"${SSH[@]}" bash -s -- "$DEPLOY_APP" <<'REMOTE'
set -euo pipefail
cd "$1"
git fetch origin
if git diff --name-only HEAD origin/main | grep -qE '^composer\.(json|lock)$'; then
    echo "composer.json/lock berubah: install dependensi"
    composer install --no-dev -o --no-interaction
fi
git reset --hard origin/main
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
echo
git log --oneline -1
REMOTE

step "Selesai. Cek situsnya."
