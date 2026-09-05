#!/bin/sh
set -e

# Default PORT to 80 if not set (Render provides PORT environment variable)
export PORT="${PORT:-80}"

# Ensure Nginx runtime directory exists
mkdir -p /run/nginx

# Clear any default Alpine configs to prevent conflict
rm -rf /etc/nginx/http.d/* /etc/nginx/conf.d/* 2>/dev/null  true

# Substitute PORT in full nginx configuration template
envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf

# Setup storage link
php artisan storage:link --force  true

# Production optimisations
if [ "$APP_ENV" = "production" ]; then
    echo "Caching configuration, routes, and views..."
    php artisan config:cache  true
    php artisan route:cache  true
    php artisan view:cache  true
fi

# Run database migrations if enabled
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "Running database migrations..."
    php artisan migrate --force  echo "Migrations failed, continuing startup..."
fi

echo "Starting Supervisor on port ${PORT}..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf