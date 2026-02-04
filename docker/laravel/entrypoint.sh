#!/bin/sh
set -e

# Fix permissions for bind-mounted directories at runtime
# This is necessary because bind mounts preserve host ownership,
# and Dockerfile chown only applies at build time
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Execute the main command (php-fpm handles dropping to www-data internally)
exec "$@"