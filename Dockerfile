FROM php:8.0-apache

# Instalar dependencias del sistema
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

# Limpiar cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath gd

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copiar el contenido de la aplicación
COPY . /var/www/html

# Establecer el directorio de trabajo
WORKDIR /var/www/html

# Copiar configuración personalizada de Apache
COPY .docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Habilitar mod_rewrite de Apache
RUN a2enmod rewrite

# Cambiar el propietario del directorio
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80
EXPOSE 80

CMD ["apache2-foreground"]