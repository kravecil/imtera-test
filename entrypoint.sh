#!/bin/bash

set -e

cd /var/www/html

echo "Installing Composer dependencies..."

if [ ! -f vendor/autoload.php ]; then
    composer install
fi

echo "Installing Node dependencies..."

if [ ! -d node_modules ] || [ -z "$(ls -A node_modules 2>/dev/null)" ]; then
    npm install
fi

echo "Installing Playwright browsers..."
if [ ! -d /ms-playwright ] || [ -z "$(ls -A /ms-playwright 2>/dev/null)" ]; then
    vendor/bin/playwright-install
    npx playwright install
fi


echo "Starting Laravel..."

php artisan serve --host=0.0.0.0 --port=80 &

echo "Starting Vite..."

exec npm run dev -- --host 0.0.0.0