# Dockerfile for Laravel Application

# --- Builder Stage ---
FROM composer:2 as builder
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-plugins --no-scripts --prefer-dist

# --- PHP-FPM Stage ---
FROM php:8.3-fpm-alpine as app
WORKDIR /var/www/html

# Install system dependencies
RUN apk add --no-cache \
    libpng-dev \
    libzip-dev \
    jpeg-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip

# Copy application files
COPY . .

# Copy vendor dependencies from builder stage
COPY --from=builder /app/vendor ./vendor

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
RUN chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port for PHP-FPM
EXPOSE 9000

# --- Nginx Stage ---
FROM nginx:alpine as web
WORKDIR /var/www/html

# Copy application files
COPY --from=app /var/www/html/public ./public

# Copy Nginx configuration
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
