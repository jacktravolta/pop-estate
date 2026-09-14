FROM php:8.3-fpm
RUN apt-get update && apt-get install -y git unzip libpq-dev libzip-dev libicu-dev libonig-dev && docker-php-ext-install pdo pdo_pgsql intl zip opcache && apt-get clean && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY . .
RUN chmod +x docker/entrypoint.sh docker/wait-for-db.sh 2>/dev/null || true
RUN mkdir -p var/cache var/log && chmod -R 777 var
# crea .env.example si no existe para que el COPY no falle
RUN if [ ! -f .env.example ]; then echo "APP_ENV=dev\nAPP_SECRET=test\nDATABASE_URL=postgresql://pop:pop@db:5432/pop_estate?serverVersion=16&charset=utf8\nREDIS_URL=redis://redis:6379\nMAILER_DSN=smtp://mailer:1025" > .env.example; fi
RUN if [ ! -f .env ]; then cp .env.example .env; fi
ENTRYPOINT ["sh", "./docker/entrypoint.sh"]
CMD ["php-fpm"]
