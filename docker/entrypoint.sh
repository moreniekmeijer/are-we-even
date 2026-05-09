#!/bin/sh
set -e

# Default port for local Docker; Render overrides this with its own $PORT
export PORT=${PORT:-80}

echo "Configuring Nginx for port ${PORT}..."
envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template \
  > /etc/nginx/http.d/default.conf

echo "Waiting for database..."
until php bin/console doctrine:query:sql "SELECT 1" > /dev/null 2>&1; do
  echo "  database not ready, retrying..."
  sleep 2
done

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Warming cache..."
php bin/console cache:warmup --env=prod

exec "$@"