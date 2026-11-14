#!/usr/bin/env bash
# Run ON THE SERVER, from dmk_app (Laravel root):  bash deploy-server.sh
# Pulls main, installs dependencies only when composer files changed, syncs public/ into
# public_html (never index.php, .htaccess or uploads), migrates and re-caches.
# DRY_RUN=1 prints the artisan/composer steps instead of running them.
# Layouts: dmk_app inside public_html (Hostinger), or dmk_app next to public_html (cPanel, ~/dmk_app).
# Optional overrides, also read from ~/.dmk-deploy.env: DMK_PUBLIC (web root), PHP_BIN, COMPOSER.
set -euo pipefail

main() {
    local app public old new src name
    [ -f "$HOME/.dmk-deploy.env" ] && . "$HOME/.dmk-deploy.env"
    local php="${PHP_BIN:-php}" composer="${COMPOSER:-composer}"
    app="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
    if [ -n "${DMK_PUBLIC:-}" ]; then
        public="$DMK_PUBLIC"
    elif [ -f "$app/../index.php" ]; then
        public="$(cd "$app/.." && pwd)"
    else
        public="$HOME/public_html"
    fi
    run() { if [ "${DRY_RUN:-0}" = "1" ]; then echo "[dry-run] $*"; else "$@"; fi; }

    # Safety: the web root's index.php must point to dmk_app.
    grep -q 'dmk_app' "$public/index.php" 2>/dev/null || { echo "$public/index.php tidak menunjuk ke dmk_app. Batal." >&2; exit 1; }

    cd "$app"
    git fetch origin
    old=$(git rev-parse HEAD)
    new=$(git rev-parse origin/main)
    echo "Server: ${old:0:7} -> ${new:0:7}"

    if git diff --name-only "$old" "$new" | grep -qE '^composer\.(json|lock)$'; then
        echo "composer.json/lock berubah: install dependensi"
        # --no-scripts: composer scripts need proc_open, which shared hosting disables.
        run $composer install --no-dev -o --no-interaction --no-scripts
    fi

    git reset --hard origin/main

    # public/ -> public_html. build/ is replaced so old hashed files disappear.
    for src in public/* public/.[!.]*; do
        [ -e "$src" ] || continue
        name=${src#public/}
        case "$name" in index.php|.htaccess|.gitignore|uploads|hot|storage) continue ;; esac
        [ "$name" = "build" ] && run rm -rf "$public/build"
        run cp -a "$src" "$public/"
        echo "public: $name"
    done

    run "$php" artisan package:discover --ansi
    run "$php" artisan migrate --force
    run "$php" artisan config:cache
    run "$php" artisan route:cache
    run "$php" artisan view:cache
    echo
    git log --oneline -1
}

# Everything runs inside main() so bash has read the whole file before `git reset` can rewrite it.
main "$@"
exit
