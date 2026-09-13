FROM php:8.3-fpm
RUN apt-get update && apt-get install -y git unzip libpq-dev libzip-dev libicu-dev postgresql-client && docker-php-ext-install pdo pdo_pgsql intl zip opcache && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --no-interaction || true
COPY . .
RUN composer dump-autoload --optimize || true
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh
ENTRYPOINT ["entrypoint.sh"]
