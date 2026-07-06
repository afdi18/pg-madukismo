# syntax=docker/dockerfile:1.7

FROM php:8.2-cli-bookworm AS base

ARG DEBIAN_FRONTEND=noninteractive

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        autoconf \
        ca-certificates \
        curl \
        git \
        gnupg \
        g++ \
        libcurl4-openssl-dev \
        libicu-dev \
        libonig-dev \
        libpq-dev \
        libxml2-dev \
        libzip-dev \
        make \
        pkg-config \
        unixodbc-dev \
        unzip \
        zip \
    && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
        | gpg --dearmor -o /usr/share/keyrings/microsoft.gpg \
    && echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
        > /etc/apt/sources.list.d/microsoft-prod.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 \
    && docker-php-ext-install -j$(nproc) \
        bcmath \
        curl \
        intl \
        mbstring \
        opcache \
        pdo_pgsql \
        pgsql \
        xml \
        zip \
    && pecl install sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable sqlsrv pdo_sqlsrv \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS vendor

WORKDIR /var/www/html

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --prefer-dist \
        --optimize-autoloader \
        --no-scripts

FROM node:20-bookworm-slim AS frontend

WORKDIR /var/www/html

COPY package.json package-lock.json ./

RUN npm ci

COPY .env.example .env
COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json tailwind.config.js postcss.config.js ./

RUN npm run build

FROM base AS app

WORKDIR /var/www/html

COPY . .
COPY --from=vendor /var/www/html/vendor ./vendor
COPY --from=frontend /var/www/html/public/build ./public/build

RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache \
    && composer dump-autoload --optimize --no-interaction \
    && sh -lc 'php artisan package:discover --ansi || true'

EXPOSE 8000

CMD ["sh", "-lc", "php artisan serve --host=0.0.0.0 --port=8000"]
