# syntax=docker/dockerfile:1

FROM ghcr.io/roadrunner-server/roadrunner:2025.1 AS roadrunner

FROM composer:2 AS deps
WORKDIR /app
COPY composer.json composer.lock ./
# --ignore-platform-reqs: this stage's base image has no PHP extensions at all (it only
# ships the composer CLI); the extensions actually required at runtime are installed in
# the "runtime" stage below and checked there instead.
RUN composer install --no-dev --no-scripts --no-interaction --optimize-autoloader --no-progress --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev

FROM php:8.4-cli-alpine AS runtime

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql pgsql sockets intl opcache

COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

WORKDIR /app
COPY --from=deps /app /app

RUN mkdir -p var/cache/prod var/log \
    && APP_ENV=prod APP_DEBUG=0 php bin/console app:container:compile prod

EXPOSE 8080

ENTRYPOINT ["rr", "serve", "-c", ".rr.yaml"]
