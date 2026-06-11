# Diseño: Módulo "Almacén" (insumos internos)

## Contexto y propósito

SysGYM ya tiene un módulo "Productos" (`admin/products/`) para artículos que se venden a clientes (bebidas, suplementos, etc.), con stock simple (entero) sin historial de movimientos.

Se necesita un módulo separado, **"Almacén"**, para el control interno de insumos de uso operativo del gimnasio: elementos de aseo, papel higiénico, guayas/repuestos de máquinas, aceites, café, etc. A diferencia de Productos, este módulo:

- Maneja **unidades de medida variadas** (litros, kg, unidades, metros, etc.), no solo unidades enteras.
- Lleva **kardex completo**: cada entrada y salida queda registrada como movimiento individual (fecha, usuario, cantidad, motivo/proveedor), no solo un contador de stock.
- No maneja precios de venta ni costos — es control de cantidades físicas únicamente.

## Modelo de datos

### `almacen_categorias`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | int PK auto_increment | |
| `nombre` | varchar(100) | Ej: "Aseo", "Cafetería", "Repuestos Máquinas", "Papelería" |
| `borrado` | tinyint(1) default 0 | Soft delete |

### `almacen_elementos`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | int PK auto_increment | |
| `nombre` | varchar(100) | Ej: "Papel higiénico", "Aceite lubricante máquinas", "Café molido" |
| `categoria_id` | int FK → almacen_categorias.id | |
| `unidad_medida` | varchar(20) | Valor de la lista fija (ver abajo) |
| `stock_actual` | decimal(10,2) default 0 | Permite cantidades fraccionarias (litros, kg) |
| `stock_minimo` | decimal(10,2) nullable | Umbral para alerta |
| `alerta_stock` | tinyint(1) default 0 | Activa/desactiva resaltado por stock bajo |
| `estado` | tinyint(1) default 1 | Activo/Inactivo |
| `borrado` | tinyint(1) default 0 | Soft delete |

### `almacen_movimientos`
| Campo | Tipo | Notas |
|---|---|---|
| `id` | int PK auto_increment | |
| `elemento_id` | int FK → almacen_elementos.id | |
| `tipo` | enum('entrada','salida') | |
| `cantidad` | decimal(10,2) | Siempre positiva; el signo lo da `tipo` |
| `proveedor` | varchar(100) nullable | Solo aplica/se muestra para `tipo = 'entrada'` |
| `observacion` | text nullable | Texto libre, motivo en caso de salida |
| `usuario_id` | int FK → usuarios.id | Quien registró el movimiento |
| `created_at` | timestamp default CURRENT_TIMESTAMP | |

### Lista fija de unidades de medida (`unidad_medida`)

Unidad, Caja, Paquete, Rollo, Litro (L), Mililitro (ml), Galón, Kilogramo (kg), Gramo (g), Metro (m), Centímetro (cm).

Implementada como array PHP constante (no tabla), seleccionable en un `<select>` al crear/editar un elemento.

## Permisos

Se agregan a las tablas `permissions` y `role_permissions` (asignados al rol Administrador, `role_id = 1`), categoría `Almacén`:

- **`Administrar Almacén`** — CRUD de elementos y categorías (crear, editar, eliminar/desactivar).
- **`Registrar Entradas Almacén`** — permite registrar movimientos de entrada.
- **`Registrar Salidas Almacén`** — permite registrar movimientos de salida.

La vista de Movimientos es accesible si el usuario tiene **cualquiera** de los tres permisos; los botones "Registrar Entrada" / "Registrar Salida" solo se muestran según el permiso correspondiente.

## Menú lateral

Nueva sección propia "Almacén" (con su propio ícono, ej. `fa-warehouse` o `fa-boxes`), con submenú:

- **Elementos** → `admin/almacen/index.php` (visible si `Administrar Almacén`)
- **Categorías** → `admin/almacen/categorias.php` (visible si `Administrar Almacén`)
- **Movimientos** → `admin/almacen/movimientos.php` (visible si tiene cualquiera de los 3 permisos)

## Páginas y flujos

### `admin/almacen/index.php` — Elementos
- Listado (tabla AJAX, patrón similar a `admin/products/index.php`): nombre, categoría, unidad de medida, stock actual, stock mínimo, estado.
- Filtro por categoría.
- Fila/celda de stock resaltada visualmente (ej. fondo rojo/badge) cuando `alerta_stock = 1` y `stock_actual <= stock_minimo`.
- Modal crear/editar: nombre, categoría (select), unidad de medida (select de la lista fija), stock inicial (solo en creación), stock mínimo, switch alerta_stock, estado (activo/inactivo).
- Eliminar = soft delete (`borrado = 1`), igual que Productos. No se permite si tiene movimientos asociados con stock_actual > 0 (advertencia, no bloqueo duro — igual que el patrón actual de bolsillos/categorías del sistema).
- Cada acción (crear, editar, eliminar) registra log vía `log_action()` con módulo `'Almacén'`.

### `admin/almacen/categorias.php` — Categorías
- CRUD simple: listado, crear, editar (nombre), eliminar (soft delete).
- Al eliminar una categoría con elementos asociados, se muestra advertencia pero se permite (los elementos quedan con la categoría "huérfana"/borrada — se filtra igual en el listado mostrando "Sin categoría" o similar). Esto sigue el patrón flexible existente en el sistema (ej. bolsillos borrados en Productos).

### `admin/almacen/movimientos.php` — Movimientos (kardex)
- Tabla única (AJAX) con todos los movimientos, columnas: fecha, elemento, categoría, tipo (Entrada/Salida con badge de color), cantidad + unidad, proveedor (si aplica), observación, usuario.
- Filtros: elemento, tipo, rango de fechas, usuario.
- Botón **"Registrar Entrada"** (si tiene permiso `Registrar Entradas Almacén`): modal con elemento (select), cantidad, proveedor (texto opcional), observación (texto opcional). Al guardar:
  - Inserta en `almacen_movimientos` (`tipo = 'entrada'`).
  - `UPDATE almacen_elementos SET stock_actual = stock_actual + :cantidad`.
  - `log_action('Registrar Entrada Almacén', ...)`.
- Botón **"Registrar Salida"** (si tiene permiso `Registrar Salidas Almacén`): modal con elemento (select), cantidad, observación (texto opcional). Validación: `cantidad <= stock_actual` del elemento (error si no hay suficiente stock). Al guardar:
  - Inserta en `almacen_movimientos` (`tipo = 'salida'`, `proveedor = NULL`).
  - `UPDATE almacen_elementos SET stock_actual = stock_actual - :cantidad`.
  - `log_action('Registrar Salida Almacén', ...)`.

## Fuera de alcance (no incluido en esta versión)

- Manejo de costos/valor monetario del inventario.
- Catálogo configurable de unidades de medida (queda como lista fija en código).
- Integración con alertas automáticas por WhatsApp/cron para stock mínimo (solo resaltado visual en el listado).
- Reportes/exportación de movimientos.
