#!/bin/sh
set -e
cd /var/www/html

if [ ! -f .env ]; then
  echo ">> .env no existe, creando desde .env.example"
  if [ -f .env.example ]; then
    cp .env.example .env
  else
    echo "APP_ENV=dev
APP_SECRET=pop_estate_secret_autocreated_$(date +%s)
DATABASE_URL=postgresql://pop:pop@db:5432/pop_estate?serverVersion=16&charset=utf8
REDIS_URL=redis://redis:6379
MAILER_DSN=smtp://mailer:1025" > .env
  fi
fi

echo ">> Esperando DB ${DATABASE_URL:-db:5432}..."
# tu wait-for-db original si lo tienes
if [ -x ./docker/wait-for-db.sh ]; then
  ./docker/wait-for-db.sh
else
  sleep 3
fi

# Permisos y cache
mkdir -p var/cache var/log
chmod -R 777 var 2>/dev/null || true

# Composer install solo si vendor no existe (para clone fresco)
if [ ! -d vendor ]; then
  composer install --no-interaction --no-progress
fi

# Si falta APP_SECRET genera
php bin/console cache:clear --no-warmup || true

# Migraciones automáticas en dev (opcional pero ayuda al primer up)
php bin/console doctrine:migrations:migrate --no-interaction || echo ">> migraciones pendientes o ya aplicadas"

exec "$@"
