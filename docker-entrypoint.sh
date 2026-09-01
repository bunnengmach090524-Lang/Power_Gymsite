#!/bin/sh
set -e

# Render assigns a random port via $PORT — Apache must listen on it, not 80.
if [ -n "$PORT" ]; then
  sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf
  sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf
fi

# Cache config/routes/views for production speed
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run pending migrations automatically on every deploy
php artisan migrate --force

# Re-create the storage symlink (Render's filesystem is fresh on each deploy)
php artisan storage:link || true

exec "$@"
