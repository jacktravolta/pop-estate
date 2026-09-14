#!/bin/sh
set -e
cd /var/www/html

echo ">> ENTRYPOINT: $(pwd) - ls .env*"
ls -la .env* 2>&1 || echo "no .env files in host mount"

if [ ! -f .env ]; then
  echo ">> .env no existe en volumen host, creándolo..."
  if [ -f .env.example ]; then
    cp .env.example .env
    echo ">> copiado desde .env.example"
  else
    echo ">> .env.example tampoco existe, creando default"
    cat > .env <<'EOF'
APP_ENV=dev
APP_SECRET=pop_estate_dev_secret_1234567890abcdef1234567890
DATABASE_URL=postgresql://pop:pop@db:5432/pop_estate?serverVersion=16&charset=utf8
REDIS_URL=redis://redis:6379
MAILER_DSN=smtp://mailer:1025
EOF
  fi
fi

echo ">> .env creado:"
cat .env

# Wait DB
if [ -f ./docker/wait-for-db.sh ]; then
  sh ./docker/wait-for-db.sh
else
  echo ">> Esperando db 5s..."
  sleep 5
fi

mkdir -p var/cache var/log
chmod -R 777 var 2>/dev/null || true

echo ">> cache:clear"
php bin/console cache:clear --no-warmup || echo "cache:clear fallo pero continuamos"

echo ">> migrations"
php bin/console doctrine:migrations:migrate --no-interaction || echo "migrations fallo"

exec "$@"
