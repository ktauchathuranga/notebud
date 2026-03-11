#!/bin/sh
set -e

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Create storage directories if they don't exist
mkdir -p storage/framework/{sessions,views,cache} storage/logs bootstrap/cache

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear and rebuild config cache with runtime env vars
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
