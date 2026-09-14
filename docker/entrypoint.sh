#!/bin/sh
set -e
cd /var/www/html
[ -f .env ] || cp .env.example .env
echo ">> v7 esperando DB"
until php -r "new PDO('pgsql:host=db;dbname=pop_estate;user=pop;password=pop');" 2>/dev/null; do sleep 1; done
php bin/console doctrine:database:create --if-not-exists --no-interaction || true
php bin/console cache:clear --no-warmup || true
echo ">> migrate"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration || true
echo ">> schema:update fallback"
php bin/console doctrine:schema:update --force --complete --no-interaction || true
echo ">> SQL directo por si falla todo"
php bin/console doctrine:query:sql "CREATE TABLE IF NOT EXISTS app_user (id SERIAL PRIMARY KEY, email VARCHAR(180) NOT NULL UNIQUE, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, created_at TIMESTAMP NOT NULL);" || true
php bin/console doctrine:query:sql "CREATE TABLE IF NOT EXISTS company (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL);" || true
php bin/console doctrine:query:sql "CREATE TABLE IF NOT EXISTS property (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL);" || true
php bin/console doctrine:query:sql "CREATE TABLE IF NOT EXISTS owner (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL);" || true
php bin/console doctrine:query:sql "CREATE TABLE IF NOT EXISTS settlement (id SERIAL PRIMARY KEY, name VARCHAR(255) NOT NULL);" || true
php bin/console doctrine:query:sql "CREATE TABLE IF NOT EXISTS invoice (id SERIAL PRIMARY KEY, number VARCHAR(255) NOT NULL);" || true
php bin/console doctrine:query:sql "CREATE TABLE IF NOT EXISTS messenger_messages (id BIGSERIAL PRIMARY KEY, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP NOT NULL, available_at TIMESTAMP NOT NULL, delivered_at TIMESTAMP DEFAULT NULL);" || true
echo ">> crea admin"
php bin/console app:create-user admin@pop.cl admin123 ROLE_ADMIN --no-interaction 2>/dev/null || true
php bin/console doctrine:query:sql "SELECT table_name FROM information_schema.tables WHERE table_schema='public'" || true
exec "$@"
