<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
$permisopage = 'Gestionar Roles';
include('../login/restriction.php'); ?>
<?php
require_once __DIR__ . '/../../inc/config.php';

// Asegurar que el permiso "Aplicar Descuentos en Caja" exista
try {
    $stmtChk = db()->prepare("SELECT id FROM permissions WHERE name = 'Aplicar Descuentos en Caja'");
    $stmtChk->execute();
    if (!$stmtChk->fetch()) {
        db()->exec("INSERT INTO permissions (name, category) VALUES ('Aplicar Descuentos en Caja', 'CAJA')");
    }
} catch (Exception $e) { /* ignore */ }

// Obtener permisos agrupados por categoría
$permissionsGrouped = [];
try {
    $stmtPermissions = db()->query("SELECT id, name, category FROM permissions ORDER BY category ASC, name ASC");
    foreach ($stmtPermissions->fetchAll(PDO::FETCH_ASSOC) as $perm) {
        $permissionsGrouped[$perm['category']][] = $perm;
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Roles</title>
  <?php include('../inc/header.php'); ?>
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.4/css/dataTables.bootstrap5.min.css">
  <style>
    .cat-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 10px 14px;
      cursor: pointer;
      background: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 6px;
      margin-bottom: 4px;
      font-weight: 600;
      font-size: 14px;
      user-select: none;
      transition: background 0.15s;
    }
    .cat-header:hover { background: #e9ecef; }
    .cat-header.open { background: var(--system-color-primary, #0d6efd); color: #fff; border-color: var(--system-color-primary, #0d6efd); }
    .cat-header.open .badge-perm { background: rgba(255,255,255,0.3) !important; color:#fff; }
    .cat-header .arrow { transition: transform 0.2s; font-style:normal; }
    .cat-header.open .arrow { transform: rotate(90deg); }
    .cat-body {
      display: none;
      border: 1px solid #dee2e6;
      border-top: none;
      border-radius: 0 0 6px 6px;
      padding: 10px 16px 12px;
      margin-bottom: 8px;
      margin-top: -4px;
    }
    .form-check.form-switch { margin-bottom: 5px; }
    .badge-perm { font-size: 11px; }
  </style>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1 class="mb-4">Gestión de Roles</h1>
    <button class="btn btn-success float-end" id="btnAddRole"><i class="fa fa-plus"></i> Agregar Nuevo Rol</button>
  </div>
</div>
<?php include('../inc/menu.php'); ?>
<div class="container mt-4">
  <table id="roles-table" class="table table-striped table-bordered">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Descripción</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</div>

<!-- Modal Agregar/Editar Rol -->
<div class="modal fade" id="modalAddRole" tabindex="-1" aria-labelledby="modalAddRoleLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form id="formAddRole">
        <div class="modal-header">
          <h5 class="modal-title" id="modalAddRoleLabel">Agregar / Editar Rol</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>

        <div class="modal-body" style="max-height:78vh; overflow-y:auto">
          <input type="hidden" id="edit_role_id" name="id">

          <div class="row mb-3">
            <div class="col-md-5">
              <label for="role_name" class="form-label">Nombre del Rol</label>
              <input type="text" class="form-control" id="role_name" name="name" required>
            </div>
            <div class="col-md-7">
              <label for="role_description" class="form-label">Descripción</label>
              <textarea class="form-control" id="role_description" name="description" rows="1"></textarea>
            </div>
          </div>

          <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="mb-0">Permisos</h6>
            <div class="d-flex gap-2">
              <a href="#" id="btnExpandAll" class="small text-primary">Expandir todo</a>
              <span class="text-muted small">|</span>
              <a href="#" id="btnCollapseAll" class="small text-secondary">Colapsar todo</a>
            </div>
          </div>

          <div id="permisosContainer">
            <?php foreach ($permissionsGrouped as $category => $perms):
              $catId = 'cat_' . preg_replace('/[^a-zA-Z0-9]/', '_', strtolower($category));
            ?>
            <div class="cat-header" data-target="<?= $catId ?>">
              <span>
                <i class="arrow">&#9658;</i>
                <?= htmlspecialchars($category) ?>
              </span>
              <span class="badge bg-secondary badge-perm ms-2" id="badge_<?= $catId ?>">0/<?= count($perms) ?></span>
            </div>
            <div class="cat-body" id="<?= $catId ?>">
              <div class="d-flex justify-content-end mb-2 gap-3">
                <a href="#" class="btn-select-cat small" data-cat="<?= $catId ?>">Marcar todos</a>
                <a href="#" class="btn-clear-cat small text-secondary" data-cat="<?= $catId ?>">Desmarcar todos</a>
              </div>
              <?php foreach ($perms as $perm): ?>
              <div class="form-check form-switch">
                <input class="form-check-input perm-<?= $catId ?>" type="checkbox"
                       name="permissions[]"
                       value="<?= $perm['id'] ?>"
                       id="permission_<?= $perm['id'] ?>">
                <label class="form-check-label" for="permission_<?= $perm['id'] ?>"><?= htmlspecialchars($perm['name']) ?></label>
              </div>
              <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
          </div>
        </div><!-- /modal-body -->

        <div class="modal-footer">
          <button type="button" class="btn btn-danger" id="btnDeleteRole"><i class="fa fa-trash"></i> Borrar Rol</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include('../inc/menu-footer.php'); ?>
<script>
$(document).ready(function () {

  // ── DataTable ──────────────────────────────────────────────
  var table = $('#roles-table').DataTable({
    ajax: "get_roles.php?action=fetch",
    columns: [
      { data: "name" },
      { data: "description" }
    ],
    language: { url: "//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json" }
  });

  // ── Toggle de cada categoría (jQuery puro) ─────────────────
  $(document).on('click', '.cat-header', function () {
    var targetId = $(this).data('target');
    var $body = $('#' + targetId);
    if ($body.is(':visible')) {
      $body.slideUp(180);
      $(this).removeClass('open');
    } else {
      $body.slideDown(180);
      $(this).addClass('open');
    }
  });

  // ── Actualiza los badges de cada categoría ─────────────────
  function updateBadges() {
    $('.cat-body').each(function () {
      var catId = this.id;
      var total   = $(this).find('input[type=checkbox]').length;
      var checked = $(this).find('input[type=checkbox]:checked').length;
      var $badge  = $('#badge_' + catId);
      $badge.text(checked + '/' + total);
      if (checked > 0) {
        $badge.removeClass('bg-secondary').addClass('bg-success');
      } else {
        $badge.removeClass('bg-success').addClass('bg-secondary');
      }
    });
  }

  $(document).on('change', '.form-check-input', updateBadges);

  // ── Marcar / desmarcar todos de una categoría ──────────────
  $(document).on('click', '.btn-select-cat', function (e) {
    e.preventDefault();
    $('#' + $(this).data('cat')).find('input[type=checkbox]').prop('checked', true);
    updateBadges();
  });
  $(document).on('click', '.btn-clear-cat', function (e) {
    e.preventDefault();
    $('#' + $(this).data('cat')).find('input[type=checkbox]').prop('checked', false);
    updateBadges();
  });

  // ── Expandir / colapsar todo ───────────────────────────────
  $('#btnExpandAll').on('click', function (e) {
    e.preventDefault();
    $('.cat-body').slideDown(180);
    $('.cat-header').addClass('open');
  });
  $('#btnCollapseAll').on('click', function (e) {
    e.preventDefault();
    $('.cat-body').slideUp(180);
    $('.cat-header').removeClass('open');
  });

  // ── Nuevo rol ──────────────────────────────────────────────
  $('#btnAddRole').click(function () {
    $('#formAddRole')[0].reset();
    $('#edit_role_id').val('');
    $('.form-check-input').prop('checked', false);
    $('.cat-body').hide();
    $('.cat-header').removeClass('open');
    updateBadges();
    $('#modalAddRole').modal('show');
  });

  // ── Guardar rol ────────────────────────────────────────────
  $('#formAddRole').on('submit', function (e) {
    e.preventDefault();
    const roleId = $('#edit_role_id').val();
    $.ajax({
      url: 'get_roles.php',
      method: 'POST',
      data: {
        action: roleId ? 'edit' : 'add',
        id: roleId,
        name: $('#role_name').val(),
        description: $('#role_description').val(),
        permissions: $("input[name='permissions[]']:checked").map(function () { return $(this).val(); }).get()
      },
      dataType: 'json',
      success: function (res) {
        if (res.status === 'success') {
          Swal.fire('Éxito', res.message, 'success');
          $('#modalAddRole').modal('hide');
          table.ajax.reload();
        } else {
          Swal.fire('Error', res.message, 'error');
        }
      }
    });
  });

  // ── Borrar rol ─────────────────────────────────────────────
  $('#btnDeleteRole').on('click', function () {
    const roleId = $('#edit_role_id').val();
    if (!roleId) { Swal.fire('Error', 'No se encontró el ID del rol', 'error'); return; }
    Swal.fire({
      title: '¿Está seguro?',
      text: 'Este rol se marcará como borrado.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      confirmButtonText: 'Sí, borrar'
    }).then(function (result) {
      if (result.isConfirmed) {
        $.ajax({
          url: 'get_roles.php',
          method: 'POST',
          data: { action: 'delete', id: roleId },
          dataType: 'json',
          success: function (res) {
            if (res.status === 'success') {
              Swal.fire('Borrado', res.message, 'success');
              $('#modalAddRole').modal('hide');
              table.ajax.reload();
            } else {
              Swal.fire('Error', res.message, 'error');
            }
          }
        });
      }
    });
  });

  // ── Editar rol (clic en fila) ──────────────────────────────
  $('#roles-table tbody').on('click', 'tr', function () {
    var data = table.row(this).data();
    if (!data) return;

    $('#edit_role_id').val(data.id);
    $('#role_name').val(data.name);
    $('#role_description').val(data.description);
    $('.form-check-input').prop('checked', false);
    $('.cat-body').hide();
    $('.cat-header').removeClass('open');

    $.ajax({
      url: 'get_roles.php',
      method: 'POST',
      data: { action: 'get', id: data.id },
      dataType: 'json',
      success: function (res) {
        if (res.status === 'success') {
          res.data.permissions.forEach(function (permId) {
            $('#permission_' + permId).prop('checked', true);
          });
          updateBadges();
          // Abrir solo las categorías con permisos marcados
          $('.cat-body').each(function () {
            if ($(this).find('input:checked').length > 0) {
              $(this).show();
              $('[data-target="' + this.id + '"]').addClass('open');
            }
          });
          $('#modalAddRole').modal('show');
        } else {
          Swal.fire('Error', res.message, 'error');
        }
      }
    });
  });

});
</script>
</body>
</html>
