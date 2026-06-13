FROM php:8.2-apache

# =========================
# 1. Extensions
# =========================
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_pgsql zip

# =========================
# 2. Apache
# =========================
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# =========================
# 3. Code
# =========================
COPY . /var/www/html

# =========================
# 4. Composer
# =========================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# =========================
# 5. Permissions Laravel
# =========================
RUN mkdir -p storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# =========================
# 6. Expose
# =========================
EXPOSE 80

# =========================
# 7. BOOTSTRAP PRODUCTION (IMPORTANT)
# =========================
CMD bash -c "\
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache && \
    php artisan config:clear || true && \
    php artisan cache:clear || true && \
    php artisan route:clear || true && \
    php artisan migrate --force || true && \
    php artisan db:seed --force || true && \
    php artisan l5-swagger:generate || true && \
    apache2-foreground"
