# Spec: Rediseño /admin/statistics/

**Fecha:** 2026-06-02  
**Estado:** Aprobado

---

## Objetivo

Rediseñar visualmente la página `/admin/statistics/index.php` aplicando el estilo "Opción B" (KPIs con degradado + gráfico principal ancho + columna lateral), mejorar los filtros a pills por categoría, y añadir 2 nuevos gráficos: Asistencias por Mes e Ingresos vs Egresos.

---

## Filtros

Barra de pills por **categoría** (no por gráfico individual):

| Pill | Categoría | Gráficos que muestra/oculta |
|---|---|---|
| ✅ Todos | — | Todos activos |
| 👥 Clientes | clientes | Nuevos, Planes, Activos/Inactivos, Género, Edad, Cumpleaños |
| 🚶 Asistencias | asistencias | Asistencias por Mes |
| 💰 Finanzas | finanzas | Ingresos vs Egresos |

- Al hacer clic en una pill activa se desmarca (vuelve a Todos)
- Múltiple selección permitida (se pueden combinar categorías)
- Animación slideDown/slideUp al mostrar/ocultar
- La pill activa se rellena con color sólido; inactiva es outlined

---

## KPIs — 5 tarjetas con degradado

Mostradas en fila horizontal antes de los gráficos.

| # | Label | Dato | Color |
|---|---|---|---|
| 1 | Clientes Activos | `COUNT(*) FROM clientes WHERE estado='activo' AND borrado=0` | Azul |
| 2 | Asistencias Este Mes | `COUNT(DISTINCT idCliente) FROM asistencias WHERE fecha BETWEEN ...` | Morado |
| 3 | Ingresos del Mes | `SUM(valor) FROM ventas WHERE payment_method != 'Egreso' AND fecha BETWEEN ...` | Verde |
| 4 | Egresos del Mes | `SUM(ABS(valor)) FROM ventas WHERE payment_method = 'Egreso' AND fecha BETWEEN ...` | Rojo |
| 5 | Nuevos Este Mes | `COUNT(*) FROM clientes WHERE DATE(created_at) BETWEEN ...` | Teal |

---

## Layout

```
[ KPI Activos ] [ KPI Asist.Mes ] [ KPI Ingresos ] [ KPI Egresos ] [ KPI Nuevos ]

┌────────────────────────────────┬─────────────────────┐
│  📈 Asistencias por Mes (new)  │  ⚖️ Activos/Inact.  │
│  💰 Ingresos vs Egresos (new)  │  ⚥ Género           │
│  ➕ Nuevos Clientes            │  📅 Edad            │
│  🎂 Cumpleaños por Mes         │                     │
└────────────────────────────────┴─────────────────────┘

[ 🏷️ Usuarios por Plan ]   (ancho completo o 2/3)
```

- Columna izquierda: `flex: 2` — gráficos grandes apilados
- Columna derecha: `flex: 1` — gráficos compactos apilados
- No usa Bootstrap grid; usa CSS flexbox/grid propio del sistema

---

## Gráficos existentes — se conservan todos

Cada uno en su propio `include()`. Solo cambia el contenedor HTML/CSS que los envuelve.

| Archivo | Gráfico | Categoría |
|---|---|---|
| `nuevos.php` | Nuevos Clientes Mensuales (línea) | clientes |
| `planes.php` | Usuarios por Plan (barras) | clientes |
| `activo.php` | Activos vs Inactivos (donut) | clientes |
| `genero.php` | Género (pie) | clientes |
| `edad.php` | Distribución por Edad (barras) | clientes |
| `cumpleanos.php` | Cumpleaños por Mes (barras) | clientes |

---

## Gráficos nuevos

### `asistencias_mes.php`
- **Tipo:** línea con área rellena
- **Datos:** asistencias únicas por mes, últimos 12 meses
- **Query:**
  ```sql
  SELECT DATE_FORMAT(fecha, '%b %Y') AS label, COUNT(DISTINCT idCliente) AS total
  FROM asistencias
  WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
  GROUP BY DATE_FORMAT(fecha, '%Y-%m')
  ORDER BY DATE_FORMAT(fecha, '%Y-%m') ASC
  ```
- **Color:** `#8b5cf6` (morado) con área rgba(139,92,246,0.12)
- **Categoría:** asistencias

### `ingresos_egresos.php`
- **Tipo:** barras agrupadas
- **Datos:** ingresos y egresos por mes, últimos 6 meses
- **Query:**
  ```sql
  SELECT
    DATE_FORMAT(fecha, '%b %Y') AS label,
    SUM(CASE WHEN payment_method != 'Egreso' THEN valor ELSE 0 END) AS ingresos,
    SUM(CASE WHEN payment_method = 'Egreso' THEN ABS(valor) ELSE 0 END) AS egresos
  FROM ventas
  WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  GROUP BY DATE_FORMAT(fecha, '%Y-%m')
  ORDER BY DATE_FORMAT(fecha, '%Y-%m') ASC
  ```
- **Colores:** ingresos `rgba(16,185,129,0.8)`, egresos `rgba(239,68,68,0.7)`
- **Categoría:** finanzas

---

## CSS / Estilo

- No se usa Bootstrap CDN (ya está cargado vía `header.php` si aplica, o se omite)
- CSS propio inline en `index.php` con variables del sistema (`var(--system-color-primary)`)
- Soporta dark mode mediante `.dark-mode` body class (usa las variables CSS del sistema)
- Cards con `border-radius: 14px`, sombra suave, borde `1px solid var(--border-color)`
- KPI cards: degradado de color de fondo, texto blanco, pseudo-elemento `::after` como círculo decorativo
- Pills de filtro: borde del color de categoría; al activar → fondo sólido

---

## Archivos a modificar/crear

| Archivo | Acción |
|---|---|
| `admin/statistics/index.php` | Reescribir HTML/CSS completo; conservar todos los `include()` |
| `admin/statistics/asistencias_mes.php` | Crear nuevo |
| `admin/statistics/ingresos_egresos.php` | Crear nuevo |

Los archivos de gráficos existentes (`nuevos.php`, `planes.php`, etc.) **no se modifican**.

---

## Lo que NO cambia

- Ningún archivo PHP de gráficos existente
- La lógica de permisos (`restriction.php`)
- La sesión y autenticación
