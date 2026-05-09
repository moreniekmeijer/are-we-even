# Stage 1: Build
FROM php:8.2-fpm-alpine AS build

RUN apk add --no-cache git unzip libpq-dev libzip-dev icu-dev

RUN docker-php-ext-install pdo_pgsql zip intl opcache

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .

RUN APP_ENV=prod composer install \
    --no-dev \
    --optimize-autoloader \
    --classmap-authoritative \
    --no-interaction


# Stage 2: Production
FROM php:8.2-fpm-alpine

ENV APP_ENV=prod

RUN apk add --no-cache nginx libpq libzip icu-libs supervisor gettext

# Copy compiled PHP extensions from build stage instead of recompiling
COPY --from=build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# Opcache production config
COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/opcache.ini

# Copy application
COPY --from=build /var/www/html /var/www/html

# Copy configs
COPY docker/nginx.conf.template /etc/nginx/http.d/default.conf.template
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html

RUN chown -R www-data:www-data var public

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
