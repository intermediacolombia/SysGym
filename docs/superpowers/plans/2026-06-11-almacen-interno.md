# Módulo Almacén (insumos internos) Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a new "Almacén" admin module for tracking internal supplies (cleaning products, paper, machine parts, oils, coffee, etc.) with categories, a catalog of items with units of measure, and a full entry/exit movement history (kardex).

**Architecture:** Pure procedural PHP following the existing `admin/products/` pattern: each page is a single file combining session/permission checks, an AJAX JSON API (`?action=fetch` / `POST action=add|edit|delete|...`), and a Bootstrap 5 + DataTables + SweetAlert2 UI. Three new MySQL tables (`almacen_categorias`, `almacen_elementos`, `almacen_movimientos`) and three new permissions wired into the existing `permissions` / `role_permissions` tables and `admin/inc/menu.php` sidebar.

**Tech Stack:** PHP (PDO via `db()`), MySQL, Bootstrap 5, jQuery, DataTables, SweetAlert2 — no build step, no test runner.

**Note on testing:** This codebase has no automated test suite (per `CLAUDE.md`: "Manual testing via admin UI"). Each task below substitutes `php -l` syntax checks (automated) plus explicit manual verification steps in the browser/admin UI in place of unit tests.

---

## Task 1: Database schema — new tables and permissions

**Files:**
- Create: `almacen_schema.sql` (project root)

- [ ] **Step 1: Write the SQL migration file**

Create `almacen_schema.sql` with the three new tables and the three new permissions, assigned to the Administrador role (id 1):

```sql
-- =====================================================
-- Módulo Almacén (insumos internos) — esquema y permisos
-- Ejecutar manualmente contra la base de datos del sistema.
-- =====================================================

CREATE TABLE `almacen_categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `borrado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `almacen_elementos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `categoria_id` int DEFAULT NULL,
  `unidad_medida` varchar(20) NOT NULL,
  `stock_actual` decimal(10,2) NOT NULL DEFAULT '0.00',
  `stock_minimo` decimal(10,2) DEFAULT NULL,
  `alerta_stock` tinyint(1) NOT NULL DEFAULT '0',
  `estado` tinyint(1) NOT NULL DEFAULT '1',
  `borrado` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `fk_almacen_elementos_categoria`
    FOREIGN KEY (`categoria_id`) REFERENCES `almacen_categorias` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE `almacen_movimientos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `elemento_id` int NOT NULL,
  `tipo` enum('entrada','salida') NOT NULL,
  `cantidad` decimal(10,2) NOT NULL,
  `proveedor` varchar(100) DEFAULT NULL,
  `observacion` text,
  `usuario_id` int DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `elemento_id` (`elemento_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `fk_almacen_movimientos_elemento`
    FOREIGN KEY (`elemento_id`) REFERENCES `almacen_elementos` (`id`),
  CONSTRAINT `fk_almacen_movimientos_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- Permisos del módulo
INSERT INTO `permissions` (`name`, `category`) VALUES
('Administrar Almacén', 'Almacén'),
('Registrar Entradas Almacén', 'Almacén'),
('Registrar Salidas Almacén', 'Almacén');

-- Asignar todos los permisos de Almacén al rol Administrador (id 1)
INSERT INTO `role_permissions` (`role_id`, `permission_id`)
SELECT 1, id FROM `permissions`
WHERE `name` IN ('Administrar Almacén', 'Registrar Entradas Almacén', 'Registrar Salidas Almacén');
```

- [ ] **Step 2: Apply the migration to the local/dev database**

Run (adjust user/db name to your local environment, matching `inc/url_bd.php`):

```bash
mysql -u <db_user> -p <db_name> < almacen_schema.sql
```

Expected: no errors. If `role_permissions` insert returns "0 rows affected" check that the `permissions` insert ran first (run both statements in the same session/transaction).

- [ ] **Step 3: Verify the tables and permissions exist**

```bash
mysql -u <db_user> -p <db_name> -e "SHOW TABLES LIKE 'almacen_%'; SELECT id, name, category FROM permissions WHERE category='Almacén'; SELECT * FROM role_permissions WHERE permission_id IN (SELECT id FROM permissions WHERE category='Almacén');"
```

Expected: 3 tables (`almacen_categorias`, `almacen_elementos`, `almacen_movimientos`), 3 permission rows, and 3 `role_permissions` rows with `role_id = 1`.

- [ ] **Step 4: Commit**

```bash
git add almacen_schema.sql
git commit -m "Add: esquema de BD y permisos para módulo Almacén"
```

---

## Task 2: Categorías page (`admin/almacen/categorias.php`)

**Files:**
- Create: `admin/almacen/categorias.php`

This is a minimal CRUD: list categories, add, edit (rename), soft-delete. Follows the AJAX/DataTables/SweetAlert2 pattern from `admin/products/index.php`.

- [ ] **Step 1: Create the directory and write the file**

Create `admin/almacen/categorias.php`:

```php
<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
$permisopage = 'Administrar Almacén';
include('../login/restriction.php'); ?>
<?php
require_once __DIR__ . '/../../inc/config.php';

// =====================================================
// FETCH: Obtener categorías
// =====================================================
if (isset($_GET['action']) && $_GET['action'] == "fetch") {
    $stmt = db()->prepare("SELECT c.id, c.nombre,
                                   (SELECT COUNT(*) FROM almacen_elementos e WHERE e.categoria_id = c.id AND e.borrado = 0) AS total_elementos
                            FROM almacen_categorias c
                            WHERE c.borrado = 0
                            ORDER BY c.nombre ASC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

// =====================================================
// CRUD: Agregar / Editar / Eliminar
// =====================================================
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == "add") {
        $nombre = trim($_POST['nombre']);

        if ($nombre === '') {
            echo json_encode(['status' => 'error', 'message' => 'El nombre es obligatorio']);
            exit;
        }

        $stmtCheck = db()->prepare("SELECT id, borrado FROM almacen_categorias WHERE nombre = :nombre LIMIT 1");
        $stmtCheck->execute([':nombre' => $nombre]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing && $existing['borrado'] == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ya existe una categoría con ese nombre']);
            exit;
        }

        if ($existing && $existing['borrado'] == 1) {
            $stmtUpdate = db()->prepare("UPDATE almacen_categorias SET borrado = 0 WHERE id = :id");
            $stmtUpdate->execute([':id' => $existing['id']]);

            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Reactivar Categoría Almacén', json_encode(['id' => $existing['id'], 'nombre' => $nombre], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Categoría reactivada correctamente']);
            exit;
        }

        $stmt = db()->prepare("INSERT INTO almacen_categorias (nombre, borrado) VALUES (:nombre, 0)");
        if ($stmt->execute([':nombre' => $nombre])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Agregar Categoría Almacén', json_encode(['nombre' => $nombre], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Categoría agregada correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar la categoría']);
        }
        exit;
    }

    elseif ($action == "edit") {
        $id = trim($_POST['id']);
        $nombre = trim($_POST['nombre']);

        if ($nombre === '') {
            echo json_encode(['status' => 'error', 'message' => 'El nombre es obligatorio']);
            exit;
        }

        $stmt = db()->prepare("UPDATE almacen_categorias SET nombre = :nombre WHERE id = :id");
        if ($stmt->execute([':nombre' => $nombre, ':id' => $id])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Editar Categoría Almacén', json_encode(['id' => $id, 'nombre' => $nombre], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Categoría actualizada correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar la categoría']);
        }
        exit;
    }

    elseif ($action == "delete") {
        $id = trim($_POST['id']);
        $stmt = db()->prepare("UPDATE almacen_categorias SET borrado = 1 WHERE id = :id");
        if ($stmt->execute([':id' => $id])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Borrar Categoría Almacén', json_encode(['id' => $id], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Categoría borrada correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al borrar la categoría']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Categorías de Almacén</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
  <div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
    <div class="portada">
      <h1 class="mb-4">Categorías de Almacén</h1>
      <button class="btn btn-success float-end" id="btnAddCategoria"><i class="fa fa-plus"></i> Agregar Categoría</button>
    </div>
  </div>
  <?php include('../inc/menu.php'); ?>
  <div class="container mt-4">
    <table id="categorias-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Elementos asociados</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <!-- Modal Agregar -->
  <div class="modal fade" id="modalAddCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formAddCategoria">
          <div class="modal-header">
            <h5 class="modal-title">Agregar Categoría</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="add_nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="add_nombre" name="nombre" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Editar -->
  <div class="modal fade" id="modalEditCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formEditCategoria">
          <div class="modal-header">
            <h5 class="modal-title">Editar Categoría</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_id" name="id">
            <div class="mb-3">
              <label for="edit_nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="btnDeleteCategoria"><i class="fa fa-trash-o"></i> Borrar</button>
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php include('../inc/menu-footer.php'); ?>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script>
  $(document).ready(function(){
    var table = $('#categorias-table').DataTable({
      "ajax": "categorias.php?action=fetch",
      "columns": [
        { "data": "nombre" },
        { "data": "total_elementos" }
      ],
      "pageLength": 50,
      "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
    });

    $("#btnAddCategoria").click(function(){
      $("#formAddCategoria")[0].reset();
      $("#modalAddCategoria").modal("show");
    });

    $("#formAddCategoria").on("submit", function(e){
      e.preventDefault();
      $.ajax({
        url: "categorias.php",
        method: "POST",
        data: { action: "add", nombre: $("#add_nombre").val() },
        dataType: "json",
        success: function(response){
          if(response.status === "success"){
            Swal.fire("Éxito", response.message, "success");
            $("#modalAddCategoria").modal("hide");
            table.ajax.reload();
          } else {
            Swal.fire("Error", response.message, "error");
          }
        }
      });
    });

    $('#categorias-table tbody').on('click', 'tr', function(){
      var data = table.row(this).data();
      if(data){
        $("#edit_id").val(data.id);
        $("#edit_nombre").val(data.nombre);
        $("#modalEditCategoria").modal("show");
      }
    });

    $("#formEditCategoria").on("submit", function(e){
      e.preventDefault();
      $.ajax({
        url: "categorias.php",
        method: "POST",
        data: { action: "edit", id: $("#edit_id").val(), nombre: $("#edit_nombre").val() },
        dataType: "json",
        success: function(response){
          if(response.status === "success"){
            Swal.fire("Éxito", response.message, "success");
            $("#modalEditCategoria").modal("hide");
            table.ajax.reload();
          } else {
            Swal.fire("Error", response.message, "error");
          }
        }
      });
    });

    $("#btnDeleteCategoria").on("click", function(){
      var id = $("#edit_id").val();
      Swal.fire({
        title: "¿Está seguro?",
        text: "Esta acción borrará la categoría.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, borrar",
        cancelButtonText: "Cancelar"
      }).then((result) => {
        if(result.isConfirmed){
          $.ajax({
            url: "categorias.php",
            method: "POST",
            data: { action: "delete", id: id },
            dataType: "json",
            success: function(response){
              if(response.status === "success"){
                Swal.fire("Borrado", response.message, "success");
                $("#modalEditCategoria").modal("hide");
                table.ajax.reload();
              } else {
                Swal.fire("Error", response.message, "error");
              }
            }
          });
        }
      });
    });
  });
  </script>
</body>
</html>
```

- [ ] **Step 2: Syntax-check the file**

```bash
php -l "admin/almacen/categorias.php"
```

Expected: `No syntax errors detected in admin/almacen/categorias.php`

- [ ] **Step 3: Commit**

```bash
git add admin/almacen/categorias.php
git commit -m "Add: CRUD de categorías para módulo Almacén"
```

---

## Task 3: Elementos catalog page (`admin/almacen/index.php`)

**Files:**
- Create: `admin/almacen/index.php`

This is the main catalog: list items with category, unit of measure, current/min stock (highlighted when low), add/edit/soft-delete.

- [ ] **Step 1: Write the file**

Create `admin/almacen/index.php`:

```php
<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
$permisopage = 'Administrar Almacén';
include('../login/restriction.php'); ?>
<?php
require_once __DIR__ . '/../../inc/config.php';

// Lista fija de unidades de medida: clave guardada en BD => etiqueta visible
$unidades_medida = [
    'unidad'      => 'Unidad',
    'caja'        => 'Caja',
    'paquete'     => 'Paquete',
    'rollo'       => 'Rollo',
    'litro'       => 'Litro (L)',
    'mililitro'   => 'Mililitro (ml)',
    'galon'       => 'Galón',
    'kilogramo'   => 'Kilogramo (kg)',
    'gramo'       => 'Gramo (g)',
    'metro'       => 'Metro (m)',
    'centimetro'  => 'Centímetro (cm)',
];

// =====================================================
// FETCH: Obtener elementos
// =====================================================
if (isset($_GET['action']) && $_GET['action'] == "fetch") {
    $stmt = db()->prepare("SELECT e.id, e.nombre, e.categoria_id, c.nombre AS categoria_nombre,
                                   e.unidad_medida, e.stock_actual, e.stock_minimo, e.alerta_stock, e.estado
                            FROM almacen_elementos e
                            LEFT JOIN almacen_categorias c ON e.categoria_id = c.id AND c.borrado = 0
                            WHERE e.borrado = 0
                            ORDER BY e.nombre ASC");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

// =====================================================
// CRUD: Agregar / Editar / Eliminar
// =====================================================
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == "add") {
        $nombre = trim($_POST['nombre']);
        $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $unidad_medida = trim($_POST['unidad_medida']);
        $stock_actual = isset($_POST['stock_actual']) && $_POST['stock_actual'] !== '' ? (float)$_POST['stock_actual'] : 0;
        $alerta_stock = (!empty($_POST['alerta_stock']) && $_POST['alerta_stock'] == '1') ? 1 : 0;
        $stock_minimo = $alerta_stock && $_POST['stock_minimo'] !== '' ? (float)$_POST['stock_minimo'] : null;
        $estado = trim($_POST['estado']);

        if ($nombre === '' || $unidad_medida === '' || !array_key_exists($unidad_medida, $unidades_medida)) {
            echo json_encode(['status' => 'error', 'message' => 'Nombre y unidad de medida son obligatorios']);
            exit;
        }

        $stmtCheck = db()->prepare("SELECT id, borrado FROM almacen_elementos WHERE nombre = :nombre LIMIT 1");
        $stmtCheck->execute([':nombre' => $nombre]);
        $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if ($existing && $existing['borrado'] == 0) {
            echo json_encode(['status' => 'error', 'message' => 'Ya existe un elemento con ese nombre']);
            exit;
        }

        if ($existing && $existing['borrado'] == 1) {
            $stmtUpdate = db()->prepare("
                UPDATE almacen_elementos
                SET categoria_id = :categoria_id, unidad_medida = :unidad_medida, stock_actual = :stock_actual,
                    stock_minimo = :stock_minimo, alerta_stock = :alerta_stock, estado = :estado, borrado = 0
                WHERE id = :id
            ");
            $stmtUpdate->execute([
                ':categoria_id' => $categoria_id,
                ':unidad_medida' => $unidad_medida,
                ':stock_actual' => $stock_actual,
                ':stock_minimo' => $stock_minimo,
                ':alerta_stock' => $alerta_stock,
                ':estado' => $estado,
                ':id' => $existing['id']
            ]);

            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Reactivar Elemento Almacén', json_encode(['id' => $existing['id'], 'nombre' => $nombre], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Elemento reactivado y actualizado correctamente']);
            exit;
        }

        $stmt = db()->prepare("
            INSERT INTO almacen_elementos
            (nombre, categoria_id, unidad_medida, stock_actual, stock_minimo, alerta_stock, estado, borrado)
            VALUES
            (:nombre, :categoria_id, :unidad_medida, :stock_actual, :stock_minimo, :alerta_stock, :estado, 0)
        ");
        if ($stmt->execute([
            ':nombre' => $nombre,
            ':categoria_id' => $categoria_id,
            ':unidad_medida' => $unidad_medida,
            ':stock_actual' => $stock_actual,
            ':stock_minimo' => $stock_minimo,
            ':alerta_stock' => $alerta_stock,
            ':estado' => $estado
        ])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Agregar Elemento Almacén', json_encode([
                'nombre' => $nombre, 'categoria_id' => $categoria_id, 'unidad_medida' => $unidad_medida,
                'stock_actual' => $stock_actual, 'stock_minimo' => $stock_minimo, 'alerta_stock' => $alerta_stock, 'estado' => $estado
            ], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Elemento agregado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar el elemento']);
        }
        exit;
    }

    elseif ($action == "edit") {
        $id = trim($_POST['id']);
        $nombre = trim($_POST['nombre']);
        $categoria_id = !empty($_POST['categoria_id']) ? (int)$_POST['categoria_id'] : null;
        $unidad_medida = trim($_POST['unidad_medida']);
        $alerta_stock = (!empty($_POST['alerta_stock']) && $_POST['alerta_stock'] == '1') ? 1 : 0;
        $stock_minimo = $alerta_stock && $_POST['stock_minimo'] !== '' ? (float)$_POST['stock_minimo'] : null;
        $estado = trim($_POST['estado']);

        if ($nombre === '' || $unidad_medida === '' || !array_key_exists($unidad_medida, $unidades_medida)) {
            echo json_encode(['status' => 'error', 'message' => 'Nombre y unidad de medida son obligatorios']);
            exit;
        }

        $stmt = db()->prepare("
            UPDATE almacen_elementos
            SET nombre = :nombre, categoria_id = :categoria_id, unidad_medida = :unidad_medida,
                stock_minimo = :stock_minimo, alerta_stock = :alerta_stock, estado = :estado
            WHERE id = :id
        ");
        if ($stmt->execute([
            ':nombre' => $nombre,
            ':categoria_id' => $categoria_id,
            ':unidad_medida' => $unidad_medida,
            ':stock_minimo' => $stock_minimo,
            ':alerta_stock' => $alerta_stock,
            ':estado' => $estado,
            ':id' => $id
        ])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Editar Elemento Almacén', json_encode([
                'id' => $id, 'nombre' => $nombre, 'categoria_id' => $categoria_id, 'unidad_medida' => $unidad_medida,
                'stock_minimo' => $stock_minimo, 'alerta_stock' => $alerta_stock, 'estado' => $estado
            ], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Elemento actualizado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar el elemento']);
        }
        exit;
    }

    elseif ($action == "delete") {
        $id = trim($_POST['id']);
        $stmt = db()->prepare("UPDATE almacen_elementos SET borrado = 1 WHERE id = :id");
        if ($stmt->execute([':id' => $id])) {
            require_once __DIR__ . '/../inc/log_action.php';
            log_action('Borrar Elemento Almacén', json_encode(['id' => $id], JSON_UNESCAPED_UNICODE), 'Almacén');

            echo json_encode(['status' => 'success', 'message' => 'Elemento borrado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al borrar el elemento']);
        }
        exit;
    }
}

// Categorías activas para los selects
$categorias = db()->query("SELECT id, nombre FROM almacen_categorias WHERE borrado = 0 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Almacén - Elementos</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
  <div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
    <div class="portada">
      <h1 class="mb-4">Almacén - Elementos</h1>
      <button class="btn btn-success float-end" id="btnAddElemento"><i class="fa fa-plus"></i> Agregar Elemento</button>
    </div>
  </div>
  <?php include('../inc/menu.php'); ?>
  <div class="container mt-4">
    <div class="mb-3">
      <label for="filtroCategoria" class="form-label">Filtrar por categoría</label>
      <select id="filtroCategoria" class="form-control" style="max-width:300px;">
        <option value="">Todas</option>
        <?php foreach ($categorias as $c): ?>
          <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <table id="elementos-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Nombre</th>
          <th>Categoría</th>
          <th>Unidad</th>
          <th>Stock actual</th>
          <th>Stock mínimo</th>
          <th>Estado</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <!-- Modal Agregar -->
  <div class="modal fade" id="modalAddElemento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formAddElemento">
          <div class="modal-header">
            <h5 class="modal-title">Agregar Elemento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="add_nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="add_nombre" name="nombre" required>
            </div>
            <div class="mb-3">
              <label for="add_categoria_id" class="form-label">Categoría</label>
              <select class="form-control" id="add_categoria_id" name="categoria_id">
                <option value="">Sin categoría</option>
                <?php foreach ($categorias as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="add_unidad_medida" class="form-label">Unidad de medida</label>
              <select class="form-control" id="add_unidad_medida" name="unidad_medida" required>
                <option value="">Seleccione</option>
                <?php foreach ($unidades_medida as $key => $label): ?>
                  <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="add_stock_actual" class="form-label">Stock inicial</label>
              <input type="number" step="0.01" min="0" class="form-control" id="add_stock_actual" name="stock_actual" value="0" required>
            </div>
            <div class="mb-3">
              <label for="add_estado" class="form-label">Estado</label>
              <select class="form-control" id="add_estado" name="estado" required>
                <option value="1" selected>Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="add_alerta_stock" name="alerta_stock">
              <label class="form-check-label" for="add_alerta_stock">Activar alerta por stock bajo</label>
            </div>
            <div class="mb-3" id="add_minimo_container" style="display:none;">
              <label for="add_stock_minimo" class="form-label">Stock mínimo para alerta</label>
              <input type="number" step="0.01" min="0" class="form-control" id="add_stock_minimo" name="stock_minimo">
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Editar -->
  <div class="modal fade" id="modalEditElemento" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formEditElemento">
          <div class="modal-header">
            <h5 class="modal-title">Editar Elemento</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <input type="hidden" id="edit_id" name="id">
            <div class="mb-3">
              <label for="edit_nombre" class="form-label">Nombre</label>
              <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
            </div>
            <div class="mb-3">
              <label for="edit_categoria_id" class="form-label">Categoría</label>
              <select class="form-control" id="edit_categoria_id" name="categoria_id">
                <option value="">Sin categoría</option>
                <?php foreach ($categorias as $c): ?>
                  <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="edit_unidad_medida" class="form-label">Unidad de medida</label>
              <select class="form-control" id="edit_unidad_medida" name="unidad_medida" required>
                <?php foreach ($unidades_medida as $key => $label): ?>
                  <option value="<?= $key ?>"><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Stock actual</label>
              <input type="text" class="form-control" id="edit_stock_actual_display" disabled>
              <small class="text-muted">El stock se ajusta desde Almacén &gt; Movimientos.</small>
            </div>
            <div class="mb-3">
              <label for="edit_estado" class="form-label">Estado</label>
              <select class="form-control" id="edit_estado" name="estado" required>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="form-check form-switch mb-3">
              <input class="form-check-input" type="checkbox" id="edit_alerta_stock">
              <label class="form-check-label" for="edit_alerta_stock">Activar alerta por stock bajo</label>
            </div>
            <div class="mb-3" id="edit_minimo_container" style="display:none;">
              <label for="edit_stock_minimo" class="form-label">Stock mínimo para alerta</label>
              <input type="number" step="0.01" min="0" class="form-control" id="edit_stock_minimo" name="stock_minimo">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" id="btnDeleteElemento"><i class="fa fa-trash-o"></i> Borrar</button>
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar cambios</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php include('../inc/menu-footer.php'); ?>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script>
  var UNIDADES = <?= json_encode($unidades_medida, JSON_UNESCAPED_UNICODE) ?>;

  $(document).ready(function(){
    var table = $('#elementos-table').DataTable({
      "ajax": "index.php?action=fetch",
      "columns": [
        { "data": "nombre" },
        { "data": "categoria_nombre", "defaultContent": "Sin categoría" },
        { "data": "unidad_medida",
          "render": function(data){ return UNIDADES[data] || data; }
        },
        { "data": "stock_actual" },
        { "data": "stock_minimo", "defaultContent": "-" },
        { "data": "estado",
          "render": function(data){
            return data == 1
              ? '<span class="badge bg-success">Activo</span>'
              : '<span class="badge bg-danger">Inactivo</span>';
          }
        }
      ],
      "createdRow": function(row, data){
        if (data.alerta_stock == 1 && data.stock_minimo !== null && parseFloat(data.stock_actual) <= parseFloat(data.stock_minimo)) {
          $(row).addClass('table-danger');
        }
      },
      "pageLength": 50,
      "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
    });

    $('#filtroCategoria').on('change', function(){
      var val = $(this).val();
      if (val === '') {
        table.column(1).search('').draw();
      } else {
        var nombre = $(this).find('option:selected').text();
        table.column(1).search('^' + $.fn.dataTable.util.escapeRegex(nombre) + '$', true, false).draw();
      }
    });

    $("#btnAddElemento").click(function(){
      $("#formAddElemento")[0].reset();
      $("#add_minimo_container").hide();
      $("#modalAddElemento").modal("show");
    });

    $("#formAddElemento").on("submit", function(e){
      e.preventDefault();
      $.ajax({
        url: "index.php",
        method: "POST",
        data: {
          action: "add",
          nombre: $("#add_nombre").val(),
          categoria_id: $("#add_categoria_id").val(),
          unidad_medida: $("#add_unidad_medida").val(),
          stock_actual: $("#add_stock_actual").val(),
          estado: $("#add_estado").val(),
          alerta_stock: $("#add_alerta_stock").is(":checked") ? 1 : 0,
          stock_minimo: $("#add_alerta_stock").is(":checked") ? $("#add_stock_minimo").val() : ''
        },
        dataType: "json",
        success: function(response){
          if(response.status === "success"){
            Swal.fire("Éxito", response.message, "success");
            $("#modalAddElemento").modal("hide");
            table.ajax.reload();
          } else {
            Swal.fire("Error", response.message, "error");
          }
        }
      });
    });

    $('#elementos-table tbody').on('click', 'tr', function(){
      var data = table.row(this).data();
      if(data){
        $("#edit_id").val(data.id);
        $("#edit_nombre").val(data.nombre);
        $("#edit_categoria_id").val(data.categoria_id || '');
        $("#edit_unidad_medida").val(data.unidad_medida);
        $("#edit_stock_actual_display").val(data.stock_actual + ' ' + (UNIDADES[data.unidad_medida] || data.unidad_medida));
        $("#edit_estado").val(data.estado);

        if (data.alerta_stock == 1) {
          $("#edit_alerta_stock").prop('checked', true);
          $("#edit_minimo_container").show();
          $("#edit_stock_minimo").val(data.stock_minimo);
        } else {
          $("#edit_alerta_stock").prop('checked', false);
          $("#edit_minimo_container").hide();
          $("#edit_stock_minimo").val('');
        }

        $("#modalEditElemento").modal("show");
      }
    });

    $("#formEditElemento").on("submit", function(e){
      e.preventDefault();
      $.ajax({
        url: "index.php",
        method: "POST",
        data: {
          action: "edit",
          id: $("#edit_id").val(),
          nombre: $("#edit_nombre").val(),
          categoria_id: $("#edit_categoria_id").val(),
          unidad_medida: $("#edit_unidad_medida").val(),
          estado: $("#edit_estado").val(),
          alerta_stock: $("#edit_alerta_stock").is(":checked") ? 1 : 0,
          stock_minimo: $("#edit_alerta_stock").is(":checked") ? $("#edit_stock_minimo").val() : ''
        },
        dataType: "json",
        success: function(response){
          if(response.status === "success"){
            Swal.fire("Éxito", response.message, "success");
            $("#modalEditElemento").modal("hide");
            table.ajax.reload();
          } else {
            Swal.fire("Error", response.message, "error");
          }
        }
      });
    });

    $("#btnDeleteElemento").on("click", function(){
      var id = $("#edit_id").val();
      Swal.fire({
        title: "¿Está seguro?",
        text: "Esta acción borrará el elemento.",
        icon: "warning",
        showCancelButton: true,
        confirmButtonText: "Sí, borrar",
        cancelButtonText: "Cancelar"
      }).then((result) => {
        if(result.isConfirmed){
          $.ajax({
            url: "index.php",
            method: "POST",
            data: { action: "delete", id: id },
            dataType: "json",
            success: function(response){
              if(response.status === "success"){
                Swal.fire("Borrado", response.message, "success");
                $("#modalEditElemento").modal("hide");
                table.ajax.reload();
              } else {
                Swal.fire("Error", response.message, "error");
              }
            }
          });
        }
      });
    });

    $("#add_alerta_stock").on("change", function(){ $("#add_minimo_container").toggle(this.checked); });
    $("#edit_alerta_stock").on("change", function(){ $("#edit_minimo_container").toggle(this.checked); });
  });
  </script>
</body>
</html>
```

- [ ] **Step 2: Syntax-check the file**

```bash
php -l "admin/almacen/index.php"
```

Expected: `No syntax errors detected in admin/almacen/index.php`

- [ ] **Step 3: Commit**

```bash
git add admin/almacen/index.php
git commit -m "Add: catálogo de elementos para módulo Almacén"
```

---

## Task 4: Movimientos page (`admin/almacen/movimientos.php`)

**Files:**
- Create: `admin/almacen/movimientos.php`

This page shows the merged kardex (entradas + salidas) with filters, and provides "Registrar Entrada" / "Registrar Salida" buttons gated by their respective permissions. It uses a custom permission check (any of the three Almacén permissions) instead of the single-permission `restriction.php`.

- [ ] **Step 1: Write the file**

Create `admin/almacen/movimientos.php`:

```php
<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
require_once __DIR__ . '/../../inc/config.php';

$permisos = $_SESSION['user_permissions'] ?? [];
$puedeAdministrar = in_array('Administrar Almacén', $permisos);
$puedeEntradas = in_array('Registrar Entradas Almacén', $permisos);
$puedeSalidas = in_array('Registrar Salidas Almacén', $permisos);

// Misma lista fija de unidades de medida usada en index.php (clave BD => etiqueta visible)
$unidades_medida = [
    'unidad'      => 'Unidad',
    'caja'        => 'Caja',
    'paquete'     => 'Paquete',
    'rollo'       => 'Rollo',
    'litro'       => 'Litro (L)',
    'mililitro'   => 'Mililitro (ml)',
    'galon'       => 'Galón',
    'kilogramo'   => 'Kilogramo (kg)',
    'gramo'       => 'Gramo (g)',
    'metro'       => 'Metro (m)',
    'centimetro'  => 'Centímetro (cm)',
];

if (!$puedeAdministrar && !$puedeEntradas && !$puedeSalidas) {
    $_SESSION['error'] = "<center>No tiene permisos para ver esta página.<br> Permiso necesario:<strong> 'Administrar Almacén', 'Registrar Entradas Almacén' o 'Registrar Salidas Almacén'</strong><center>";
    header("Location: $url/admin/");
    exit();
}

// =====================================================
// FETCH: Obtener movimientos (con filtros)
// =====================================================
if (isset($_GET['action']) && $_GET['action'] == "fetch") {
    $where = ["1=1"];
    $params = [];

    if (!empty($_GET['elemento_id'])) {
        $where[] = "m.elemento_id = :elemento_id";
        $params[':elemento_id'] = (int)$_GET['elemento_id'];
    }
    if (!empty($_GET['tipo']) && in_array($_GET['tipo'], ['entrada', 'salida'])) {
        $where[] = "m.tipo = :tipo";
        $params[':tipo'] = $_GET['tipo'];
    }
    if (!empty($_GET['fecha_inicio'])) {
        $where[] = "DATE(m.created_at) >= :fecha_inicio";
        $params[':fecha_inicio'] = $_GET['fecha_inicio'];
    }
    if (!empty($_GET['fecha_fin'])) {
        $where[] = "DATE(m.created_at) <= :fecha_fin";
        $params[':fecha_fin'] = $_GET['fecha_fin'];
    }
    if (!empty($_GET['usuario_id'])) {
        $where[] = "m.usuario_id = :usuario_id";
        $params[':usuario_id'] = (int)$_GET['usuario_id'];
    }

    $sql = "SELECT m.id, m.elemento_id, e.nombre AS elemento_nombre, e.unidad_medida,
                   m.tipo, m.cantidad, m.proveedor, m.observacion, m.usuario_id,
                   TRIM(CONCAT(u.nombre, ' ', u.apellido)) AS usuario_nombre, m.created_at
            FROM almacen_movimientos m
            JOIN almacen_elementos e ON m.elemento_id = e.id
            LEFT JOIN usuarios u ON m.usuario_id = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY m.created_at DESC";

    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

// =====================================================
// REGISTRAR ENTRADA
// =====================================================
if (isset($_POST['action']) && $_POST['action'] == "entrada") {
    if (!$puedeEntradas) {
        echo json_encode(['status' => 'error', 'message' => 'No tiene permiso para registrar entradas']);
        exit;
    }

    $elemento_id = (int)$_POST['elemento_id'];
    $cantidad = (float)$_POST['cantidad'];
    $proveedor = trim($_POST['proveedor'] ?? '');
    $observacion = trim($_POST['observacion'] ?? '');
    $usuario_id = $_SESSION['user']['id'] ?? null;

    if ($elemento_id <= 0 || $cantidad <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Elemento y cantidad (mayor a 0) son obligatorios']);
        exit;
    }

    $stmtCheck = db()->prepare("SELECT id FROM almacen_elementos WHERE id = :id AND borrado = 0");
    $stmtCheck->execute([':id' => $elemento_id]);
    if (!$stmtCheck->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'El elemento seleccionado no existe']);
        exit;
    }

    db()->beginTransaction();
    try {
        $stmt = db()->prepare("
            INSERT INTO almacen_movimientos (elemento_id, tipo, cantidad, proveedor, observacion, usuario_id)
            VALUES (:elemento_id, 'entrada', :cantidad, :proveedor, :observacion, :usuario_id)
        ");
        $stmt->execute([
            ':elemento_id' => $elemento_id,
            ':cantidad' => $cantidad,
            ':proveedor' => $proveedor !== '' ? $proveedor : null,
            ':observacion' => $observacion !== '' ? $observacion : null,
            ':usuario_id' => $usuario_id
        ]);

        $stmtUpd = db()->prepare("UPDATE almacen_elementos SET stock_actual = stock_actual + :cantidad WHERE id = :id");
        $stmtUpd->execute([':cantidad' => $cantidad, ':id' => $elemento_id]);

        db()->commit();

        require_once __DIR__ . '/../inc/log_action.php';
        log_action('Registrar Entrada Almacén', json_encode([
            'elemento_id' => $elemento_id, 'cantidad' => $cantidad, 'proveedor' => $proveedor, 'observacion' => $observacion
        ], JSON_UNESCAPED_UNICODE), 'Almacén');

        echo json_encode(['status' => 'success', 'message' => 'Entrada registrada correctamente']);
    } catch (Exception $ex) {
        db()->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar la entrada']);
    }
    exit;
}

// =====================================================
// REGISTRAR SALIDA
// =====================================================
if (isset($_POST['action']) && $_POST['action'] == "salida") {
    if (!$puedeSalidas) {
        echo json_encode(['status' => 'error', 'message' => 'No tiene permiso para registrar salidas']);
        exit;
    }

    $elemento_id = (int)$_POST['elemento_id'];
    $cantidad = (float)$_POST['cantidad'];
    $observacion = trim($_POST['observacion'] ?? '');
    $usuario_id = $_SESSION['user']['id'] ?? null;

    if ($elemento_id <= 0 || $cantidad <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Elemento y cantidad (mayor a 0) son obligatorios']);
        exit;
    }

    $stmtCheck = db()->prepare("SELECT id, stock_actual FROM almacen_elementos WHERE id = :id AND borrado = 0");
    $stmtCheck->execute([':id' => $elemento_id]);
    $elemento = $stmtCheck->fetch(PDO::FETCH_ASSOC);

    if (!$elemento) {
        echo json_encode(['status' => 'error', 'message' => 'El elemento seleccionado no existe']);
        exit;
    }

    if ($cantidad > (float)$elemento['stock_actual']) {
        echo json_encode(['status' => 'error', 'message' => 'No hay suficiente stock disponible para esta salida']);
        exit;
    }

    db()->beginTransaction();
    try {
        $stmt = db()->prepare("
            INSERT INTO almacen_movimientos (elemento_id, tipo, cantidad, proveedor, observacion, usuario_id)
            VALUES (:elemento_id, 'salida', :cantidad, NULL, :observacion, :usuario_id)
        ");
        $stmt->execute([
            ':elemento_id' => $elemento_id,
            ':cantidad' => $cantidad,
            ':observacion' => $observacion !== '' ? $observacion : null,
            ':usuario_id' => $usuario_id
        ]);

        $stmtUpd = db()->prepare("UPDATE almacen_elementos SET stock_actual = stock_actual - :cantidad WHERE id = :id");
        $stmtUpd->execute([':cantidad' => $cantidad, ':id' => $elemento_id]);

        db()->commit();

        require_once __DIR__ . '/../inc/log_action.php';
        log_action('Registrar Salida Almacén', json_encode([
            'elemento_id' => $elemento_id, 'cantidad' => $cantidad, 'observacion' => $observacion
        ], JSON_UNESCAPED_UNICODE), 'Almacén');

        echo json_encode(['status' => 'success', 'message' => 'Salida registrada correctamente']);
    } catch (Exception $ex) {
        db()->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Error al registrar la salida']);
    }
    exit;
}

// Elementos activos para los selects de los modales
$elementos = db()->query("SELECT id, nombre, unidad_medida, stock_actual FROM almacen_elementos WHERE borrado = 0 AND estado = 1 ORDER BY nombre ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Almacén - Movimientos</title>
  <?php include('../inc/header.php'); ?>
</head>
<body>
  <div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
    <div class="portada">
      <h1 class="mb-4">Almacén - Movimientos</h1>
      <?php if ($puedeEntradas): ?>
        <button class="btn btn-success float-end ms-2" id="btnEntrada"><i class="fa fa-arrow-down"></i> Registrar Entrada</button>
      <?php endif; ?>
      <?php if ($puedeSalidas): ?>
        <button class="btn btn-warning float-end" id="btnSalida"><i class="fa fa-arrow-up"></i> Registrar Salida</button>
      <?php endif; ?>
    </div>
  </div>
  <?php include('../inc/menu.php'); ?>
  <div class="container mt-4">
    <div class="row mb-3">
      <div class="col-md-3">
        <label for="filtro_elemento" class="form-label">Elemento</label>
        <select id="filtro_elemento" class="form-control">
          <option value="">Todos</option>
          <?php foreach ($elementos as $e): ?>
            <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label for="filtro_tipo" class="form-label">Tipo</label>
        <select id="filtro_tipo" class="form-control">
          <option value="">Todos</option>
          <option value="entrada">Entrada</option>
          <option value="salida">Salida</option>
        </select>
      </div>
      <div class="col-md-2">
        <label for="filtro_fecha_inicio" class="form-label">Desde</label>
        <input type="date" id="filtro_fecha_inicio" class="form-control">
      </div>
      <div class="col-md-2">
        <label for="filtro_fecha_fin" class="form-label">Hasta</label>
        <input type="date" id="filtro_fecha_fin" class="form-control">
      </div>
      <div class="col-md-3 d-flex align-items-end">
        <button id="btnFiltrar" class="btn btn-primary me-2"><i class="fa fa-filter"></i> Filtrar</button>
        <button id="btnLimpiarFiltro" class="btn btn-secondary"><i class="fa fa-times"></i> Limpiar</button>
      </div>
    </div>

    <table id="movimientos-table" class="table table-striped table-bordered">
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Elemento</th>
          <th>Tipo</th>
          <th>Cantidad</th>
          <th>Proveedor</th>
          <th>Observación</th>
          <th>Usuario</th>
        </tr>
      </thead>
      <tbody></tbody>
    </table>
  </div>

  <!-- Modal Registrar Entrada -->
  <div class="modal fade" id="modalEntrada" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formEntrada">
          <div class="modal-header">
            <h5 class="modal-title">Registrar Entrada</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="entrada_elemento_id" class="form-label">Elemento</label>
              <select class="form-control" id="entrada_elemento_id" name="elemento_id" required>
                <option value="">Seleccione</option>
                <?php foreach ($elementos as $e): ?>
                  <option value="<?= $e['id'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= htmlspecialchars($unidades_medida[$e['unidad_medida']] ?? $e['unidad_medida']) ?>)</option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="entrada_cantidad" class="form-label">Cantidad</label>
              <input type="number" step="0.01" min="0.01" class="form-control" id="entrada_cantidad" name="cantidad" required>
            </div>
            <div class="mb-3">
              <label for="entrada_proveedor" class="form-label">Proveedor (opcional)</label>
              <input type="text" class="form-control" id="entrada_proveedor" name="proveedor">
            </div>
            <div class="mb-3">
              <label for="entrada_observacion" class="form-label">Observación (opcional)</label>
              <textarea class="form-control" id="entrada_observacion" name="observacion" rows="2"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal Registrar Salida -->
  <div class="modal fade" id="modalSalida" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <form id="formSalida">
          <div class="modal-header">
            <h5 class="modal-title">Registrar Salida</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="mb-3">
              <label for="salida_elemento_id" class="form-label">Elemento</label>
              <select class="form-control" id="salida_elemento_id" name="elemento_id" required>
                <option value="">Seleccione</option>
                <?php foreach ($elementos as $e): ?>
                  <option value="<?= $e['id'] ?>" data-stock="<?= $e['stock_actual'] ?>"><?= htmlspecialchars($e['nombre']) ?> (<?= htmlspecialchars($unidades_medida[$e['unidad_medida']] ?? $e['unidad_medida']) ?>) - Disponible: <?= $e['stock_actual'] ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="mb-3">
              <label for="salida_cantidad" class="form-label">Cantidad</label>
              <input type="number" step="0.01" min="0.01" class="form-control" id="salida_cantidad" name="cantidad" required>
            </div>
            <div class="mb-3">
              <label for="salida_observacion" class="form-label">Observación / motivo</label>
              <textarea class="form-control" id="salida_observacion" name="observacion" rows="2"></textarea>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          </div>
        </form>
      </div>
    </div>
  </div>

<?php include('../inc/menu-footer.php'); ?>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
  <script>
  var UNIDADES = <?= json_encode($unidades_medida, JSON_UNESCAPED_UNICODE) ?>;

  $(document).ready(function(){
    function buildAjaxUrl(){
      var params = $.param({
        action: 'fetch',
        elemento_id: $('#filtro_elemento').val(),
        tipo: $('#filtro_tipo').val(),
        fecha_inicio: $('#filtro_fecha_inicio').val(),
        fecha_fin: $('#filtro_fecha_fin').val()
      });
      return 'movimientos.php?' + params;
    }

    var table = $('#movimientos-table').DataTable({
      "ajax": buildAjaxUrl(),
      "columns": [
        { "data": "created_at" },
        { "data": "elemento_nombre" },
        { "data": "tipo",
          "render": function(data){
            return data === 'entrada'
              ? '<span class="badge bg-success">Entrada</span>'
              : '<span class="badge bg-warning text-dark">Salida</span>';
          }
        },
        { "data": null,
          "render": function(data, type, row){ return row.cantidad + ' ' + (UNIDADES[row.unidad_medida] || row.unidad_medida); }
        },
        { "data": "proveedor", "defaultContent": "-" },
        { "data": "observacion", "defaultContent": "-" },
        { "data": "usuario_nombre", "defaultContent": "-" }
      ],
      "order": [[0, 'desc']],
      "pageLength": 50,
      "language": { "url": "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
    });

    $('#btnFiltrar').on('click', function(){
      table.ajax.url(buildAjaxUrl()).load();
    });

    $('#btnLimpiarFiltro').on('click', function(){
      $('#filtro_elemento, #filtro_tipo').val('');
      $('#filtro_fecha_inicio, #filtro_fecha_fin').val('');
      table.ajax.url(buildAjaxUrl()).load();
    });

    $('#btnEntrada').on('click', function(){
      $('#formEntrada')[0].reset();
      $('#modalEntrada').modal('show');
    });

    $('#formEntrada').on('submit', function(e){
      e.preventDefault();
      $.ajax({
        url: 'movimientos.php',
        method: 'POST',
        data: {
          action: 'entrada',
          elemento_id: $('#entrada_elemento_id').val(),
          cantidad: $('#entrada_cantidad').val(),
          proveedor: $('#entrada_proveedor').val(),
          observacion: $('#entrada_observacion').val()
        },
        dataType: 'json',
        success: function(response){
          if(response.status === 'success'){
            Swal.fire('Éxito', response.message, 'success');
            $('#modalEntrada').modal('hide');
            table.ajax.url(buildAjaxUrl()).load();
          } else {
            Swal.fire('Error', response.message, 'error');
          }
        }
      });
    });

    $('#btnSalida').on('click', function(){
      $('#formSalida')[0].reset();
      $('#modalSalida').modal('show');
    });

    $('#formSalida').on('submit', function(e){
      e.preventDefault();
      $.ajax({
        url: 'movimientos.php',
        method: 'POST',
        data: {
          action: 'salida',
          elemento_id: $('#salida_elemento_id').val(),
          cantidad: $('#salida_cantidad').val(),
          observacion: $('#salida_observacion').val()
        },
        dataType: 'json',
        success: function(response){
          if(response.status === 'success'){
            Swal.fire('Éxito', response.message, 'success');
            $('#modalSalida').modal('hide');
            table.ajax.url(buildAjaxUrl()).load();
          } else {
            Swal.fire('Error', response.message, 'error');
          }
        }
      });
    });
  });
  </script>
</body>
</html>
```

- [ ] **Step 2: Syntax-check the file**

```bash
php -l "admin/almacen/movimientos.php"
```

Expected: `No syntax errors detected in admin/almacen/movimientos.php`

- [ ] **Step 3: Commit**

```bash
git add admin/almacen/movimientos.php
git commit -m "Add: kardex de movimientos (entradas/salidas) para módulo Almacén"
```

---

## Task 5: Sidebar menu integration

**Files:**
- Modify: `admin/inc/menu.php:151-152` (right after the Productos submenu closes, before the Nómina section)

- [ ] **Step 1: Add the Almacén section to the menu**

In `admin/inc/menu.php`, locate the end of the Productos block:

```php
    <div class="sg-submenu"><div>
      <a href="<?= $url ?>/admin/products" class="sg-subitem" onclick="sgCloseSidebar()">Stock</a>
      <?php if (in_array('Ver y Editar Bolsillos', $_SESSION["user_permissions"])): ?>
        <a href="<?= $url ?>/admin/products/pocket.php" class="sg-subitem" onclick="sgCloseSidebar()">Bolsillos</a>
      <?php endif; ?>
    </div></div>
    <?php endif; ?>
```

Immediately after that `<?php endif; ?>` (still before the "Operaciones" → Nómina block), insert a new dedicated "Almacén" section:

```php

    <?php if (isset($_SESSION["user_permissions"]) && (
        in_array('Administrar Almacén', $_SESSION["user_permissions"]) ||
        in_array('Registrar Entradas Almacén', $_SESSION["user_permissions"]) ||
        in_array('Registrar Salidas Almacén', $_SESSION["user_permissions"])
    )): ?>
    <div class="sg-divider"></div>
    <span class="sg-section-label">Almacén</span>

    <?php if (in_array('Administrar Almacén', $_SESSION["user_permissions"])): ?>
      <a href="<?= $url ?>/admin/almacen/" class="sg-item" onclick="sgCloseSidebar()">
        <span class="sg-icon"><i class="fas fa-box"></i></span>
        <span class="sg-label">Elementos</span>
      </a>
      <a href="<?= $url ?>/admin/almacen/categorias.php" class="sg-item" onclick="sgCloseSidebar()">
        <span class="sg-icon"><i class="fas fa-tags"></i></span>
        <span class="sg-label">Categorías</span>
      </a>
    <?php endif; ?>
    <a href="<?= $url ?>/admin/almacen/movimientos.php" class="sg-item" onclick="sgCloseSidebar()">
      <span class="sg-icon"><i class="fas fa-exchange-alt"></i></span>
      <span class="sg-label">Movimientos</span>
    </a>
    <?php endif; ?>
```

- [ ] **Step 2: Syntax-check the file**

```bash
php -l "admin/inc/menu.php"
```

Expected: `No syntax errors detected in admin/inc/menu.php`

- [ ] **Step 3: Commit**

```bash
git add admin/inc/menu.php
git commit -m "Add: sección Almacén al menú lateral"
```

---

## Task 6: End-to-end manual verification

**Files:** none (manual QA pass)

- [ ] **Step 1: Start the dev server**

```bash
php -S localhost:8000
```

- [ ] **Step 2: Verify menu visibility**

Log in as an Administrador user (role_id = 1, which got all 3 new permissions in Task 1). Confirm the sidebar shows a new "Almacén" section with **Elementos**, **Categorías**, and **Movimientos** links.

- [ ] **Step 3: Categorías flow**

Go to `Almacén > Categorías`:
- Add a category, e.g. "Aseo".
- Add a second category, e.g. "Cafetería".
- Edit "Cafetería" → rename to "Cafetería y Cocina", confirm the table updates.
- Confirm both rows show `0` in "Elementos asociados".

- [ ] **Step 4: Elementos flow**

Go to `Almacén > Elementos`:
- Add an element: nombre "Papel higiénico", categoría "Aseo", unidad "Rollo", stock inicial `20`, sin alerta. Confirm it appears in the table with category "Aseo" and unit "Rollo".
- Add a second element: nombre "Café molido", categoría "Cafetería y Cocina", unidad "Kilogramo (kg)", stock inicial `2`, activar alerta con stock mínimo `1`. Confirm it appears, row not highlighted (2 > 1).
- Use the category filter dropdown to filter by "Aseo" — confirm only "Papel higiénico" shows.
- Edit "Papel higiénico" → change estado to "Inactivo", save, confirm badge changes to "Inactivo".

- [ ] **Step 5: Movimientos — entrada**

Go to `Almacén > Movimientos`:
- Click "Registrar Entrada", select "Café molido", cantidad `5`, proveedor "Distribuidora XYZ", guardar.
- Confirm a new row appears with tipo "Entrada", cantidad "5 Kilogramo (kg)", proveedor "Distribuidora XYZ".
- Go back to `Almacén > Elementos` and confirm "Café molido" stock_actual is now `7`.

- [ ] **Step 6: Movimientos — salida con stock suficiente**

Back in `Almacén > Movimientos`:
- Click "Registrar Salida", select "Café molido", cantidad `6`, observación "Consumo cafetería semana 1", guardar.
- Confirm a new row appears with tipo "Salida", cantidad "6 Kilogramo (kg)", observación visible.
- Go back to `Almacén > Elementos` and confirm "Café molido" stock_actual is now `1`. Confirm the row IS highlighted in red (1 <= stock_minimo of 1, alerta activa).

- [ ] **Step 7: Movimientos — salida con stock insuficiente (validación)**

Back in `Almacén > Movimientos`:
- Click "Registrar Salida", select "Café molido", cantidad `100`, guardar.
- Confirm an error alert appears ("No hay suficiente stock disponible...") and no new row is added, and stock_actual remains `1`.

- [ ] **Step 8: Filtros de movimientos**

In `Almacén > Movimientos`:
- Filter by Tipo = "Entrada" → confirm only the entrada row for "Café molido" shows.
- Filter by Elemento = "Papel higiénico" → confirm the table is empty (no movements yet for that item).
- Click "Limpiar" → confirm both movement rows show again.

- [ ] **Step 9: Permission scoping (optional but recommended)**

If you have a second test user/role without the Almacén permissions:
- Confirm the "Almacén" section does not appear in their sidebar.
- Confirm direct navigation to `/admin/almacen/`, `/admin/almacen/categorias.php`, and `/admin/almacen/movimientos.php` redirects to `/admin/` with a permission error message.

If you have a role with only `Registrar Salidas Almacén` (not `Administrar Almacén` or entradas):
- Confirm `Almacén > Movimientos` is reachable but `Almacén > Elementos`/`Categorías` are not in the menu and not directly reachable.
- Confirm only the "Registrar Salida" button shows on the Movimientos page (no "Registrar Entrada" button).

- [ ] **Step 10: Check system logs**

Go to `Sistema > Logs del Sistema` (requires `Ver Logs del Sistema` permission) and confirm entries exist for: "Agregar Categoría Almacén", "Agregar Elemento Almacén", "Editar Elemento Almacén", "Registrar Entrada Almacén", "Registrar Salida Almacén".

---

## Spec coverage check

- Categorías CRUD with soft delete → Task 2 ✅
- Elementos catálogo: nombre, categoría, unidad de medida (lista fija), stock_actual, stock_minimo, alerta_stock, estado, soft delete → Task 3 ✅
- Stock bajo resaltado visualmente → Task 3 (DataTables `createdRow` + `table-danger`) ✅
- Kardex unificado con filtros (elemento, tipo, fechas, usuario via query param support) → Task 4 ✅
- Registrar Entrada (cantidad, proveedor, observación) actualizando stock → Task 4 ✅
- Registrar Salida (cantidad validada contra stock, observación) actualizando stock → Task 4 ✅
- 3 permisos nuevos (Administrar / Entradas / Salidas) asignados al rol Administrador → Task 1 ✅
- Sección "Almacén" propia en el menú lateral → Task 5 ✅
- Logging vía `log_action()` para todas las operaciones → Tasks 2-4 ✅
