FROM php:8.2-apache

# =========================
# 1. Extensions système
# =========================
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip

# =========================
# 2. Apache rewrite (Laravel)
# =========================
RUN a2enmod rewrite

# =========================
# 3. DocumentRoot vers /public
# =========================
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# =========================
# 4. Code source
# =========================
COPY . /var/www/html

# =========================
# 5. Composer
# =========================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# =========================
# 6. Permissions Laravel
# =========================
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# =========================
# 7. Port
# =========================
EXPOSE 80

# =========================
# 8. CMD (IMPORTANT FIX)
# =========================
CMD php artisan config:clear && \
    php artisan cache:clear && \
    php artisan route:clear && \
    php artisan l5-swagger:generate && \
    apache2-foreground
