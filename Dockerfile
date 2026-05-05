# Stage 1: Build
FROM php:8.2-fpm-alpine AS build

# Install system dependencies
RUN apk add --no-cache \
    git \
    unzip \
    libpq-dev \
    libzip-dev \
    icu-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_pgsql \
    zip \
    intl \
    opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock symfony.lock ./

# Install dependencies
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application source
COPY . .

# Run composer autoloader and scripts
RUN APP_ENV=prod composer install --no-dev --optimize-autoloader --classmap-authoritative

# Warm up cache
RUN APP_ENV=prod bin/console cache:warmup

# Stage 2: Production
FROM php:8.2-fpm-alpine

ENV APP_ENV=prod

# Install Nginx and other runtime dependencies
RUN apk add --no-cache \
    nginx \
    libpq \
    libzip \
    icu-libs \
    supervisor

# Install PHP extensions (needed at runtime too)
RUN apk add --no-cache libpq-dev libzip-dev icu-dev \
    && docker-php-ext-install pdo_pgsql zip intl opcache \
    && apk del libpq-dev libzip-dev icu-dev

# Copy application from build stage
COPY --from=build /var/www/html /var/www/html

# Copy Nginx config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf

# Copy Supervisor config
COPY docker/supervisord.conf /etc/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

# Fix permissions
RUN chown -R www-data:www-data var public

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
