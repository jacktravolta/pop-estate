# pop-full-stack-php-symfony - Solución
## Levantar
docker compose up -d --build
# espera 15s
## Acceso
http://localhost:8080
Login: admin@pop.cl / admin123
## Flujo
1. /settlements -> crear liquidación con items
2. Pagar
3. /invoices/generator -> periodo 2025-10 -> Preview -> Crear -> Descarga TXT
## Decisiones
- UniqueEntity en Settlement evita duplicados
- BillingService evita refacturar periodo ya EMITIDO
- Archivo plano según facturacion.cl
- Extras: JWT, pgvector, TextToSQL con Ollama qwen2.5:1.5b como plus
