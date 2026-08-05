# =========================
# Stage 1 - Frontend Build
# =========================
FROM node:22-alpine AS frontend

WORKDIR /app

COPY package*.json ./
RUN npm ci

COPY . .

RUN npm run build

# =========================
# Stage 2 - PHP
# =========================
FROM php:8.4-fpm

ARG UID=1000
ARG GID=1000

RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    libicu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
    bcmath \
    exif \
    gd \
    intl \
    pdo_mysql \
    zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupmod -o -g ${GID} www-data \
    && usermod -o -u ${UID} -g www-data www-data

WORKDIR /var/www/html

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-scripts \
    --no-interaction

COPY --from=frontend /app/public/build ./public/build

COPY docker/php-fpm/zz-ebooking.conf /usr/local/etc/php-fpm.d/zz-ebooking.conf
COPY docker/php-fpm/entrypoint.sh /usr/local/bin/ebooking-entrypoint

RUN ln -s ../storage/app/public public/storage \
    && chmod +x /usr/local/bin/ebooking-entrypoint

RUN chown -R www-data:www-data storage bootstrap/cache

USER www-data

ENTRYPOINT ["/usr/local/bin/ebooking-entrypoint"]

CMD ["php-fpm"]
