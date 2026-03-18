#!/bin/bash

# Initialize Docker environment and install Composer dependencies

echo "Starting Docker containers..."
docker-compose up -d

echo "Waiting for MySQL to be ready..."
sleep 15

echo "Installing Composer dependencies..."
docker-compose exec -T web bash -c "
  apt-get update && \
  apt-get install -y --no-install-recommends curl git && \
  curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
  composer install --no-interaction
"

echo "Composer dependencies installed!"
echo ""
echo "You can now run tests with:"
echo "  docker-compose exec web vendor/bin/phpunit"
echo ""
echo "Or access the web application at:"
echo "  http://localhost:8080"
