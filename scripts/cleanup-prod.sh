#!/bin/bash
set -e
echo "=== LIMPIEZA PRODUCCION POP-ESTATE ==="

# 1. Borra basura dev
echo ">> Borrando basura..."
rm -f .env.local .env.local.php
rm -f .phpunit.result.cache phpunit.xml
rm -rf var/cache/* var/log/* var/sessions/*
rm -rf coverage/ .php-cs-fixer.cache
find . -name "*.bak" -o -name "*~" -o -name "*.swp" -o -name ".DS_Store" | xargs rm -f 2>/dev/null || true

# 2. Limpia docker dev data
echo ">> Borrando volumenes dev..."
docker compose down -v --remove-orphans 2>/dev/null || true
docker volume rm pop-estate_db_data pop-estate_ollama_data 2>/dev/null || true

# 3. Verifica que no queden duplicados
echo ">> Verificando duplicados por hash..."
DUP=$(find src templates config -type f \( -name "*.php" -o -name "*.twig" \) 2>/dev/null | xargs md5sum 2>/dev/null | sort | uniq -w32 -d | wc -l)
if [ "$DUP" -gt 0 ]; then
  echo "WARN: Hay $DUP archivos duplicados exactos:"
  find src templates config -type f \( -name "*.php" -o -name "*.twig" \) 2>/dev/null | xargs md5sum 2>/dev/null | sort | uniq -w32 -dD
else
  echo "OK: No hay duplicados"
fi

# 4. Optimiza composer para prod
echo ">> Optimizando composer..."
composer install --no-dev --optimize-autoloader --no-interaction

# 5. Crea .env.prod limpio si no existe
if [ ! -f .env.prod ]; then
cat > .env.prod <<'ENV'
APP_ENV=prod
APP_SECRET=CHANGE_ME_GENERATE_NEW
DATABASE_URL=postgresql://pop:pop_estate_secret@database:5432/pop_estate?serverVersion=16&charset=utf8
DATABASE_RO_URL=postgresql://pop_ro:ro_secret@database:5432/pop_estate?serverVersion=16&charset=utf8
MESSENGER_TRANSPORT_DSN=doctrine://default?auto_setup=0
OLLAMA_URL=http://ollama:11434
IVA_RATE=0.19
JWT_PASSPHRASE=jwt_secret_prod
JWT_TTL=3600
ENV
echo ">> .env.prod creado - CAMBIA APP_SECRET!"
fi

# 6. Lista que va a producción
echo ""
echo "=== ARCHIVOS FINALES QUE VAN A PROD ==="
du -sh . --exclude=vendor --exclude=.git --exclude=var --exclude=ollama_data --exclude=db_data
echo ""
ls -lh docker/php/
echo ""
echo "=== CHECKLIST PROD ==="
[ -f docker/php/entrypoint.sh ] && echo "[OK] entrypoint.sh existe" || echo "[FAIL] entrypoint.sh falta"
[ -f docker/php/Dockerfile ] && echo "[OK] Dockerfile existe" || echo "[FAIL] Dockerfile falta"
grep -q "MESSENGER_TRANSPORT_DSN" .env && echo "[OK] MESSENGER_TRANSPORT_DSN en .env" || echo "[FAIL] Falta MESSENGER"
[ ! -f .env.local ] && echo "[OK] No hay .env.local" || echo "[FAIL] .env.local existe"
echo ""

# 7. Test build prod
echo ">> Probando build prod..."
docker compose up -d --build
sleep 15
docker compose logs app --tail=20
docker exec pop-estate-db-1 psql -U pop -d pop_estate -c "SELECT count(*) FROM property;" 2>&1 | tail -n 5

echo ""
echo "=== LISTO PARA PROD ==="
echo "Para entregar: zip -r pop-estate-prod.zip . -x 'vendor/*' '.git/*' 'var/*' 'ollama_data/*' 'db_data/*'"
