# Usa una imagen oficial de PHP con Apache
FROM php:8.1-apache

# Configura variables de entorno para evitar interacciones en la instalación
ENV DEBIAN_FRONTEND=noninteractive

# Instala dependencias del sistema y extensiones PHP necesarias para Laravel
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    git \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Habilita el módulo mod_rewrite de Apache
RUN a2enmod rewrite

# Copia el archivo de configuración del Virtual Host
COPY docker/vhost.conf /etc/apache2/sites-available/000-default.conf

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Configura el directorio de trabajo
WORKDIR /var/www/html

# Copia los archivos de configuración primero (para aprovechar el caché de Docker)
COPY composer.json composer.lock ./

# Copia el resto del código
COPY . .

# Instala las dependencias de Composer para producción
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Ajusta permisos para Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expone el puerto 80
EXPOSE 80

# Script de entrada para Apache
CMD ["apache2-foreground"]
