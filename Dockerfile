FROM php:8.2-apache

# 1. Installer les extensions nécessaires pour Laravel et PostgreSQL
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip

# 2. Activer le module Apache Rewrite pour Laravel
RUN a2enmod rewrite

# 3. Configurer le DocumentRoot d'Apache vers le dossier /public de Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# 4. Copier le code du projet
COPY . /var/www/html

# 5. Installer Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# 6. Donner les permissions pour le stockage Laravel
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 7. Exposer le port 80
EXPOSE 80

RUN php artisan l5-swagger:generate

# 8. Script de démarrage pour les migrations
CMD php artisan config:clear && php artisan migrate --force && apache2-foreground
