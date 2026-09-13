#!/bin/sh
set -e
echo ">> Esperando DB database:5432..."
until pg_isready -h database -U pop -d pop_estate > /dev/null 2>&1; do sleep 2; done
echo ">> DB lista"
export DATABASE_URL="postgresql://pop:pop_estate_secret@database:5432/pop_estate?serverVersion=16&charset=utf8"
if ! PGPASSWORD=pop_estate_secret psql -h database -U pop -d pop_estate -c "\dt" 2>/dev/null | grep -q "app_user"; then
  echo "=== DB VACIA -> seed ==="
  php bin/console doctrine:database:create --if-not-exists --no-interaction || true
  php bin/console doctrine:schema:drop --force --no-interaction --full-database || true
  php bin/console doctrine:schema:create --no-interaction
  php bin/console doctrine:fixtures:load --no-interaction --append || true
  php bin/console app:fixtures:load --no-interaction || true
fi
php bin/console cache:clear --no-interaction || true
exec php-fpm
