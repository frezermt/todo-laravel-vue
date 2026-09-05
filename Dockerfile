# Stage 1: Build PHP dependencies and Frontend Assets
FROM php:8.4-cli-alpine AS builder

# Install system dependencies, Node.js, and npm
RUN apk add --no-cache \
    nodejs \
    npm \
    git \
    unzip \
    libzip-dev \
    postgresql-dev \
    icu-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev

# Configure and install PHP extensions needed for Laravel & Wayfinder artisan commands
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        intl \
        zip \
        bcmath \
        gd \
        mbstring \
        pcntl \
        exif

# Copy Composer binary
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy Composer manifests and install PHP dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-progress --no-interaction

# Copy all source code
COPY . .

# Complete Composer autoloader
RUN composer dump-autoload --optimize --no-dev

# Install NPM dependencies and compile Vite / Wayfinder assets
RUN npm ci
RUN npm run build

# Stage 2: Production PHP-FPM + Nginx Runtime
FROM php:8.4-fpm-alpine

# Install production system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    gettext \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    oniguruma-dev \
    bash \
    curl

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        opcache \
        intl \
        zip \
        bcmath \
        gd \
        mbstring \
        pcntl \
        exif

# Copy Composer binary for artisan / runtime usage
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy built application, vendor dependencies, and compiled frontend assets
COPY --from=builder /app /var/www/html

# Remove node_modules to keep final production image small
RUN rm -rf /var/www/html/node_modules

# Setup Nginx and Supervisor configs
RUN mkdir -p /run/nginx /etc/supervisor/conf.d
COPY docker/nginx.conf /etc/nginx/nginx.conf.template
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh

RUN chmod +x /usr/local/bin/entrypoint.sh

# Set directory permissions for web server (www-data)
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose default HTTP port
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]