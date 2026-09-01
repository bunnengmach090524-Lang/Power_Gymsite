# ---- Dockerfile for GymSite (Laravel 13 + Vue/Inertia) ----
# Place this file at the ROOT of your project (same level as artisan, composer.json)
FROM php:8.3-apache

# 1. System dependencies + PHP extensions Laravel/GymSite needs
RUN apt-get update && apt-get install -y \
    git curl zip unzip \
    libpng-dev libonig-dev libxml2-dev libzip-dev \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 2. Enable Apache rewrite (needed for Laravel's pretty URLs)
RUN a2enmod rewrite

# 3. Point Apache's document root at Laravel's /public folder
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

WORKDIR /var/www/html

# 4. Composer (for PHP deps) and Node (for Vite/Vue build)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs

# 5. Copy project files in
COPY . .

# 6. Install PHP deps (no dev tools, optimized autoloader)
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 7. Install JS deps and build Vue/Inertia assets
RUN npm install && npm run build

# 8. Laravel needs write access to these two folders
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# 9. Entrypoint: runs migrations/caching/storage-link, then starts Apache
COPY docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

EXPOSE 80
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
