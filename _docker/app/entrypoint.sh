#!/bin/sh
set -e

echo "=== Installing backend dependencies ==="
cd /var/www/backend
composer install --no-interaction --optimize-autoloader

echo "=== Ensuring storage directories exist ==="
mkdir -p storage/framework/views
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/logs
chmod -R 775 storage

echo "=== Running migrations ==="
php artisan migrate --force --no-interaction

echo "=== Linking storage ==="
php artisan storage:link --force

echo "=== Building frontend admin ==="
cd /var/www/frontend/admin
npm install
npm run build

echo "=== Building frontend site ==="
cd /var/www/frontend/site
npm install
npm run build

echo "=== Starting PHP-FPM ==="
php-fpm