<?php
// Sesiones y restricciones (ajusta a tu gusto)
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Ver Empleados';
include('../login/restriction.php');
session_start();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Listado de Empleados</title>
  <?php include('../inc/header.php'); ?>
  <!-- Aquí asumes que en header.php ya cargas los estilos de Bootstrap, DataTables, FontAwesome, etc. -->

  <style>
    /* Estilos de la tabla */
    #tablaEmpleados tbody tr,
    #tablaEmpleados tbody tr td {
      cursor: pointer !important;
      transition: background-color 0.1s ease;
    }
    #tablaEmpleados thead th {
      background-color: #214A82;
      color: white;
    }
    #tablaEmpleados tbody tr:hover {
      background-color: #4972AA !important;
    }
    #tablaEmpleados tbody tr:hover td {
      color: white !important;
    }
    /* La columna de acción no dispara la redirección al modal de edición */
    .no-click {
      cursor: default !important;
    }
  </style>

</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
	<div class="portada">
    <h1>Listado de Empleados</h1>
		<button type="button" class="btn btn-primary float-end" data-toggle="modal" data-target="#newEmployeeModal">
      <i class="fas fa-user-plus"></i> Nuevo Empleado
    </button>
	</div>
	</div>
<?php include('../inc/menu.php'); ?>
<div class="container mt-4">
  
  <?php if (isset($_SESSION["user_permissions"]) && in_array('Agregar Empleados', $_SESSION["user_permissions"])): ?>
  <div class="mb-3">
    
  </div>
  <?php endif;?>
  
  <!-- Alertas -->
  <?php if(isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
      <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  <?php endif; ?>
  <?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
      <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  <?php endif; ?>
  
  <!-- Tabla de empleados -->
  <table id="tablaEmpleados" class="table table-striped table-bordered">
    <thead>
  <tr>
    <th>Documento</th>
    <th>Nombre</th>
    <th>Apellido</th>
    <th>Correo</th>
    <th>Teléfono</th>
    <th>Cargo</th>
    <th>Salario</th>
    <th>Fecha Ingreso</th> <!-- Nueva columna -->
    <th>Estado</th>
    <?php if (isset($_SESSION["user_permissions"]) && in_array('Borrar Empleados', $_SESSION["user_permissions"])): ?>
    <th>Acción</th>
    <?php endif; ?>  
  </tr>
</thead>
    <tbody>
      <?php
// Conexión a la BD
require_once __DIR__ . '/../../inc/config.php';
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Seleccionar empleados
    $sql = "SELECT id, documento, nombre, apellido, correo, telefono, dialCode, cargo, salario, estado, fecha_ingreso 
            FROM empleados 
            WHERE borrado = 0";
    $stmt = $pdo->query($sql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo '<tr class="employee-row"
            data-id="' . $row['id'] . '"
            data-documento="' . htmlspecialchars($row['documento']) . '"
            data-nombre="' . htmlspecialchars($row['nombre']) . '"
            data-apellido="' . htmlspecialchars($row['apellido']) . '"
            data-correo="' . htmlspecialchars($row['correo']) . '"
            data-telefono="' . htmlspecialchars($row['telefono']) . '"
            data-dialcode="' . htmlspecialchars($row['dialCode']) . '"
            data-cargo="' . htmlspecialchars($row['cargo']) . '"
            data-salario="' . htmlspecialchars($row['salario']) . '"
            data-estado="' . htmlspecialchars($row['estado']) . '"
            data-fecha_ingreso="' . htmlspecialchars($row['fecha_ingreso']) . '">'; // Nuevo campo
        
        echo '<td>' . htmlspecialchars($row['documento']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nombre']) . '</td>';
        echo '<td>' . htmlspecialchars($row['apellido']) . '</td>';
        echo '<td>' . htmlspecialchars($row['correo']) . '</td>';
        echo '<td>+' . htmlspecialchars($row['dialCode']) . ' ' . htmlspecialchars($row['telefono']) . '</td>';
        echo '<td>' . htmlspecialchars($row['cargo']) . '</td>';
        echo '<td>$' . number_format($row['salario'], 0, ',', '.') . '</td>';
        // Nueva columna para la fecha de ingreso
        echo '<td>' . date('d/m/Y', strtotime($row['fecha_ingreso'])) . '</td>';
        echo '<td>';
        if ($row['estado'] == 0) {
            echo '<span class="badge badge-success">Activo</span>';
        } else {
            echo '<span class="badge badge-danger">Inactivo</span>';
        }
        echo '</td>';
        if (isset($_SESSION["user_permissions"]) && in_array('Borrar Empleados', $_SESSION["user_permissions"])):
        echo '<td class="no-click text-center">
                <button type="button" class="btn btn-sm btn-outline-danger delete-btn" data-id="' . $row['id'] . '">
                  <i class="fas fa-trash"></i>
                </button>
              </td>';
        endif;
        echo '</tr>';
    }
} catch (PDOException $e) {
    echo '<tr><td colspan="10">Error: ' . $e->getMessage() . '</td></tr>';
}
?>
    </tbody>
  </table>
</div>

<!-- Modal Nuevo Empleado -->
<?php if (isset($_SESSION["user_permissions"]) && in_array('Agregar Empleados', $_SESSION["user_permissions"])): ?>
<div class="modal fade" id="newEmployeeModal" tabindex="-1" role="dialog" aria-labelledby="newEmployeeModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" action="new_employee.php" class="needs-validation">
        <div class="modal-header">
          <h5 class="modal-title">Nuevo Empleado</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Campos -->
          <div class="form-group">
            <label for="newDocumento">Documento</label>
            <input type="number" class="form-control" id="newDocumento" name="documento" required>
            <div class="invalid-feedback">Ingrese el documento.</div>
          </div>
          <div class="form-group">
            <label for="newNombre">Nombre</label>
            <input type="text" class="form-control" id="newNombre" name="nombre" required>
            <div class="invalid-feedback">Ingrese el nombre.</div>
          </div>
          <div class="form-group">
            <label for="newApellido">Apellido</label>
            <input type="text" class="form-control" id="newApellido" name="apellido" required>
            <div class="invalid-feedback">Ingrese el apellido.</div>
          </div>
          <div class="form-group">
            <label for="newCorreo">Correo</label>
            <input type="email" class="form-control" id="newCorreo" name="correo" required>
            <div class="invalid-feedback">Ingrese un correo válido.</div>
          </div>
          <div class="form-group">
            <label for="newTelefono">Teléfono</label><br>
            <input type="tel" class="form-control" id="newTelefono" name="telefono" required>
            <div class="invalid-feedback">Ingrese el teléfono.</div>
          </div>
          <div class="form-group">
            <label for="newCargo">Cargo</label>
            <input type="text" class="form-control" id="newCargo" name="cargo" required>
            <div class="invalid-feedback">Ingrese el cargo.</div>
          </div>
          
          <!-- Nuevo campo: Fecha de Ingreso -->
          <div class="form-group">
            <label for="newFechaIngreso">Fecha de Ingreso</label>
            <input type="date" class="form-control" id="newFechaIngreso" name="fecha_ingreso" required>
            <div class="invalid-feedback">Seleccione la fecha de ingreso.</div>
          </div>
          
          <label for="newSalario" class="form-label">Salario</label>
          <div class="input-group mb-3">			  
            <div class="input-group-prepend">
              <span class="input-group-text" id="basic-addon1">$</span>
            </div>            
            <input type="number" class="form-control" id="newSalario" name="salario" required step="0.01">
            <div class="invalid-feedback">Ingrese el salario.</div>
          </div>
          
          <div class="form-group">
            <label for="newEstado">Estado</label>
            <select class="form-control" id="newEstado" name="estado" required>
              <option value="">Seleccione un estado</option>
              <option value="0">Activo</option>
              <option value="1">Inactivo</option>
            </select>
            <div class="invalid-feedback">Seleccione un estado.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="newRegisterBtn"><i class="fa fa-save"></i> Registrar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif;?>
<!-- Modal Editar Empleado -->
<?php if (isset($_SESSION["user_permissions"]) && in_array('Editar Empleados', $_SESSION["user_permissions"])): ?>
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" action="update_employee.php" class="needs-validation" novalidate id="editForm">
        <div class="modal-header">
          <h5 class="modal-title">Editar Empleado</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <!-- Campo oculto para el ID -->
          <input type="hidden" id="editId" name="id">
          <div class="form-group">
            <label for="editDocumento">Documento</label>
            <input type="number" class="form-control" id="editDocumento" name="documento" required>
            <div class="invalid-feedback">Ingrese el documento.</div>
          </div>
          <div class="form-group">
            <label for="editNombre">Nombre</label>
            <input type="text" class="form-control" id="editNombre" name="nombre" required>
            <div class="invalid-feedback">Ingrese el nombre.</div>
          </div>
          <div class="form-group">
            <label for="editApellido">Apellido</label>
            <input type="text" class="form-control" id="editApellido" name="apellido" required>
            <div class="invalid-feedback">Ingrese el apellido.</div>
          </div>
          <div class="form-group">
            <label for="editCorreo">Correo</label>
            <input type="email" class="form-control" id="editCorreo" name="correo" required>
            <div class="invalid-feedback">Ingrese un correo válido.</div>
          </div>
          <div class="form-group">
            <label for="editTelefono">Teléfono</label><br>
            <input type="tel" class="form-control" id="editTelefono" name="telefono" required>
            <div class="invalid-feedback">Ingrese el teléfono.</div>
          </div>
          <div class="form-group">
            <label for="editCargo">Cargo</label>
            <input type="text" class="form-control" id="editCargo" name="cargo" required>
            <div class="invalid-feedback">Ingrese el cargo.</div>
          </div>
          
          <!-- Nuevo campo: Fecha de Ingreso -->
          <div class="form-group">
            <label for="editFechaIngreso">Fecha de Ingreso</label>
            <input type="date" class="form-control" id="editFechaIngreso" name="fecha_ingreso" required>
            <div class="invalid-feedback">Seleccione la fecha de ingreso.</div>
          </div>
          
          <label for="editSalario" class="form-label">Salario</label>
          <div class="input-group mb-3">			  
            <div class="input-group-prepend">
              <span class="input-group-text" id="basic-addon1">$</span>
            </div> 
            <input type="number" step="0.01" class="form-control" id="editSalario" name="salario" required>
            <div class="invalid-feedback">Ingrese el salario.</div>
          </div>
          
          <div class="form-group">
            <label for="editEstado">Estado</label>
            <select class="form-control" id="editEstado" name="estado" required>
              <option value="">Seleccione un estado</option>
              <option value="0">Activo</option>
              <option value="1">Inactivo</option>
            </select>
            <div class="invalid-feedback">Seleccione un estado.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Guardar Cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
	
<?php include('../inc/menu-footer.php'); ?>
<!-- intlTelInput JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
$(document).ready(function() {
  // Inicializar DataTables
  var table = $('#tablaEmpleados').DataTable({
    "language": { "url": "//cdn.datatables.net/plug-ins/1.10.20/i18n/Spanish.json" },
    "order": [[0, "asc"]],
    "pageLength": 50
  });

  // Inicializar intlTelInput
  var newPhoneInput = document.querySelector("#newTelefono");
  var editPhoneInput = document.querySelector("#editTelefono");
  
  var newIti = window.intlTelInput(newPhoneInput, {
    initialCountry: "co",
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
    separateDialCode: true
  });
  
  var editIti = window.intlTelInput(editPhoneInput, {
    initialCountry: "co",
    utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
    separateDialCode: true
  });

  // Manejador de clic para editar
  $('#tablaEmpleados tbody').on('click', 'tr.employee-row', function(e) {
    if ($(e.target).closest('.delete-btn').length > 0) return;
    
    var id = $(this).data('id');
    var documento = $(this).data('documento');
    var nombre = $(this).data('nombre');
    var apellido = $(this).data('apellido');
    var correo = $(this).data('correo');
    var telefono = $(this).data('telefono');
    var dialCode = $(this).data('dialcode');
    var cargo = $(this).data('cargo');
    var salario = $(this).data('salario');
    var estado = $(this).data('estado');
    var fecha_ingreso = $(this).data('fecha_ingreso'); // Nuevo campo
    
    // Rellenar campos
    $('#editId').val(id);
    $('#editDocumento').val(documento);
    $('#editNombre').val(nombre);
    $('#editApellido').val(apellido);
    $('#editCorreo').val(correo);
    $('#editCargo').val(cargo);
    $('#editSalario').val(salario);
    $('#editEstado').val(estado);
    $('#editFechaIngreso').val(fecha_ingreso); // Rellenar nuevo campo
    
    // Establecer número de teléfono
    var fullNumber = '+' + dialCode + telefono;
    editIti.setNumber(fullNumber);
    
    $('#editModal').modal('show');
  });

  // Antes de enviar formulario nuevo
  $('#newEmployeeModal form').on('submit', function(e) {
    var fullNumber = newIti.getNumber();
    var dialCode = newIti.getSelectedCountryData().dialCode;
    var phoneNumber = fullNumber.replace('+' + dialCode, '');
    
    $(this).append(
      '<input type="hidden" name="dialCode" value="' + dialCode + '">' +
      '<input type="hidden" name="telefono" value="' + phoneNumber + '">'
    );
  });

  // Antes de enviar formulario edición
  $('#editModal form').on('submit', function(e) {
    var fullNumber = editIti.getNumber();
    var dialCode = editIti.getSelectedCountryData().dialCode;
    var phoneNumber = fullNumber.replace('+' + dialCode, '');
    
    $(this).append(
      '<input type="hidden" name="dialCode" value="' + dialCode + '">' +
      '<input type="hidden" name="telefono" value="' + phoneNumber + '">'
    );
  });

  // Confirmación de borrado
  $('#tablaEmpleados').on('click', '.delete-btn', function(e) {
    e.stopPropagation();
    var id = $(this).data('id');
    Swal.fire({
      title: '¿Está seguro?',
      text: "Esta acción borrará el empleado.",
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Sí, borrar',
      cancelButtonText: 'Cancelar'
    }).then((result) => {
      if (result.isConfirmed) {
        window.location = "dele_employee.php?id=" + id;
      }
    });
  });
  
  // Corrección para el botón "X" y "Cancelar"
  $('#editModal').on('hidden.bs.modal', function() {
    // Limpiar el formulario al cerrar el modal
    $('#editForm')[0].reset();
    $('#editForm').removeClass('was-validated');
  });

  // Asegurar que el botón "X" y "Cancelar" funcionen correctamente
  $('#editModal .close, #editModal .btn-secondary').on('click', function(e) {
    e.preventDefault();
    $('#editModal').modal('hide');
  });
  
  // Validación de formularios
  (function() {
    'use strict';
    window.addEventListener('load', function() {
      var forms = document.getElementsByClassName('needs-validation');
      var validation = Array.prototype.filter.call(forms, function(form) {
        form.addEventListener('submit', function(event) {
          if (form.checkValidity() === false) {
            event.preventDefault();
            event.stopPropagation();
          }
          form.classList.add('was-validated');
        }, false);
      });
    }, false);
  })();
});
</script>
</body>
</html>




