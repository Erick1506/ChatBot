#!/bin/bash
set -e

# Si existe .env.example y no .env, copia el ejemplo (opcional)
if [ ! -f /var/www/html/.env ] && [ -f /var/www/html/.env.example ]; then
  cp /var/www/html/.env.example /var/www/html/.env
fi

# Ejecutar migrations o cache solo en runtime y si APP_ENV != local
php artisan migrate --force || true
php artisan config:cache || true
php artisan route:cache || true
php artisan view:cache || true

# Lanzar Apache
exec apache2-foreground
