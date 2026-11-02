#!/usr/bin/env bash
# Run on the laptop: tests, build, push, then (optionally) trigger the update on the server.
# Usage: ./deploy.sh            tests + build check + push + run deploy-server.sh over SSH
#        SKIP_TESTS=1 ./deploy.sh
#        NO_SERVER=1 ./deploy.sh   push only, then run `bash deploy-server.sh` on the server yourself
# public/build is committed on purpose: the server has no Node, it only pulls.
# Server settings live in .deploy.env (not committed); copy .deploy.env.example.
set -euo pipefail
cd "$(dirname "$0")"
step() { printf '\n\033[1;33m==> %s\033[0m\n' "$*"; }

[ "$(git rev-parse --abbrev-ref HEAD)" = "main" ] || { echo "Deploy hanya dari branch main." >&2; exit 1; }
[ -z "$(git status --porcelain --untracked-files=no)" ] || { echo "Ada perubahan yang belum di-commit. Commit dulu." >&2; exit 1; }

if [ "${SKIP_TESTS:-0}" != "1" ]; then
    step "Tes"
    php artisan test tests/Feature
fi

step "Build aset dan cek sudah ter-commit"
npm run build
if [ -n "$(git status --porcelain public/build)" ]; then
    echo "public/build berubah setelah build. Jalankan: git add public/build && git commit, lalu ulangi." >&2
    exit 1
fi

step "Push ke GitHub"
git push

if [ "${NO_SERVER:-0}" = "1" ]; then
    step "Selesai. Di server jalankan: bash ~/domains/.../public_html/dmk_app/deploy-server.sh"
    exit 0
fi

[ -f .deploy.env ] || { echo "Tanpa .deploy.env saya hanya push. Di server jalankan: bash deploy-server.sh" >&2; exit 0; }
# shellcheck disable=SC1091
source .deploy.env
: "${DEPLOY_SSH:?isi DEPLOY_SSH}" "${DEPLOY_PORT:?isi DEPLOY_PORT}" "${DEPLOY_APP:?isi DEPLOY_APP}"

step "Update server"
ssh -p "$DEPLOY_PORT" "$DEPLOY_SSH" "bash $DEPLOY_APP/deploy-server.sh"
step "Selesai. Cek situsnya."
