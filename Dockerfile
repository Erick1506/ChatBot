# Dockerfile optimizado para Laravel (PHP 8.2 FPM)
FROM php:8.2-fpm

# Instalar dependencias del sistema y extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libpng-dev libjpeg-dev libonig-dev libicu-dev \
    libxml2-dev curl libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    libpq-dev build-essential \
 && docker-php-ext-configure gd --with-freetype --with-jpeg \
 && docker-php-ext-install pdo_mysql pdo_pgsql zip gd intl bcmath opcache pcntl

# Instalar composer (desde la imagen oficial de composer)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Evitar problemas con permisos en composer (opcional pero útil)
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/composer

WORKDIR /var/www/html

# Copiar solo archivos de composer para aprovechar cache de Docker
COPY composer.json composer.lock ./

# Instalar dependencias PHP sin ejecutar scripts (evita ejecutar artisan antes de tiempo)
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts

# Copiar todo el proyecto
COPY . .

# Ahora que el código está copiado, ejecutar los scripts necesarios:
# 1) generar se KEY si no existe (no rompe si ya existe)
# 2) ejecutar scripts de composer (incluye package:discover)
# 3) crear storage link (intento seguro)
RUN php artisan key:generate --ansi || true \
 && composer run-script post-install-cmd --no-interaction || true \
 && composer run-script post-autoload-dump --no-interaction || true \
 && php artisan storage:link || true

# Ajustes finales
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache || true

EXPOSE 9000
CMD ["php-fpm"]
