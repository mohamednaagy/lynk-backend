#!/bin/sh
set -e

# Fix permissions for bind-mounted directories at runtime
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure consistent permissions for existing and future files
setfacl -R -m u:www-data:rwX /var/www/html/storage /var/www/html/bootstrap/cache
setfacl -R -m g:www-data:rwX /var/www/html/storage /var/www/html/bootstrap/cache

setfacl -R -m d:u:www-data:rwX /var/www/html/storage /var/www/html/bootstrap/cache
setfacl -R -m d:g:www-data:rwX /var/www/html/storage /var/www/html/bootstrap/cache

# Ensure ACL mask does not restrict permissions
setfacl -R -m m:rwX /var/www/html/storage /var/www/html/bootstrap/cache

# Execute the main command (php-fpm handles dropping to www-data internally)
exec "$@"
