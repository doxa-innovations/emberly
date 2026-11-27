# syntax=docker/dockerfile:1

FROM docker.io/library/composer:2.8 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative --no-interaction

FROM docker.io/library/node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .gitignore vite.config.js ./
RUN npm ci --ignore-scripts --no-audit --no-fund
COPY resources ./resources
RUN npm run build

FROM docker.io/dunglas/frankenphp:1.14.0-php8.4.28-bookworm
RUN install-php-extensions bcmath intl opcache pcntl pdo_pgsql \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && setcap -r /usr/local/bin/frankenphp \
    && chown -R www-data:www-data /config /data
WORKDIR /app
COPY --chown=www-data:www-data . .
COPY --chown=www-data:www-data --from=vendor /app/vendor ./vendor
COPY --chown=www-data:www-data --from=assets /app/public/build ./public/build
USER www-data
EXPOSE 8000
ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php", "artisan", "octane:frankenphp", "--host=0.0.0.0", "--port=8000", "--workers=2", "--max-requests=500"]
