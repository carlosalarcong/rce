# syntax=docker/dockerfile:1
FROM php:8.5-fpm-alpine AS base

COPY --from=ghcr.io/mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
# pgsql (nativo) además de pdo_pgsql: Hakam arma DSN "pgsql://" para crear bases de tenant, y DBAL lo resuelve al driver nativo.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN apk add --no-cache git unzip \
    && install-php-extensions pdo_pgsql pgsql intl zip apcu

COPY docker/php/conf.d/app.ini $PHP_INI_DIR/conf.d/zz-app.ini

ENV COMPOSER_ALLOW_SUPERUSER=1
WORKDIR /var/www/app

FROM base AS dev
RUN install-php-extensions xdebug
COPY docker/php/conf.d/xdebug.ini $PHP_INI_DIR/conf.d/zz-xdebug.ini
