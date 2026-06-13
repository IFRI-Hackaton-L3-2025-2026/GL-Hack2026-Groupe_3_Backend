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
# 2. Apache config
# =========================
RUN a2enmod rewrite

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/conf-available/*.conf

# =========================
# 3. Copier projet
# =========================
COPY . /var/www/html

# =========================
# 4. Composer install
# =========================
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# =========================
# 5. Permissions Laravel (IMPORTANT)
# =========================
RUN mkdir -p storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# =========================
# 6. Expose port
# =========================
EXPOSE 80

# =========================
# 7. Start script (Render safe)
# =========================
CMD bash -c "\
    chown -R www-data:www-data storage bootstrap/cache && \
    chmod -R 775 storage bootstrap/cache && \
    php artisan config:clear || true && \
    php artisan cache:clear || true && \
    php artisan route:clear || true && \
    php artisan migrate --force || true && \
    php artisan l5-swagger:generate || true && \
    apache2-foreground"
