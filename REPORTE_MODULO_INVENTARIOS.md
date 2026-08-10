# Reporte de Implementación: Módulo de Inventarios y Kardex (`Subuz`)

Este documento detalla la arquitectura, archivos creados/modificados y los pasos de despliegue para el nuevo módulo de **Control de Inventarios y Kardex** en el sistema **Subuz**.

---

## 1. Resumen Ejecutivo

El módulo responde a la solicitud de contar con un control consolidado de existencias que permita gestionar para cada ítem:
$$\text{Saldo Final} = \text{Saldo Inicial} + \text{Ingresos (+)} - \text{Salidas (-)}$$

El módulo abarca 3 categorías principales:
1. **Insumos y Empaques:** Bidones 20L, Tapas, Sellos, Etiquetas, Bolsas 2kg, Bolsas 3kg y Bolsas 5kg.
2. **Activos (Equipos):** Dispensadores, Congeladoras y Exhibidores (conteo de disponibles vs prestados a clientes).
3. **Productos Terminados:** Bolsas de hielo y agua embotellada.

---

## 2. Archivos Creados y Modificados

### 📁 Archivos Nuevos (4)

1. **[database/migrations/2026_08_05_000000_create_inventory_movements_table.php](file:///c:/laragon/www/XPANDE/Subuz/database/migrations/2026_08_05_000000_create_inventory_movements_table.php)**
   - Migración autónoma que crea la tabla `inventory_movements` (almacena cada movimiento de Kardex).
   - Asegura la estructura de llaves primarias en MySQL si hiciera falta.
   - Siembra de forma no destructiva los insumos estándar si no existieran previamente en la BD.

2. **[app/Models/InventoryMovement.php](file:///c:/laragon/www/XPANDE/Subuz/app/Models/InventoryMovement.php)**
   - Modelo Eloquent para interactuar con la tabla `inventory_movements`.
   - Almacena: `item_type`, `item_id`, `item_name`, `movement_type` (`initial_balance`, `income`, `outcome`, `adjustment`), `quantity`, `notes` y `user_id`.

3. **[app/Http/Controllers/InventoryController.php](file:///c:/laragon/www/XPANDE/Subuz/app/Http/Controllers/InventoryController.php)**
   - `index()`: Procesa la matriz de saldos en tiempo real y toma el stock existente en BD por defecto si aún no hay movimientos cargados.
   - `storeInitialBalance()`: Configura/modifica el Saldo Inicial por producto/insumo/activo.
   - `storeMovement()`: Registra ingresos/salidas de stock y gestiona la afección opcional a **Caja** y **Egresos/Gastos**.
   - `history()`: Endpoint AJAX que sirve el historial detallado de Kardex.
   - `storeSupply()`: Registra nuevos insumos directamente.

4. **[resources/views/inventories/index.blade.php](file:///c:/laragon/www/XPANDE/Subuz/resources/views/inventories/index.blade.php)**
   - Vista principal dividida en 3 pestañas (Insumos, Activos, Productos Terminados).
   - Tarjetas de resumen en cabecera.
   - Modales interactivos para Saldo Inicial, Registrar Movimiento, Historial Kardex y Nuevo Insumo.
   - JavaScript con prevención de doble clic y estado de carga (`Procesando...`).

---

### 📝 Archivos Modificados (2)

1. **[routes/web.php](file:///c:/laragon/www/XPANDE/Subuz/routes/web.php)**
   - Registro de importación `use App\Http\Controllers\InventoryController;`.
   - Definición del grupo de rutas asociadas `/inventories`.

2. **[resources/views/template/app.blade.php](file:///c:/laragon/www/XPANDE/Subuz/resources/views/template/app.blade.php)**
   - Adición del elemento **Inventarios** en el menú de navegación principal con el icono `<i class="ti ti-boxes"></i>`.

---

## 3. Funcionalidades Implementadas

### A. Kardex y Control de Existencias
- **Saldo Inicial:** Editable en cualquier momento mediante el botón de lápiz 📝. Si un insumo/producto ya tenía stock en la base de datos previa, el sistema lo toma automáticamente como Saldo Inicial.
- **Ingresos (+):** Aumentan el stock en tiempo real y quedan registrados en la bitácora con usuario, fecha y observación.
- **Salidas (-):** Disminuyen el stock en tiempo real y quedan registradas.
- **Saldo Final (=):** Se calcula automáticamente con la fórmula $S_i + \text{Ingresos} - \text{Salidas}$.

### B. Integración Financiera Opcional (Caja y Gastos)
En el modal de **Registrar Movimiento**, se añadieron los campos opcionales **Monto S/** y **Método de Pago**:
- **Al registrar un Ingreso (+):** Si se ingresa monto y método de pago (ej: Efectivo, Yape), el sistema genera automáticamente un **Egreso (Gasto)** bajo la categoría *Compra de Inventario* que se refleja en la Caja abierta.
- **Al registrar una Salida (-):** Si se ingresa monto y método de pago, el sistema genera automáticamente un **Ingreso de dinero** en la Caja abierta.
- **Sin Método de Pago:** Si se dejan en blanco, el movimiento solo afecta al stock físico del Kardex sin alterar el flujo monetario de caja.

### C. Prevención de Registros Duplicados (Doble Clic)
- Todos los formularios cuentan con bloqueo automático (`btn.disabled = true`).
- Al hacer clic en enviar, el botón cambia dinámicamente su texto a un spinner animado con la etiqueta **"Procesando..."** o **"Guardando..."**, impidiendo envíos dobles en conexiones lentas o doble clic accidental.

---

## 4. Guía de Despliegue en VPS (Producción)

Para desplegar estos cambios en el servidor VPS, seguir estos 3 sencillos pasos:

1. **Subir los cambios al servidor (Git Pull / FTP):**
   - Asegurarse de haber subido los 4 archivos nuevos y 2 modificados listados arriba.

2. **Ejecutar la migración:**
   ```bash
   php artisan migrate
   ```
   *(La migración creará la tabla `inventory_movements` y sembrará los insumos de forma segura sin alterar registros existentes).*

3. **Limpiar cachés de Laravel:**
   ```bash
   php artisan view:clear
   php artisan config:clear
   ```
