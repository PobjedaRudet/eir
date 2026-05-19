#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data bootstrap/cache storage

if [ ! -L public/storage ] && [ ! -e public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec apache2-foreground
