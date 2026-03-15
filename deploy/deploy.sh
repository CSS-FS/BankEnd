#!/usr/bin/env bash

set -Eeuo pipefail

APP_DIR="${APP_DIR:-/var/www/flocksense-backend}"
PHP_SERVICE="${PHP_SERVICE:-app}"

compose() {
    if docker compose version >/dev/null 2>&1; then
        docker compose "$@"
        return
    fi

    docker-compose "$@"
}

log() {
    printf '\n[%s] %s\n' "$(date '+%Y-%m-%d %H:%M:%S')" "$1"
}

require_file() {
    local file_path="$1"

    if [[ ! -f "$file_path" ]]; then
        printf 'Required file not found: %s\n' "$file_path" >&2
        exit 1
    fi
}

log "Switching to ${APP_DIR}"
cd "${APP_DIR}"

require_file ".env"
require_file "docker-compose.yml"
require_file "Dockerfile"

log "Ensuring the external Docker network exists"
docker network inspect appnet >/dev/null 2>&1 || docker network create appnet >/dev/null

log "Preparing writable Laravel directories"
mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache
chown -R 33:33 storage bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache

log "Clearing stale frontend dev-server markers"
rm -f public/hot

log "Building the PHP-FPM image"
compose build "${PHP_SERVICE}"

log "Installing Composer dependencies"
compose run --rm --user root "${PHP_SERVICE}" composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction

if [[ -f package.json ]]; then
    if [[ -f public/build/manifest.json ]]; then
        log "Using prebuilt frontend assets from deployment bundle"
    else
        log "Frontend assets missing; building on server"
        docker run --rm \
            -v "${APP_DIR}:/app" \
            -w /app \
            node:22-alpine \
            sh -lc "rm -f public/hot && npm ci && npm run build"
    fi
fi

log "Restarting application containers"
compose down --remove-orphans
compose up -d

log "Running database migrations"
compose exec -T "${PHP_SERVICE}" php artisan migrate --force

log "Linking storage and warming Laravel caches"
compose exec -T --user root "${PHP_SERVICE}" php artisan storage:link || true
compose exec -T "${PHP_SERVICE}" php artisan optimize:clear
compose exec -T "${PHP_SERVICE}" php artisan config:cache
compose exec -T "${PHP_SERVICE}" php artisan route:cache
compose exec -T "${PHP_SERVICE}" php artisan view:cache

log "Running nginx health check"
if ! compose exec -T nginx wget -q -O /dev/null http://127.0.0.1/nginx-health; then
    printf 'Nginx health check failed: /nginx-health did not return success.\n' >&2
    exit 1
fi

log "Running application health check"
if ! compose exec -T nginx wget -q -O /dev/null http://127.0.0.1/api/health; then
    printf 'Application health check failed: /api/health did not return success.\n' >&2
    exit 1
fi

log "Deployment completed successfully"
