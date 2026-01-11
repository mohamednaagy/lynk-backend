# syntax=docker/dockerfile:1.4
# Multi-stage Dockerfile for Laravel application
# Stage 1: Composer dependencies
FROM composer:2 AS composer-stage

WORKDIR /app

COPY composer.json ./

RUN composer install \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --optimize --no-scripts

# Stage 2: Runtime image
FROM php:8.4-fpm-alpine

# Accept build arguments for user ID and group ID
# For macOS: Use UID 501, but use GID 1000 to avoid Alpine's GID 20 conflict
ARG USER_ID=1000
ARG GROUP_ID=1000

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

# Configure PHP settings for production
RUN { \
        echo 'memory_limit = 512M'; \
        echo 'max_execution_time = 300'; \
        echo 'post_max_size = 100M'; \
        echo 'upload_max_filesize = 100M'; \
        echo 'max_input_vars = 5000'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Create non-root user www-data with host-matching uid (GID 1000 avoids Alpine conflicts)
RUN deluser www-data 2>/dev/null || true \
    && delgroup www-data 2>/dev/null || true \
    && addgroup -g ${GROUP_ID} www-data \
    && adduser -u ${USER_ID} -G www-data -s /bin/sh -D www-data

# Set working directory
WORKDIR /var/www/html

# Copy application files (includes .env created by GitHub Actions workflow)
COPY --chown=www-data:www-data . .

# Copy vendor from composer stage
COPY --from=composer-stage --chown=www-data:www-data /app/vendor ./vendor

# Verify .env file exists and show size
RUN set -ex; \
    \
    if [ -f .env ]; then \
        echo "✓ Using .env file from build context ($(wc -c < .env) bytes)"; \
        echo "First 3 lines:"; \
        head -3 .env; \
    else \
        echo "⚠️  No .env file found, using .env.example as template"; \
        cp .env.example .env; \
    fi; \
    \
    # Final verification
    test -f .env || (echo "❌ .env file not found" && exit 1); \
    \
    echo "✅ Environment configuration ready"

# Ensure storage and bootstrap/cache directories and permissions
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs/lynk \
    storage/app/public \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Configure PHP-FPM to listen on port 8000
RUN echo "listen = 8000" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Switch to www-data user
USER www-data

# Expose php-fpm port
EXPOSE 8000

# Start php-fpm
CMD ["php-fpm", "-F"]
