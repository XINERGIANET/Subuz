# Resumen de Actualización: Historial de Liquidaciones y Comprobantes

Se ha implementado una nueva funcionalidad que permite agregar números de comprobantes (facturas, boletas, etc.) a los reportes de liquidación y mantener un registro permanente de cada PDF generado.

## 1. Agregado de Comprobantes al PDF (Modal)
Se interceptó el botón de "Generar reporte" en la vista de Liquidación. Ahora se abre un modal que ofrece tres opciones de configuración antes de generar el PDF:
* **Ninguno**: Genera el reporte clásico sin añadir información adicional.
* **General**: Permite ingresar un único comprobante (ej: `F001-00123`). Este número aparecerá destacado en la cabecera del reporte PDF.
* **Por cada venta**: El sistema carga dinámicamente vía AJAX todas las ventas a crédito de ese cliente en el rango de fechas, y muestra una tabla donde se puede escribir un correlativo individual distinto para cada guía. Estos correlativos se imprimen al lado de cada ítem en el reporte.

## 2. Persistencia en Base de Datos
Para soportar un historial, se creó el modelo y la migración para la nueva tabla `liquidations`. 
* Cada vez que se hace clic en "Continuar y Generar PDF", se guarda un registro en la base de datos justo antes de procesar el archivo.
* La tabla guarda: `client_id`, `start_date`, `end_date`, `payment_date`, `correlative_type`, `general_correlative`, `sale_correlatives` (en formato JSON) y el `total`.

## 3. Pantalla de Historial de Liquidaciones
* Se añadió el botón **"Ver historial"** en la cabecera de la vista principal de generación.
* Se creó una vista dedicada (`liquidations_history.blade.php`) que muestra una tabla estructurada y paginada con todos los reportes emitidos.
* La vista detalla la fecha en que se emitió el reporte, el cliente, las fechas que cubrió, el monto total y un badge visual que indica qué tipo de comprobantes se incluyeron (General, Por Venta o Ninguno).

## 4. Corrección de Errores de BD
Durante la creación de la migración para `liquidations`, se detectó y solucionó una incompatibilidad de llaves foráneas (`General error: 3780`). El campo `client_id` se adaptó a la estructura exacta de la tabla `clients` (`bigInteger`) permitiendo una integración perfecta. También se corrigió un error de sintaxis en el modelo `Liquidation`.

---

**Archivos Principales Modificados/Creados:**
* `routes/web.php` (Nuevas rutas para AJAX e Historial)
* `app/Http/Controllers/ReportController.php` (Lógica de PDF, AJAX y vistas)
* `resources/views/reports/liquidation.blade.php` (Integración de Modal JS)
* `resources/views/reports/liquidations_history.blade.php` (Nueva vista)
* `app/Models/Liquidation.php` (Nuevo Modelo)
* `database/migrations/...create_liquidations_table.php` (Nueva Migración)
