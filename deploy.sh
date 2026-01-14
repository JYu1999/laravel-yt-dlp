#!/bin/bash

# Stop execution on any error
set -e

echo "Deploying application..."

# 1. Pull latest code
echo "Pulling latest code..."
git pull origin master

# 2. Build and start containers
echo "Building and starting containers..."
docker compose -f docker-compose.prod.yml up -d --build

# 3. Install dependencies
echo "Installing dependencies..."
docker compose -f docker-compose.prod.yml exec -T app composer install --no-dev --optimize-autoloader

# 4. Run migrations
echo "Running migrations..."
docker compose -f docker-compose.prod.yml exec -T app php artisan migrate --force

# 5. Clear and cache config/routes/views
echo "Optimizing application..."
docker compose -f docker-compose.prod.yml exec -T app php artisan optimize:clear
docker compose -f docker-compose.prod.yml exec -T app php artisan config:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan route:cache
docker compose -f docker-compose.prod.yml exec -T app php artisan view:cache

echo "Deployment completed successfully!"
