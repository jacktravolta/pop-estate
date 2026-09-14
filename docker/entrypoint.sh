#!/bin/sh
set -e
cd /var/www/html
echo ">> ENTRYPOINT v3 - $(date)"
if [ ! -f .env ]; then
  echo ">> CREANDO .env"
  [ -f .env.example ] && cp .env.example .env || cat > .env <<'EOF'
APP_ENV=dev
APP_SECRET=pop_estate_secret_1234567890abcdef1234567890
DATABASE_URL=postgresql://pop:pop@db:5432/pop_estate?serverVersion=16&charset=utf8
REDIS_URL=redis://redis:6379
MAILER_DSN=smtp://mailer:1025
EOF
fi
if [ ! -f vendor/autoload.php ] || [ ! -f vendor/autoload_runtime.php ]; then
  echo ">> COMPOSER INSTALL - esto tarda 60-90s primera vez..."
  composer install --no-interaction --optimize-autoloader
else
  echo ">> vendor OK"
fi
until php -r "new PDO('pgsql:host=db;dbname=pop_estate;user=pop;password=pop');" 2>/dev/null; do sleep 1; done
echo ">> DB lista"
mkdir -p var/cache var/log; chmod -R 777 var 2>/dev/null || true
php bin/console cache:clear || true
php bin/console doctrine:migrations:migrate --no-interaction || true
exec "$@"
