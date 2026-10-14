#!/bin/sh
# Menyalakan aplikasi: siapkan kunci dan database bila belum ada, lalu jalankan server.
set -e
cd /app

if [ -z "$APP_KEY" ]; then
    APP_KEY="$(php artisan key:generate --show)"
    export APP_KEY
fi

if [ ! -f "$DB_DATABASE" ]; then
    touch "$DB_DATABASE"
    php artisan migrate:fresh --seed --force
fi

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
