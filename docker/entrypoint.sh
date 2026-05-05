#!/bin/sh
set -e

# Wait for database to be ready (optional but recommended)
# We can use a simple loop or a tool like wait-for-it.sh

echo "Running migrations..."
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

echo "Clearing cache..."
php bin/console cache:clear --env=prod

exec "$@"
