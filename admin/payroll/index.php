<?php
// Sesiones y restricciones (ajusta a tu gusto)
require_once __DIR__ . '/../login/session.php';
$permisopage = 'Pagar Nominas';
include('../login/restriction.php');
session_start();

require_once __DIR__ . '/../../inc/config.php';

// Manejar el envío del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_empleado = $_POST['empleado'];
    $fecha_inicio_pago = $_POST['fecha_inicio_pago'];
    $fecha_fin_pago = $_POST['fecha_fin_pago'];

    try {
        // Obtener el salario y el teléfono del empleado seleccionado
        $sql_empleado = "SELECT * FROM empleados WHERE id = :id";
        $stmt_empleado = db()->prepare($sql_empleado);
        $stmt_empleado->execute([':id' => $id_empleado]);
        $empleado = $stmt_empleado->fetch(PDO::FETCH_ASSOC);

        if (!$empleado) {
            throw new Exception("Empleado no encontrado.");
        }

        // Crear objetos DateTime para las fechas seleccionadas
        $fecha_inicio = new DateTime($fecha_inicio_pago);
        $fecha_fin = new DateTime($fecha_fin_pago);

        // Verificar que sean del mismo mes y año
        $mismo_mes = $fecha_inicio->format('Y-m') === $fecha_fin->format('Y-m');
        $ultimo_dia_mes = $fecha_fin->format('t');

        // Detectar tipo de período
        $es_mes_completo = $fecha_inicio->format('d') === '01' && $fecha_fin->format('d') == $ultimo_dia_mes;
        $es_primera_quincena = $mismo_mes && $fecha_inicio->format('d') === '01' && $fecha_fin->format('d') === '15';
        $es_segunda_quincena = $mismo_mes && $fecha_inicio->format('d') === '16' && $fecha_fin->format('d') == $ultimo_dia_mes;

        // Calcular valor pagado
        if ($es_mes_completo) {
            $valor_pagado = $empleado['salario'];
            $dias_pagados = (int) $ultimo_dia_mes;
            $tipo_pago = 'Mes completo';
        } elseif ($es_primera_quincena || $es_segunda_quincena) {
            $valor_pagado = $empleado['salario'] / 2;
            $dias_pagados = $es_primera_quincena ? 15 : ($ultimo_dia_mes - 15);
            $tipo_pago = 'Quincena';
        } else {
            $dias_pagados = $fecha_inicio->diff($fecha_fin)->days + 1;
            $salario_diario = $empleado['salario'] / 30;
            $valor_pagado = $salario_diario * $dias_pagados;
            $tipo_pago = 'Proporcional';
        }

        // Guardar la nómina en la base de datos
        $sql_nomina = "INSERT INTO nomina (
            fecha_generacion, nombre_empleado, fecha_inicio_pago, fecha_fin_pago, total_dias_pagados, valor_pagado
        ) VALUES (
            NOW(), :nombre_empleado, :fecha_inicio_pago, :fecha_fin_pago, :total_dias_pagados, :valor_pagado
        )";
        $stmt_nomina = db()->prepare($sql_nomina);
        $stmt_nomina->execute([
            ':nombre_empleado' => $empleado['nombre'] . ' ' . $empleado['apellido'],
            ':fecha_inicio_pago' => $fecha_inicio_pago,
            ':fecha_fin_pago' => $fecha_fin_pago,
            ':total_dias_pagados' => $dias_pagados,
            ':valor_pagado' => $valor_pagado
        ]);

        // Obtener el ID de la nómina generada
        $id_nomina = db()->lastInsertId();		

        // Preparar los datos para enviar el mensaje por WhatsApp
        $cp_nombres = $empleado['nombre'];
        $cp_apellidos = $empleado['apellido'];
        $cp_dialCode = $empleado['dialCode']; // Asegúrate de que este campo esté correctamente registrado
        $cp_telefono = $empleado['telefono']; // Asegúrate de que este campo esté correctamente registrado
        $cp_pago_plan = $fecha_inicio_pago;
        $cp_vencimiento_plan = $fecha_fin_pago;
        $facturaId = $id_nomina; // Usamos el ID de la nómina como referencia

        // Incluir el archivo que maneja el envío por WhatsApp
        include('../../whatsapp/employee-payroll.php');
		
		// LOGS
		require_once __DIR__ . '/../inc/log_action.php';		
		$desc = json_encode([
			'nombre_empleado' => $empleado['nombre'] . ' ' . $empleado['apellido'],
            'fecha_inicio_pago' => $fecha_inicio_pago,
            'fecha_fin_pago' => $fecha_fin_pago,
            'total_dias_pagados' => $dias_pagados,
            'valor_pagado' => $valor_pagado			
		], JSON_UNESCAPED_UNICODE);
		log_action('Pago Nomina', $desc, 'Nomina');
		// END LOGS	

        // Mensaje de éxito
        $_SESSION['success'] = "Nómina generada y enviada correctamente.";
        header("Location: index.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['error'] = "Error al generar la nómina: " . $e->getMessage();
        header("Location: index.php");
        exit;
    }
	
}


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pago de Nómina</title>
    <?php include('../inc/header.php'); ?>
    <style>
        .form-group {
            margin-bottom: 1rem;
        }
        .btn-primary {
            background-color: #214A82;
            border-color: #214A82;
        }
        .btn-primary:hover {
            background-color: #1B3C6F;
            border-color: #1B3C6F;
        }
        .summary-box {
            margin-top: 1rem;
            padding: 1rem;
            background-color: #f8f9fa;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
    </style>
</head>
<body>
	<div class="container" style="padding: 0px; background:rgba(0,0,0,0.00)">
<div class="portada">
    <h1>Pago de Nómina</h1>
	
	</div>
	</div>
<?php include('../inc/menu.php'); ?>

<div class="container mt-4">
    

    <!-- Mostrar mensajes de éxito o error -->
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Formulario para pagar nómina -->
    <form method="post" action="" id="payrollForm">
        <div class="row">
            <!-- Columna 1: Seleccionar Empleado -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="empleado">Seleccionar Empleado:</label>
                    <select class="form-control" id="empleado" name="empleado" required>
                        <option value="">Seleccione un empleado</option>
                        <?php
                        try {
                           

                            $sql = "SELECT id, nombre, apellido, salario FROM empleados WHERE borrado = 0";
                            $stmt = db()->query($sql);
                            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                                echo '<option value="' . $row['id'] . '" data-salario="' . $row['salario'] . '">' .
                                    htmlspecialchars($row['nombre']) . ' ' .
                                    htmlspecialchars($row['apellido']) . '</option>';
                            }
                        } catch (PDOException $e) {
                            echo '<option value="">Error al cargar empleados</option>';
                        }
                        ?>
                    </select>
                </div>
            </div>

            <!-- Columna 2: Fecha de Inicio -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="fecha_inicio_pago">Fecha de Inicio de Pago:</label>
                    <input type="date" class="form-control" id="fecha_inicio_pago" name="fecha_inicio_pago" required>
                </div>
            </div>

            <!-- Columna 3: Fecha de Fin -->
            <div class="col-md-4">
                <div class="form-group">
                    <label for="fecha_fin_pago">Fecha de Fin de Pago:</label>
                    <input type="date" class="form-control" id="fecha_fin_pago" name="fecha_fin_pago" required>
                </div>
            </div>
        </div>

        <!-- Resumen en tiempo real -->
        <div class="summary-box mt-4" id="summaryBox" style="display: none;">
            <div class="card">
                <div class="card-body">
                    <strong>Resumen:</strong>
                    <ul class="list-unstyled">
                        <li><strong>Días laborados:</strong> <span id="totalDias">0</span></li>
                        <li><strong>Tipo de pago:</strong> <span id="tipoPago">Proporcional</span></li>
                        <li><strong>Valor a pagar:</strong> $<span id="valorAPagar">0</span></li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Botón Pagar Nómina -->
        <div class="mt-3">
            <button type="button" class="btn btn-primary" id="payNominaBtn"><i class="fa fa-money-bill"></i> Pagar Nómina</button>
        </div>
    </form>
</div>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const empleadoSelect = document.getElementById('empleado');
    const fechaInicioPago = document.getElementById('fecha_inicio_pago');
    const fechaFinPago = document.getElementById('fecha_fin_pago');
    const summaryBox = document.getElementById('summaryBox');
    const totalDias = document.getElementById('totalDias');
    const tipoPago = document.getElementById('tipoPago');
    const valorAPagar = document.getElementById('valorAPagar');
    const payNominaBtn = document.getElementById('payNominaBtn');

    let salarioMensual = 0;

    /*function calcularResumen() {
        const fechaInicioStr = fechaInicioPago.value;
        const fechaFinStr = fechaFinPago.value;

        // Validaciones iniciales
        if (!fechaInicioStr || !fechaFinStr || salarioMensual <= 0) {
            summaryBox.style.display = 'none';
            return;
        }

        const fechaInicio = new Date(fechaInicioStr + 'T00:00:00');
        const fechaFin = new Date(fechaFinStr + 'T00:00:00');

        if (fechaInicio > fechaFin) {
            summaryBox.style.display = 'none';
            return;
        }

        const mismoMes = (
            fechaInicio.getFullYear() === fechaFin.getFullYear() &&
            fechaInicio.getMonth() === fechaFin.getMonth()
        );

        const ultimoDiaDelMes = new Date(fechaInicio.getFullYear(), fechaInicio.getMonth() + 1, 0).getDate();

        const esMesCompleto = (
            mismoMes &&
            fechaInicio.getDate() === 1 &&
            fechaFin.getDate() === ultimoDiaDelMes
        );

        let diasPagados = 0;
        let valorPagar = 0;
        let tipoPagoTexto = '';

        if (esMesCompleto) {
            diasPagados = ultimoDiaDelMes;
            valorPagar = salarioMensual;
            tipoPagoTexto = 'Mes completo';
        } else {
            // Cálculo de días pagados (siempre incluye fecha inicio y fin)
            diasPagados = Math.floor((fechaFin - fechaInicio) / (1000 * 60 * 60 * 24)) + 1;
            const salarioDiario = salarioMensual / 30; // SIEMPRE dividir entre 30
            valorPagar = salarioDiario * diasPagados;
            tipoPagoTexto = 'Proporcional';
        }

        // Mostrar resumen
        totalDias.textContent = diasPagados;
        tipoPago.textContent = tipoPagoTexto;
        valorAPagar.textContent = valorPagar.toLocaleString('es-CO', { minimumFractionDigits: 0 });
        summaryBox.style.display = 'block';
    }*/

		function calcularResumen() {
    const fechaInicioStr = fechaInicioPago.value;
    const fechaFinStr = fechaFinPago.value;

    if (!fechaInicioStr || !fechaFinStr || salarioMensual <= 0) {
        summaryBox.style.display = 'none';
        return;
    }

    const fechaInicio = new Date(fechaInicioStr + 'T00:00:00');
    const fechaFin = new Date(fechaFinStr + 'T00:00:00');

    if (fechaInicio > fechaFin) {
        summaryBox.style.display = 'none';
        return;
    }

    const anio = fechaInicio.getFullYear();
    const mes = fechaInicio.getMonth();
    const ultimoDiaDelMes = new Date(anio, mes + 1, 0).getDate();

    const mismoMes = (
        fechaInicio.getFullYear() === fechaFin.getFullYear() &&
        fechaInicio.getMonth() === fechaFin.getMonth()
    );

    const esMesCompleto = (
        mismoMes &&
        fechaInicio.getDate() === 1 &&
        fechaFin.getDate() === ultimoDiaDelMes
    );

    const esPrimeraQuincena = (
        mismoMes &&
        fechaInicio.getDate() === 1 &&
        fechaFin.getDate() === 15
    );

    const esSegundaQuincena = (
        mismoMes &&
        fechaInicio.getDate() === 16 &&
        fechaFin.getDate() === ultimoDiaDelMes
    );

    let diasPagados = 0;
    let valorPagar = 0;
    let tipoPagoTexto = '';

    if (esMesCompleto) {
        diasPagados = ultimoDiaDelMes;
        valorPagar = salarioMensual;
        tipoPagoTexto = 'Mes completo';
    } else if (esPrimeraQuincena || esSegundaQuincena) {
        diasPagados = esPrimeraQuincena ? 15 : (ultimoDiaDelMes - 15);
        valorPagar = salarioMensual / 2;
        tipoPagoTexto = 'Quincena';
    } else {
        diasPagados = Math.floor((fechaFin - fechaInicio) / (1000 * 60 * 60 * 24)) + 1;
        const salarioDiario = salarioMensual / 30;
        valorPagar = salarioDiario * diasPagados;
        tipoPagoTexto = 'Proporcional';
    }

    totalDias.textContent = diasPagados;
    tipoPago.textContent = tipoPagoTexto;
    valorAPagar.textContent = valorPagar.toLocaleString('es-CO', { minimumFractionDigits: 0 });
    summaryBox.style.display = 'block';
}
				  
						  
    // Cambiar empleado
    empleadoSelect.addEventListener('change', function () {
        const selectedOption = empleadoSelect.options[empleadoSelect.selectedIndex];
        salarioMensual = parseFloat(selectedOption.getAttribute('data-salario')) || 0;
        calcularResumen();
    });

    // Cambiar fechas
    fechaInicioPago.addEventListener('change', calcularResumen);
    fechaFinPago.addEventListener('change', calcularResumen);

    // Botón pagar
    payNominaBtn.addEventListener('click', function () {
        const empleado = empleadoSelect.options[empleadoSelect.selectedIndex].text;
        const fechaInicio = fechaInicioPago.value;
        const fechaFin = fechaFinPago.value;

        Swal.fire({
            title: 'Confirmar Pago de Nómina',
            html:
                `<strong>Empleado:</strong> ${empleado}<br>` +
                `<strong>Fecha de Inicio:</strong> ${fechaInicio}<br>` +
                `<strong>Fecha de Fin:</strong> ${fechaFin}<br>` +
                `<strong>Días Laborados:</strong> ${totalDias.textContent}<br>` +
                `<strong>Tipo de Pago:</strong> ${tipoPago.textContent}<br>` +
                `<strong>Valor a Pagar:</strong> $${valorAPagar.textContent}`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('payrollForm').submit();
            }
        });
    });
});

	</script>

<?php include('../inc/menu-footer.php'); ?>
</body>
</html>