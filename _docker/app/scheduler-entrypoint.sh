#!/bin/sh
set -e

cd /var/www/backend

echo "=== Scheduler: waiting for app to initialize ==="
until [ -f /var/www/backend/vendor/autoload.php ]; do
    sleep 2
done

echo "=== Scheduler: starting ==="
exec php artisan schedule:work
