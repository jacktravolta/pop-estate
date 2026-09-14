# NOTES.md - pop-full-stack-php-symfony

## 1. Uso de Inteligencia Artificial (requerido por README)

**Herramienta:** Meta AI (Muse Spark 1.1) + VS Code - fecha 2025-09-13/14
**Modo:** pair-programming, no vibe-coding ciego. Cada generación se revisó y adaptó al esquema real.

### Prompts literales usados y tokens

#### P1 - CRUD Liquidaciones (Spec 1)
> "Crea CRUD de Liquidaciones en Symfony 6.4: Settlement con property FK, fecha_inicio, fecha_termino, estado PENDIENTE/PAGADA/ANULADA, motivo_anulacion TEXT nullable. SettlementItem con settlement FK, descripcion, monto (positivo cargo, negativo descuento). Cálculo automático Neto=Cargos-Descuentos, IVA 19%, Total=Neto+IVA. Listar con búsqueda en tiempo real, filtros por estado/periodo y paginación. Validación UniqueEntity property+fechas."
**Tokens:** input ~520 / output ~2,800 / total 3,320

#### P2 - Formulario dinámico (Spec 2)
> "Formulario Symfony con CollectionType para items, con botón Agregar Item que clona prototipo data-prototype, botón Eliminar por item, y cálculo en tiempo real de totales con JS. Buscador en tiempo real para select de propiedades. Validación: exactamente 1 línea por clic, no 2."
**Tokens:** input ~480 / output ~2,200 / total 2,680

#### P3 - Anulación con auditoría (Spec 3)
> "Modal Bootstrap de confirmación para anular liquidación con campo motivo obligatorio min 10 chars. Al confirmar guardar en motivo_anulacion: motivo + email usuario + fecha/hora. No borrar de BD, solo cambiar estado a ANULADA."
**Tokens:** input ~350 / output ~1,400 / total 1,750

#### P4 - Generador facturación (Spec 4 + Spec 8 + Spec 9)
> "Generador de facturación por periodo YYYY-MM. Preview con cantidad liquidaciones, Neto, IVA, Total, lista items. Si ya existe factura para periodo mostrar YA FACTURADO. Folio = MAX(folio)+1. Solo facturar liquidaciones PAGADA y sin factura asociada. Tabla invoice existente tiene solo id, folio, emisor, receptor, periodo, total, estado - NO agregar columnas rutEmisor, neto, iva. Calcular neto/iva en memoria. Relación Many-to-Many Invoice <-> Settlement con tabla pivote invoice_settlement (invoice_id, settlement_id) unique y cascade delete."
**Tokens:** input ~620 / output ~3,600 / total 4,220

#### P5 - Archivo plano facturacion.cl (Spec 5)
> "Genera TXT según https://www.facturacion.cl/manualintegracion/archivoplano.php: ->Encabezado<- con datos owner, ->Totales<- neto/iva/total, ->Detalle<- con items LIQ-{id}, descripcion, monto. Reemplazar saltos de línea y punto y coma por espacios, máximo 60 items, descuentos negativo."
**Tokens:** input ~380 / output ~1,800 / total 2,180

#### P6 - UI/UX (Spec 6)
> "Diseño moderno: color primario #6366f1 indigo, éxito #10b981, peligro #dc2626, border-radius 12-16px, Bootstrap Icons, cards con gradientes en headers, paginador moderno, todo con Twig."
**Tokens:** input ~280 / output ~1,200 / total 1,480

#### P7 - Fixes reales (Spec 10)
> "Field __name__ has already been rendered, Expected Literal got 'is', operator does not exist date ~~ unknown, column rut_emisor does not exist, Se agregan 2 items, Form no guarda"
**Tokens:** input ~650 / output ~2,100 / total 2,750

### Resumen tokens
Total real ~19,380 tokens / con reintentos ~30k tokens

## 2. Paquetes usados y por qué
- symfony/framework-bundle 6.4 + doctrine/orm: base exigida
- twig/twig: vistas
- symfony/validator: validaciones
- maker-bundle --dev: scaffolding

## 3. Decisiones
- Adaptación esquema invoice
- M:N invoice_settlement UNIQUE + CASCADE
- Idempotencia por billing_id IS NULL + overlap
- UniqueEntity property+fechas

## 4. Cómo levantar
docker compose up -d --build
