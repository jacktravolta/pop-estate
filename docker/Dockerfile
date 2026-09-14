FROM php:8.2-fpm
RUN apt-get update && apt-get install -y git unzip libpq-dev libicu-dev libzip-dev libonig-dev && docker-php-ext-install pdo_pgsql intl zip opcache
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock* ./
RUN composer install --no-interaction --no-progress --no-scripts || true
COPY . .
RUN composer install --no-interaction --no-progress
RUN chown -R www-data:www-data /var/www/html
