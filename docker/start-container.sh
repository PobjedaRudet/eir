#!/usr/bin/env sh
set -eu

cd /var/www/html

mkdir -p \
    /data \
    bootstrap/cache \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    touch "${DB_DATABASE:-/data/database.sqlite}"
    chown www-data:www-data "${DB_DATABASE:-/data/database.sqlite}"
fi

chown -R www-data:www-data /data bootstrap/cache storage

if [ ! -L public/storage ] && [ ! -e public/storage ]; then
    php artisan storage:link >/dev/null 2>&1 || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    php artisan db:seed --force
fi

exec apache2-foreground
