#!/bin/sh
set -e
cd /var/www/html
[ -f .env ] || cp .env.example .env
[ -f vendor/autoload.php ] || composer install --no-interaction
until php -r "new PDO('pgsql:host=db;dbname=pop_estate;user=pop;password=pop');" 2>/dev/null; do sleep 1; done
php bin/console doctrine:database:create --if-not-exists --no-interaction || true
php bin/console cache:clear --no-warmup || true
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
php bin/console app:create-user admin@pop.cl admin123 ROLE_ADMIN --no-interaction 2>/dev/null || true
exec "$@"
