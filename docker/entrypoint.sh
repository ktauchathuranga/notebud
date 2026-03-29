#!/bin/sh
set -e

# Prefer runtime-provided Aiven CA cert when available.
# This avoids stale baked-in CA files when Aiven rotates certs.
if [ -n "$AIVEN_CA_PEM" ]; then
    printf "%s" "$AIVEN_CA_PEM" > /tmp/aiven-ca.pem
    chmod 600 /tmp/aiven-ca.pem
    export MYSQL_ATTR_SSL_CA=/tmp/aiven-ca.pem
fi

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    php artisan key:generate --force
fi

# Create storage directories if they don't exist
mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/logs \
    bootstrap/cache

# Fix permissions
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Clear and rebuild config cache with runtime env vars
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
