FROM php:8.3-fpm

RUN apt-get update && apt-get install -y \
    git unzip libpq-dev libzip-dev libicu-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql intl zip opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copia todo (incluye .env.example pero no .env)
COPY . .

# Asegura que entrypoint sea ejecutable y que exista .env.example
RUN chmod +x docker/entrypoint.sh 2>/dev/null || true \
    && chmod +x docker/wait-for-db.sh 2>/dev/null || true \
    && [ -f .env.example ] || echo "APP_ENV=dev\nAPP_SECRET=change_me\nDATABASE_URL=postgresql://pop:pop@db:5432/pop_estate?serverVersion=16&charset=utf8\nREDIS_URL=redis://redis:6379\nMAILER_DSN=smtp://mailer:1025" > .env.example

# Si no existe .env en build, crea uno temporal para que cache:clear no falle, será sobreescrito por entrypoint en runtime
RUN [ -f .env ] || cp .env.example .env

RUN mkdir -p var/cache var/log && chmod -R 777 var

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php-fpm"]
