<?php require_once __DIR__ . '/../login/session.php'; ?>
<?php
// Permisos (ajustar según tu sistema)
$permisopage = 'Ver Egresos';
include('../login/restriction.php');
?>
<?php
require_once __DIR__ . '/../../inc/config.php';

// Si $hoy no está en config.php, lo creamos aquí
if (!isset($hoy)) {
    $hoy = date('Y-m-d');
}
// Manejo de AJAX para carga de datos
if (isset($_GET['action']) && $_GET['action'] == "fetch") {
    $stmt = db()->query("SELECT id, fecha, descripcion, valor FROM egresos WHERE borrado = 0 ORDER BY fecha DESC");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['data' => $data]);
    exit;
}

// Manejo de acciones POST
if (isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action == "add") {
        $descripcion = trim($_POST['descripcion']);
        $valor = trim($_POST['valor']);

        if (empty($descripcion) || empty($valor) || !is_numeric($valor)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
            exit;
        }

        // ✅ Sin validación de duplicados
        $stmt = db()->prepare("INSERT INTO egresos (fecha, descripcion, valor, borrado) VALUES (:fecha, :descripcion, :valor, 0)");
        if ($stmt->execute([':fecha' => $hoy, ':descripcion' => $descripcion, ':valor' => $valor])) {
			
					// LOGS
		require_once __DIR__ . '/../inc/log_action.php';
		$desc = json_encode([
					
					'descripcion' => $descripcion, 
					'valor' => $valor
		], JSON_UNESCAPED_UNICODE);
		log_action('Registrar Egreso', $desc, 'Contabilidad');
		// END LOGS

            echo json_encode(['status' => 'success', 'message' => 'Egreso agregado correctamente']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al agregar']);
        }
        exit;
    }

    if ($action == "edit") {
        $id = trim($_POST['id']);
        $descripcion = trim($_POST['descripcion']);
        $valor = trim($_POST['valor']);

        if (empty($id) || empty($descripcion) || empty($valor) || !is_numeric($valor)) {
            echo json_encode(['status' => 'error', 'message' => 'Datos inválidos']);
            exit;
        }

        $stmt = db()->prepare("UPDATE egresos SET descripcion = :descripcion, valor = :valor WHERE id = :id");
        if ($stmt->execute([':descripcion' => $descripcion, ':valor' => $valor, ':id' => $id])) {
			
			
			// LOGS
		require_once __DIR__ . '/../inc/log_action.php';
		$desc = json_encode([
					'id' => $id, 					
					'descripcion' => $descripcion, 
					'valor' => $valor
		], JSON_UNESCAPED_UNICODE);
		log_action('Editar Egreso', $desc, 'Contabilidad');
		// END LOGS
			
            echo json_encode(['status' => 'success', 'message' => 'Egreso actualizado']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al actualizar']);
        }
        exit;
    }

    if ($action == "delete") {
        $id = trim($_POST['id']);
        if (empty($id)) {
            echo json_encode(['status' => 'error', 'message' => 'ID inválido']);
            exit;
        }

        $stmt = db()->prepare("UPDATE egresos SET borrado = 1 WHERE id = :id");
        if ($stmt->execute([':id' => $id])) {
			
			// LOGS
		require_once __DIR__ . '/../inc/log_action.php';
		$desc = json_encode([
					'id' => $id
		], JSON_UNESCAPED_UNICODE);
		log_action('Eliminar Egreso', $desc, 'Contabilidad');
		// END LOGS
			
            echo json_encode(['status' => 'success', 'message' => 'Egreso eliminado']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Error al eliminar']);
        }
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Egresos</title>
    <?php include('../inc/header.php'); ?>
    <style>
        .card { margin-bottom: 20px; }
        tr[role="row"] { cursor: pointer; }
    </style>
</head>
<body>
<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
  <div class="portada">
    <h1 class="mb-4">Gestión de Egresos</h1>
	  <button class="btn btn-danger float-end" id="btnAddEgreso"><i class="fa fa-plus"></i> Registrar Egreso</button>
  </div>
</div>
	
<?php include('../inc/menu.php'); ?>

<div class="container mt-4">
    
	<?php if (isset($_SESSION["user_permissions"]) && in_array('Agregar Egresos', $_SESSION["user_permissions"])): ?>
	
    
	<?php endif;?>

    <table id="egresos-table" class="table table-striped">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Descripción</th>
                <th>Valor</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Modal Agregar -->
<div class="modal fade" id="modalAddEgreso" tabindex="-1" aria-labelledby="modalAddEgresoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formAddEgreso">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalAddEgresoLabel">Agregar Egreso</h5>
					
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
					
                </div>
                <div class="modal-body">
                    <!-- Campo de fecha oculto -->
                    <input type="hidden" id="add_fecha" name="fecha" value="<?php echo $hoy; ?>">

                    <div class="mb-3">
                        <label for="add_descripcion" class="form-label">Descripción:</label>
                        <input type="text" class="form-control" id="add_descripcion" name="descripcion" required>
                    </div>
                    <div class="mb-3">
                        <label for="add_valor" class="form-label">Valor:</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="add_valor" name="valor" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary w-25">Guardar</button>
                    <button type="button" class="btn btn-secondary w-25" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="modalEditEgreso" tabindex="-1" aria-labelledby="modalEditEgresoLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="formEditEgreso">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="modalEditEgresoLabel">Editar Egreso</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="edit_id" name="id">

                    <div class="mb-3">
                        <label for="edit_fecha" class="form-label">Fecha:</label>
                        <input type="date" class="form-control" id="edit_fecha" name="fecha" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción:</label>
                        <input type="text" class="form-control" id="edit_descripcion" name="descripcion" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_valor" class="form-label">Valor:</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="edit_valor" name="valor" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
					<?php if (isset($_SESSION["user_permissions"]) && in_array('Eliminar Egresos', $_SESSION["user_permissions"])): ?>
                    <button type="button" class="btn btn-danger" id="btnDeleteEgreso">
                        <i class="fa fa-trash-o"></i> Eliminar
                    </button>
					<?php endif;?>					
                    <div>
                        <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php include('../inc/menu-footer.php'); ?>
<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
$(document).ready(function () {
    // Inicializar DataTables sin botón de edición
    var table = $('#egresos-table').DataTable({
        ajax: 'expenses.php?action=fetch',
        columns: [
            {
                data: 'fecha',
                render: function (data, type, row) {
                    return data;
                }
            },
            { data: 'descripcion' },
            {
                data: 'valor',
                render: function (data, type, row) {
                    return "$" + parseFloat(data).toLocaleString('es-CO', { minimumFractionDigits: 2 });
                }
            }
        ],
        pageLength: 50,
        language: {
            url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/es-ES.json'
        }
    });

    // Abrir modal para agregar egreso
    $('#btnAddEgreso').on('click', function () {
        $('#formAddEgreso')[0].reset();
        $('#add_fecha').val('<?php echo $hoy; ?>');
        $('#modalAddEgreso').modal('show');
    });

    // Guardar nuevo egreso
    $('#formAddEgreso').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: 'expenses.php',
            method: 'POST',
            data: {
                action: 'add',
                descripcion: $('#add_descripcion').val(),
                valor: $('#add_valor').val()
            },
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    Swal.fire('Éxito', res.message, 'success');
                    $('#modalAddEgreso').modal('hide');
                    table.ajax.reload();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'Fallo en la red.', 'error');
            }
        });
    });

    // Abrir modal de edición al hacer clic en fila
    $('#egresos-table tbody').on('click', 'tr', function () {
        var data = table.row(this).data();
        if (data) {
            $('#edit_id').val(data.id);
            $('#edit_fecha').val(data.fecha);
            $('#edit_descripcion').val(data.descripcion);
            $('#edit_valor').val(data.valor);
            $('#modalEditEgreso').modal('show');
        }
    });

    // Guardar edición
    $('#formEditEgreso').on('submit', function (e) {
        e.preventDefault();
        $.ajax({
            url: 'expenses.php',
            method: 'POST',
            data: {
                action: 'edit',
                id: $('#edit_id').val(),
                descripcion: $('#edit_descripcion').val(),
                valor: $('#edit_valor').val()
            },
            dataType: 'json',
            success: function (res) {
                if (res.status === 'success') {
                    Swal.fire('Éxito', res.message, 'success');
                    $('#modalEditEgreso').modal('hide');
                    table.ajax.reload();
                } else {
                    Swal.fire('Error', res.message, 'error');
                }
            },
            error: function () {
                Swal.fire('Error', 'No se pudo enviar la solicitud.', 'error');
            }
        });
    });

    // Eliminar egreso (soft delete)
    $('#btnDeleteEgreso').on('click', function () {
        var id = $('#edit_id').val();
        if (!id) {
            Swal.fire('Error', 'Egreso no seleccionado', 'error');
            return;
        }

        Swal.fire({
            title: '¿Desea eliminar este egreso?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.post('expenses.php', { action: 'delete', id: id }, function (res) {
                    if (res.status === 'success') {
                        Swal.fire('Eliminado', res.message, 'success');
                        $('#modalEditEgreso').modal('hide');
                        table.ajax.reload();
                    } else {
                        Swal.fire('Error', res.message, 'error');
                    }
                }, 'json').fail(function () {
                    Swal.fire('Error', 'Fallo en la red.', 'error');
                });
            }
        });
    });
});
</script>
</body>
</html>