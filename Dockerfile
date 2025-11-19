FROM php:8.2-fpm

# Instalar dependencias del sistema y extensiones comunes
RUN apt-get update && apt-get install -y \
    git unzip zip libzip-dev libpng-dev libjpeg-dev libonig-dev libicu-dev \
    libxml2-dev curl libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql zip gd intl bcmath opcache

# Instalar composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar composer files e instalar dependencias
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Copiar el resto del código
COPY . .

# Crear storage link (intento seguro)
RUN php artisan storage:link || true

# Exponer puerto (php-fpm usa 9000)
EXPOSE 9000

# Start php-fpm
CMD ["php-fpm"]
