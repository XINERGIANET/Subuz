# Propuesta del Módulo: Activos Fijos (Fixed Assets)

Este documento detalla la estructura y flujos de trabajo para el nuevo módulo de Activos Fijos (`/fixed-assets`), que reemplazará el registro empírico que actualmente se hace desde el módulo de Egresos.

---

## 1. Objetivos del Módulo

- **Inventario:** Tener un listado exacto de todos los equipos comprados (congeladoras, exhibidores, dispensadores, etc.).
- **Trazabilidad por Cliente:** Saber en todo momento qué equipo tiene cada cliente y el historial de préstamos/asignaciones de cada equipo.
- **Trazabilidad Financiera:** Registrar cuánto costó cada activo y de qué método de pago salió el dinero, conectándose con el módulo de Caja/Egresos.

---

## 2. Esquema de Base de Datos (Migraciones)

### Tabla: `fixed_assets` (Los Equipos)

Esta tabla será el núcleo del inventario de activos.

| Campo               | Tipo                 | Descripción                                                                                                              |
| :------------------ | :------------------- | :----------------------------------------------------------------------------------------------------------------------- |
| `id`                | `bigint`             | Identificador único.                                                                                                     |
| `name`              | `string`             | Nombre descriptivo (Ej: "Congeladora Mabe 2 Puertas").                                                                   |
| `category`          | `string`             | Categoría (Ej: "congeladoras", "exhibidores", "maquina gourmet").                                                        |
| `serial_number`     | `string`             | (Opcional) Número de serie único del fabricante.                                                                         |
| `status`            | `string`             | Estado actual: `available` (disponible), `assigned` (asignado), `maintenance` (mantenimiento), `retired` (dado de baja). |
| `purchase_date`     | `date`               | Fecha de adquisición.                                                                                                    |
| `purchase_cost`     | `decimal(10,2)`      | Monto de la compra (Ej: 350.00).                                                                                         |
| `payment_method_id` | `unsignedBigInteger` | ID del método de pago (Efectivo, Yape, BCP) para cuadrar caja.                                                           |
| `voucher_number`    | `string`             | (Opcional) Número de comprobante (Ej: "Comp: 8638263").                                                                  |
| `current_client_id` | `unsignedBigInteger` | (Nullable) ID del cliente que tiene el equipo _actualmente_.                                                             |
| `notes`             | `text`               | (Opcional) Detalles adicionales o fallas.                                                                                |
| `timestamps`        | `timestamps`         | Fechas de creación y actualización de Laravel.                                                                           |

### Tabla: `fixed_asset_assignments` (Historial de Asignaciones)

Para saber "por cliente" qué ha tenido a lo largo del tiempo.

| Campo            | Tipo                 | Descripción                                            |
| :--------------- | :------------------- | :----------------------------------------------------- |
| `id`             | `bigint`             | Identificador único de la asignación.                  |
| `fixed_asset_id` | `unsignedBigInteger` | ID del equipo prestado.                                |
| `client_id`      | `unsignedBigInteger` | ID del cliente receptor.                               |
| `assigned_date`  | `date`               | Fecha en que se entregó el equipo.                     |
| `returned_date`  | `date`               | (Nullable) Fecha en que el cliente devolvió el equipo. |
| `notes`          | `text`               | (Opcional) Condición de entrega/devolución.            |
| `timestamps`     | `timestamps`         | Fechas de registro.                                    |

---

## 3. Flujos de Trabajo (Vistas de UI)

### A. Dashboard Principal (`/fixed-assets`)

- **Vista:** Tabla estilizada (DataTables) con la lista de todos los equipos.
- **Filtros:** Por `Estado` (Disponible, Asignado), por `Categoría`, y buscador por `Nombre` o `Serie`.
- **Columnas:** Descripción, Categoría, Estado (Badge con color verde/rojo/amarillo), Cliente Actual, Fecha Compra, Costo.
- **Acciones:** Editar, Ver Historial, Asignar a Cliente, Dar de Baja.

### B. Crear un Nuevo Activo (Compra)

1.  El usuario presiona **"Nuevo Activo"**.
2.  Se abre un Modal o Formulario.
3.  Ingresa datos del equipo (Nombre, Categoría, Serie).
4.  Ingresa datos de compra (Costo S/, Fecha, Método de Pago, Comprobante).
5.  **Acción de Backend:**
    - Se crea el registro en `fixed_assets`.
    - _(Opcional/A coordinar)_ Se genera automáticamente un movimiento de **Egreso** en el módulo de Caja para que se descuente el dinero del flujo diario.

### C. Asignar/Devolver Activo (El control por Clientes)

- **Asignar:** Si el equipo está `Disponible`, el botón mostrará "Asignar". Al hacer clic, se abre un modal para seleccionar un `Cliente` y la fecha de entrega. Al guardar, el equipo pasa a `Asignado` y se crea un registro en `fixed_asset_assignments`.
- **Devolver:** Si el equipo ya está `Asignado`, el botón mostrará "Devolver". Al hacer clic, se registra la fecha de devolución, el equipo vuelve a estar `Disponible`, y el `current_client_id` queda en blanco.

---

## 4. Conexión con otros Módulos

- **Clientes:** Permite buscar y vincular clientes de la base de datos existente.
- **Métodos de Pago:** Para reflejar de qué cuenta (Efectivo, Yape, BCP) salió el dinero para comprar el equipo.
- **Egresos (Caja):** Ya no será necesario registrar "manualmente" el egreso. El sistema puede inyectar el egreso automáticamente al registrar el Activo Fijo.

> [!TIP]
> **Beneficio Principal:** Con este módulo, la dueña podrá sacar reportes exactos como: _"Muéstrame todos los exhibidores que están en la calle"_, _"Qué cliente tiene la congeladora que compramos en julio"_ o _"Cuánto dinero hemos invertido en máquinas este año"_.
