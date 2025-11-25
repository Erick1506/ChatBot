FROM php:8.0-apache

# 1. Instalar dependencias del sistema
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libxml2-dev

# 2. Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# 3. Instalar extensiones de PHP
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd

# 4. Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 5. Crear directorio de trabajo
WORKDIR /var/www/html

# 6. COPIAR TODOS LOS ARCHIVOS
COPY . .

# 7. INSTALAR DEPENDENCIAS SIN EJECUTAR SCRIPTS (evita que composer llame a artisan durante build)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

# 8. Generar autoload optimizado y ejecutar package discover ahora que el código está presente
RUN composer dump-autoload -o \
    && php -v >/dev/null || true

# 9. Configurar Apache (mantener que DocumentRoot sea public)
RUN echo '<VirtualHost *:80>' > /etc/apache2/sites-available/000-default.conf && \
    echo '    ServerAdmin webmaster@localhost' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    DocumentRoot /var/www/html/public' >> /etc/apache2/sites-available/000-default.conf && \
    echo '' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    <Directory /var/www/html/public>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        Options Indexes FollowSymLinks' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        AllowOverride All' >> /etc/apache2/sites-available/000-default.conf && \
    echo '        Require all granted' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    </Directory>' >> /etc/apache2/sites-available/000-default.conf && \
    echo '' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    ErrorLog ${APACHE_LOG_DIR}/error.log' >> /etc/apache2/sites-available/000-default.conf && \
    echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined' >> /etc/apache2/sites-available/000-default.conf && \
    echo '</VirtualHost>' >> /etc/apache2/sites-available/000-default.conf

# 10. Habilitar mod_rewrite
RUN a2enmod rewrite

# 11. Crear directorios de storage si no existen y ajustar permisos
RUN mkdir -p storage/framework/{sessions,views,cache} \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 12. (Opcional) No caches config/route/view en build - hacerlo en runtime si lo deseas.
# Eliminamos php artisan config:cache / route:cache / view:cache para que Render injecte env vars en runtime.

# 13. Exponer puerto
EXPOSE 80

# 14. CMD por defecto (apache)
CMD ["apache2-foreground"]
