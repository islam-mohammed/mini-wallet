#!/bin/bash

# Exit immediately if a command exits with a non-zero status
set -e

# 1. Install Composer dependencies
echo "Installing Composer dependencies..."
composer install --no-interaction --no-progress --no-suggest

# 2. Install NPM dependencies
echo "Installing NPM dependencies..."
npm install

# 3. Set up Laravel environment
echo "Setting up Laravel environment..."
if [ ! -f .env ]; then
    cp .env.example .env
    php artisan key:generate
fi

# 4. Wait for the database to be ready
echo "Waiting for MySQL database..."
until php artisan db:monitor --databases=mysql > /dev/null 2>&1; do
    echo "  MySQL is unavailable - sleeping"
    sleep 1
done
echo "  MySQL is up - executing command"

# 5. Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# If arguments are passed, execute them
if [ "$#" -gt 0 ]; then
    exec "$@"
else
    # 6. Start the Laravel development server and Vite
    echo "Starting Laravel and Vite servers..."
    php artisan serve --host=0.0.0.0 --port=8000 &
    npm run dev -- --host

    # Wait for any process to exit
    wait -n

    # Exit with status of process that exited first
    exit $?
fi
