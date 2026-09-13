# Especificaciones Funcionales (Specs)

Documento de especificaciones que guiaron el desarrollo del módulo.

## Spec 1: CRUD de Liquidaciones

Sistema completo de gestión de liquidaciones de arriendo.

- Crear liquidación con propiedad, fechas y N items (cargos/descuentos)
- Listar con búsqueda en tiempo real, filtros por estado/período y paginación
- Editar, ver detalle
- Cálculo automático: Neto = Cargos - Descuentos, IVA = 19%, Total = Neto + IVA

Validaciones:
- Propiedad y fechas obligatorias
- UniqueEntity: no duplicados (misma propiedad + mismas fechas)

Estados: PENDIENTE, PAGADA, ANULADA.

## Spec 2: Formulario Dinámico de Items

- Botón "Agregar Item" que clona prototipo de Symfony
- Botón "Eliminar" en cada item
- Cálculo en tiempo real de totales
- Buscador en tiempo real para select de propiedades

Validaciones:
- Exactamente 1 línea por clic (no 2)
- Recalcular totales al agregar/eliminar

## Spec 3: Anulación con Auditoría

Modal de confirmación con campo obligatorio "Motivo" (mín 10 caracteres).

Al confirmar, el campo `motivo_anulacion` guarda:
- Motivo escrito por el usuario
- Email del usuario que anuló
- Fecha y hora de la anulación

No se borra de la BD (trazabilidad).

## Spec 4: Generador de Facturación

- Seleccionar período (YYYY-MM)
- Preview: cantidad de liquidaciones, Neto, IVA, Total, lista de items
- Si ya existe factura para el período, mostrar "YA FACTURADO"
- Crear factura con folio automático (MAX(folio) + 1)
- Generar archivo plano TXT según facturacion.cl

Validaciones:
- Solo facturar liquidaciones PAGADAS
- No facturar liquidaciones ya asociadas a factura
- No permitir facturar mismo período dos veces

## Spec 5: Archivo Plano (facturacion.cl)

Formato TXT:
- ->Encabezado<- con datos del owner
- ->Totales<- con neto, IVA, total
- ->Detalle<- con items (LIQ-{id}, descripción, monto)

Reglas:
- Reemplazar saltos de línea y punto y coma por espacios
- Máximo 60 items
- Descuentos con monto negativo, cargos positivo

## Spec 6: Diseño UI/UX Moderno

- Color primario: #6366f1 (indigo)
- Color éxito: #10b981 (verde)
- Color peligro: #dc2626
- Border-radius: 12-16px en cards
- Iconos: Bootstrap Icons
- Cards con gradientes en headers
- Paginador moderno

## Spec 7: Prevención de Duplicados

UniqueEntity en Settlement: ['property', 'fechaInicio', 'fechaTermino']
Mensaje: "Ya existe una liquidación para esta propiedad en este período"

## Spec 8: Adaptación al Esquema Existente

Trabajar con columnas existentes de la tabla `invoice`:
- id, folio, emisor, receptor, periodo, total, estado
- NO agregar columnas: rutEmisor, neto, iva, fecha
- Calcular neto e IVA en memoria desde liquidaciones

## Spec 9: Relación Many-to-Many Invoice ↔ Settlement

- Tabla pivote: `invoice_settlement` (invoice_id, settlement_id)
- Constraint único: (invoice_id, settlement_id)
- Cascade delete
- Relación inversa con OneToMany en ambas entidades

## Spec 10: Debugging y Corrección de Errores

Errores resueltos:
1. "Field __name__ has already been rendered": capturar prototipo en variable
2. "Expected Literal, got 'is'": renombrar alias DQL a 'invSet'
3. "operator does not exist: date ~~ unknown": usar rangos de fechas
4. "column rut_emisor does not exist": adaptar al esquema real
5. Se agregan 2 items: usar cloneNode + flag isProcessing
6. Form no guarda: asegurar token CSRF en form_end

## Resumen

10 specs implementadas y validadas:
- CRUD Liquidaciones ✅
- Formulario Dinámico ✅
- Anulación con Auditoría ✅
- Generador de Facturación ✅
- Archivo Plano ✅
- Diseño UI/UX ✅
- Prevención de Duplicados ✅
- Adaptación al Esquema ✅
- Relación M:N ✅
- Debugging ✅
