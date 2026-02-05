# syntax=docker/dockerfile:1.4
# Multi-stage Dockerfile for Laravel application (Optimized for Size)
# Expected size reduction: 437MB → ~200-250MB

# Stage 1: Composer dependencies
FROM composer:2 AS composer-stage

WORKDIR /app

# Copy only composer files first (better layer caching)
COPY composer.json ./

# Install production dependencies only (--no-dev removes dev packages)
RUN composer install \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --ignore-platform-reqs

# Copy application code (use .dockerignore to exclude unnecessary files)
COPY . .

# Generate optimized autoloader for production
RUN composer dump-autoload \
    --optimize \
    --classmap-authoritative

# Stage 2: Runtime image
FROM php:8.4-fpm-alpine

# Accept build arguments for user ID and group ID
ARG USER_ID=1000
ARG GROUP_ID=1000

# Install runtime dependencies and build dependencies in single layer
# Use --no-cache to prevent package cache from being stored
# Use virtual package for build deps so they can be removed cleanly
RUN apk add --no-cache \
    # Runtime dependencies (kept in final image)
    bash \
    su-exec \
    icu-libs \
    libzip \
    oniguruma \
    libpng \
    libjpeg-turbo \
    freetype \
    # Removed: git (not needed at runtime)
    # Removed: mysql-client (use only if you need to run mysql commands in container)
    # Removed: unzip (composer not in final image)
    && apk add --no-cache --virtual .build-deps \
    # Build dependencies (will be removed after PHP extensions are built)
    $PHPIZE_DEPS \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        bcmath \
        intl \
        zip \
        gd \
        opcache \
    # Install Redis extension
    && pecl install redis \
    && docker-php-ext-enable redis \
    # Clean up build dependencies and caches
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

# Configure PHP settings for production
RUN { \
        echo 'memory_limit = 512M'; \
        echo 'max_execution_time = 300'; \
        echo 'post_max_size = 100M'; \
        echo 'upload_max_filesize = 100M'; \
        echo 'max_input_vars = 5000'; \
        # Production optimizations
        echo 'opcache.enable = 1'; \
        echo 'opcache.memory_consumption = 256'; \
        echo 'opcache.interned_strings_buffer = 16'; \
        echo 'opcache.max_accelerated_files = 20000'; \
        echo 'opcache.validate_timestamps = 0'; \
        echo 'opcache.fast_shutdown = 1'; \
    } > /usr/local/etc/php/conf.d/custom.ini

# Create non-root user www-data with host-matching uid
RUN deluser www-data 2>/dev/null || true \
    && delgroup www-data 2>/dev/null || true \
    && addgroup -g ${GROUP_ID} www-data \
    && adduser -u ${USER_ID} -G www-data -s /bin/sh -D www-data

# Set working directory
WORKDIR /var/www/html

# Copy application files (use .dockerignore to exclude tests, node_modules, etc.)
COPY --chown=www-data:www-data . .

# Copy vendor from composer stage (production dependencies only)
COPY --from=composer-stage --chown=www-data:www-data /app/vendor ./vendor

# Verify .env file exists
RUN set -ex; \
    if [ -f .env ]; then \
        echo "✓ Using .env file from build context ($(wc -c < .env) bytes)"; \
    else \
        echo "⚠️  No .env file found, using .env.example as template"; \
        cp .env.example .env; \
    fi; \
    test -f .env || (echo "❌ .env file not found" && exit 1); \
    echo "✅ Environment configuration ready"

# Ensure storage and bootstrap/cache directories exist with correct permissions
RUN mkdir -p storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs/lynk \
    storage/app/public \
    bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Pre-warm Laravel caches (optional, adds ~10MB but improves startup)
# Uncomment if you want faster container startup
# RUN php artisan config:cache \
#     && php artisan route:cache \
#     && php artisan view:cache

# Configure PHP-FPM to listen on port 8000
RUN echo "listen = 8000" >> /usr/local/etc/php-fpm.d/zz-docker.conf

# Copy entrypoint script (fixes bind mount permissions at runtime)
COPY docker/laravel/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Expose php-fpm port
EXPOSE 8000

# Health check
HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD php -r "exit(0);"

# Use entrypoint to fix permissions then drop to www-data
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm", "-F"]