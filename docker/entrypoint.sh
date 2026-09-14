#!/bin/sh
set -e
cd /var/www/html
echo ">> ENTRYPOINT v5"
[ -f .env ] || cp .env.example .env
[ -f vendor/autoload.php ] || composer install --no-interaction
echo ">> Esperando DB..."; until php -r "new PDO('pgsql:host=db;dbname=pop_estate;user=pop;password=pop');" 2>/dev/null; do sleep 1; done
php bin/console doctrine:database:create --if-not-exists --no-interaction || true
mkdir -p var/cache var/log; chmod -R 777 var 2>/dev/null || true
php bin/console cache:clear || true
echo ">> MIGRACIONES"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration -v || true
# NO HACER schema:create/update - ya tenemos migrations
# Solo si no existe app_user, crea tabla (fallback)
php bin/console doctrine:query:sql "SELECT 1 FROM app_user LIMIT 1" 2>/dev/null || php bin/console doctrine:query:sql "CREATE TABLE app_user (id SERIAL PRIMARY KEY, email VARCHAR(180) NOT NULL UNIQUE, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at TIMESTAMP NOT NULL);" || true
exec "$@"
