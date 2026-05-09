#!/bin/sh
set -e

export PORT=${PORT:-80}

echo "Configuring Nginx for port ${PORT}..."
envsubst '${PORT}' < /etc/nginx/http.d/default.conf.template \
  > /etc/nginx/http.d/default.conf

# Start supervisord (nginx + php-fpm) in background first
# so Render sees an open port while we wait for the DB
/usr/bin/supervisord -c /etc/supervisord.conf &
SUPERVISORD_PID=$!

echo "Waiting for database..."
# Parse host and port from DATABASE_URL
DB_HOST=$(echo "$DATABASE_URL" | sed 's|.*@\([^:/]*\).*|\1|')
DB_PORT=$(echo "$DATABASE_URL" | sed 's|.*:\([0-9]*\)/.*|\1|')
DB_PORT=${DB_PORT:-5432}

until nc -z "$DB_HOST" "$DB_PORT"; do
  echo "  database not ready, retrying..."
  sleep 2
done

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Warming cache..."
php bin/console cache:warmup --env=prod

# Wait for supervisord to keep container alive
wait $SUPERVISORD_PID