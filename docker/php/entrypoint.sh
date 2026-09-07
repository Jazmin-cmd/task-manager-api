#!/bin/sh
set -e

cd /var/www/html

if [ ! -f .env ]; then
    echo "[entrypoint] no se encontró .env, copiando .env.example"
    cp .env.example .env
fi

if [ ! -d vendor ]; then
    echo "[entrypoint] instalando dependencias de composer"
    composer install --no-interaction --prefer-dist --no-progress
fi

if ! grep -q "^APP_KEY=base64:" .env; then
    echo "[entrypoint] generando la clave de la aplicación"
    php artisan key:generate --force
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

exec "$@"
