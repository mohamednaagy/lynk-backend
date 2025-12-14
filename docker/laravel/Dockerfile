# Multi-stage Dockerfile for Laravel application
# Stage 1: Composer dependencies
FROM composer:2 AS composer-stage

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-scripts

# Stage 2: Runtime image
FROM php:8.4-fpm-alpine

# Install system dependencies and PHP extensions
RUN apk add --no-cache \
    git \
    unzip \
    bash \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        zip \
        gd \
        opcache

# Install Redis extension via PECL
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps


# Create non-root user www-data with uid/gid 1000
RUN deluser www-data 2>/dev/null || true \
    && delgroup www-data 2>/dev/null || true \
    && addgroup -g 1000 www-data \
    && adduser -u 1000 -G www-data -s /bin/sh -D www-data

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY --chown=www-data:www-data . .

# Copy vendor from composer stage
COPY --from=composer-stage --chown=www-data:www-data /app/vendor ./vendor

# Ensure storage and bootstrap/cache permissions
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Remove any stale Laravel caches and refresh package discovery
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && php artisan package:discover --ansi || true \
    && php artisan config:clear || true \
    && php artisan event:clear || true \
    && php artisan cache:clear || true

# Switch to www-data user
USER www-data

# Expose php-fpm port
EXPOSE 9000

# Start php-fpm
CMD ["php-fpm", "-F"]
