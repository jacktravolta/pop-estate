#!/bin/sh
set -e
cd /var/www/html
echo ">> ENTRYPOINT v4 MIGRATIONS"
[ -f .env ] || { echo ">> CREANDO .env"; cp .env.example .env 2>/dev/null || cat > .env <<'EOF'
APP_ENV=dev
APP_SECRET=pop_estate_secret_1234567890abcdef1234567890
DATABASE_URL=postgresql://pop:pop@db:5432/pop_estate?serverVersion=16&charset=utf8
REDIS_URL=redis://redis:6379
MAILER_DSN=smtp://mailer:1025
EOF
}
[ -f vendor/autoload.php ] || { echo ">> COMPOSER INSTALL"; composer install --no-interaction --optimize-autoloader; }
echo ">> Esperando DB..."; until php -r "new PDO('pgsql:host=db;dbname=pop_estate;user=pop;password=pop');" 2>/dev/null; do sleep 1; done
echo ">> DB lista"
php bin/console doctrine:database:create --if-not-exists --no-interaction || true
mkdir -p var/cache var/log; chmod -R 777 var 2>/dev/null || true
php bin/console cache:clear || true
echo ">> MIGRACIONES"
php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration -v || true
php bin/console doctrine:schema:update --force --no-interaction || true
php bin/console doctrine:query:sql "SELECT table_name FROM information_schema.tables WHERE table_schema='public'" || true
exec "$@"
