# pop-estate - Full Stack PHP Symfony

## 1. Clonar e instalar
```bash
git clone https://github.com/jacktravolta/pop-estate.git
cd pop-estate
docker compose up -d --build
# espera 15s que parta postgres + php
docker compose logs app --tail=20 -f
# cuando veas "server listening" Ctrl+C
```

## 2. Seed 148 PAGADA - Datos de prueba (obligatorio para probar generador)

El proyecto inicia limpio con solo 2 propiedades de AppFixtures. Para probar el generador por periodos necesitas cargar 148 liquidaciones.

```bash
# Carga 148 PAGADA dic 2025 + 1 ANULADA
docker compose exec app php bin/console app:seed:settlements --truncate

# Verificar
docker compose exec database psql -U pop -d pop_estate -c "SELECT estado, COUNT(*), SUM(total) FROM settlement GROUP BY estado;"

# Esperado:
# PAGADA | 148 | 113000000 aprox
# ANULADA | 1 | 673910

# Stress test 200
docker compose exec app php bin/console app:seed:settlements --count=200 --truncate
```

Implementación: src/Command/SeedSettlementsCommand.php
Reusa tu AppFixtures existente (users, companies, owners) + crea 80 propiedades + 148 settlements con created_by_id = admin@pop.cl

## 3. Acceso

URL: http://localhost:8080

Login admin:
```
Email: admin@pop.cl
Pass: admin123
```

Login user:
```
Email: user@pop.cl
Pass: user123
```

## 4. Levantar / Apagar sin perder datos

```bash
docker compose up -d --build   # levanta
docker compose down            # apaga sin borrar BD (OK)
docker compose down -v         # ⚠️ BORRA TODO - solo si quieres empezar de 0
```

## 5. Flujo de negocio completo

1. Ir a /settlements -> Nueva liquidación -> agregar items -> Guardar
2. Marcar como PAGADA
3. Ir a /invoices/actions/generator
4. Periodo: 2025-12-01 a 2025-12-31 -> Preview -> ves 148 -> Crear -> Descarga TXT
5. /invoices -> ver facturas EMITIDAS
6. /invoices/actions/preview-by-period?inicio=2025-12-01&fin=2025-12-31&property=3 -> cuadrado por propiedad

## 6. Comandos útiles (Makefile)

```bash
make up          # levanta
make down        # apaga sin borrar
make down-v      # borra todo con confirmación BORRAR
make fix         # fix caliente sin perder BD (schema + cache)
make ps          # estado contenedores
make logs        # logs app
make sh          # entra a bash del contenedor app
make db          # SELECT ultimas 10 liquidaciones
make seed-148    # carga 148 PAGADA + 1 ANULADA
```

## 7. Decisiones técnicas

- **UniqueEntity en Settlement**: evita duplicar liquidación para misma propiedad + mismo periodo (fecha_inicio + fecha_termino + property_id)
- **BillingService**: evita refacturar periodo ya EMITIDO/PAGADA, agrupa por propiedad, 1 factura por propiedad
- **Settlement estado**: PENDIENTE -> PAGADA / ANULADA. Solo PAGADA genera factura
- **Invoice**: EMITIDA -> PAGADA / ANULADA con auditoría (motivo min 10 chars)
- **Archivo plano**: formato según facturacion.cl (TXT con separadores)
- **Extras plus**: JWT (lexik), pgvector, TextToSQL con Ollama qwen2.5:1.5b en /ai/query
- **Stack**: Symfony 6.4, Doctrine, Postgres 15 + pgvector, Docker, Twig + Bootstrap 5

## 8. Estructura

```
src/Entity/ (Property, Owner, Company, Settlement, Invoice, User)
src/Controller/ (SettlementController, InvoiceController, AiQueryController)
src/Service/ (BillingService)
src/Command/SeedSettlementsCommand.php -> 148 PAGADA
src/DataFixtures/AppFixtures.php -> datos base
templates/ (settlements, invoices)
```

## 9. Troubleshooting

Si ves `created_by_id violates not-null` -> ejecuta con --truncate, el comando ya setea admin@pop.cl
Si ves `DateTimeImmutable` error -> ya está corregido, usa DateTime para fecha_inicio/fin y DateTimeImmutable para createdAt
Si no carga: `make fix` o `docker compose exec app php bin/console doctrine:schema:update --force && docker compose exec app php bin/console cache:clear`

## 10. Contacto

**JUAN GUILLERMO ESPINOZA CASTRO**
Santiago, Chile | +56 9 5709 1534 | juan.espinoza.castro88@gmail.com

**PERFIL PROFESIONAL**
Analista de Sistemas con más de 20 años de experiencia en banca, seguros, retail y sector público. Especializado en entornos regulados con altos estándares de seguridad y compliance, integración con servicios core bancarios, backend Python, ETL, APIs REST, PostgreSQL con pgvector y arquitecturas RAG y NL-to-SQL. Desarrollo Full Stack PHP Symfony.

- GitHub: https://github.com/juan-espinoza-castro88
- Email: juan.espinoza.castro88@gmail.com
- Tel: +56 9 5709 1534
- Ubicación: Santiago, Chile

Proyecto: pop-estate - Sistema de liquidación y facturación para administración de propiedades (Full Stack PHP Symfony + Postgres + Docker + JWT + pgvector + Ollama qwen2.5:1.5b)

Disponible para proyectos freelance / contratación - Contacto directo por email o teléfono.
