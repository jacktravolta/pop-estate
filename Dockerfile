FROM php:8.3-fpm
RUN apt-get update && apt-get install -y git unzip libpq-dev libzip-dev libicu-dev libonig-dev && docker-php-ext-install pdo pdo_pgsql intl zip opcache && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN chmod +x docker/entrypoint.sh 2>/dev/null || true && mkdir -p var/cache var/log && chmod -R 777 var
ENTRYPOINT ["sh", "./docker/entrypoint.sh"]
CMD ["php-fpm"]
