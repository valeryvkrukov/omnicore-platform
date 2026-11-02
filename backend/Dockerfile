FROM php:8.5.4-fpm-alpine

RUN apk update && apk add --no-cache \
    alpine-sdk \
    postgresql-dev \
    bash \
    git

RUN docker-php-ext-install pdo pdo_pgsql

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

EXPOSE 9000
CMD ["php-fpm"]
