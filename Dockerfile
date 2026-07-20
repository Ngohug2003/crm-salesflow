# syntax=docker/dockerfile:1.7

FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-progress --no-scripts --ignore-platform-req=ext-pcntl

FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json ./
RUN npm install
COPY --from=vendor /app/vendor ./vendor
COPY resources ./resources
COPY vite.config.js ./
RUN npm run build

FROM php:8.5-fpm-bookworm AS runtime
ARG APP_ENV=local
ENV APP_ENV=${APP_ENV} \
    COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y --no-install-recommends \
        curl git libicu-dev libjpeg62-turbo-dev libpng-dev libpq-dev libzip-dev procps unzip \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl pcntl pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-salesflow.ini
COPY docker/php/entrypoint.sh /usr/local/bin/salesflow-entrypoint

RUN chmod +x /usr/local/bin/salesflow-entrypoint \
    && mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && php artisan package:discover --ansi

USER www-data
EXPOSE 9000 8080
ENTRYPOINT ["salesflow-entrypoint"]
CMD ["php-fpm"]

FROM nginx:1.27-alpine AS web
WORKDIR /var/www/html
COPY public ./public
COPY --from=assets /app/public/build ./public/build
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
EXPOSE 80
