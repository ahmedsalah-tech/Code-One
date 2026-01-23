#!/bin/bash

# Exit immediately if a command exits with a non-zero status.
set -e

# --- Helper Functions ---
info() {
    echo -e "\033[34m[INFO]\033[0m $1"
}

success() {
    echo -e "\033[32m[SUCCESS]\033[0m $1"
}

error() {
    echo -e "\033[31m[ERROR]\033[0m $1"
    exit 1
}

# --- Main Script ---

# 1. Check for Docker and Docker Compose
if ! command -v docker &> /dev/null || ! command -v docker-compose &> /dev/null; then
    error "Docker and Docker Compose are required. Please install them and try again."
fi

# 2. Set up environment file
if [ ! -f .env ]; then
    info "No .env file found. Copying from .env.docker..."
    cp .env.docker .env
    success ".env file created."
else
    info ".env file already exists. Skipping creation."
fi

# 3. Check for APP_KEY
if grep -q "APP_KEY=$" .env; then
    error "APP_KEY is missing in your .env file. Please run 'php artisan key:generate --show', paste the key into the .env file, and run this script again."
fi

# 4. Build and start containers
info "Building and starting Docker containers in detached mode..."
docker-compose up -d --build

# Wait for MySQL container to be healthy
info "Waiting for the database container to be ready..."
while ! docker-compose exec db mysqladmin ping -h"localhost" --silent; do
    sleep 1
done
success "Database container is healthy."

# 5. Run database migrations and seeders
info "Running database migrations and seeding..."
docker-compose exec app php artisan migrate --seed
success "Migrations and seeding complete."

# 6. Final instructions
success "Docker environment is set up and running!"
info "Your application is available at: http://localhost:8000"
info "Mailpit UI is available at: http://localhost:8025"
