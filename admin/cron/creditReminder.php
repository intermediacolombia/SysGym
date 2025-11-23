<?php
/**
 * creditReminder.php
 *
 * Recupera los clientes con créditos activos y devuelve:
 *   - Datos básicos del cliente
 *   - Total adeudado (suma de todos los créditos pendientes)
 *   - Detalle de cada crédito pendiente en formato legible
 */

require_once __DIR__ . '/../../inc/config.php';

try {
    // Conexión a la BD
    db()->exec("SET lc_time_names = 'es_ES'");

    /*-------------------------------------------------------------
     * Consulta:
     *  1. Clientes activos, no borrados ni congelados.
     *  2. Créditos pendientes (estado = 'pendiente' o saldo > 0).
     *  3. Agrupa por cliente, suma el saldo pendiente y concatena
     *     el detalle de cada crédito.
     *------------------------------------------------------------*/
  $sql = '
    SELECT c.id,
           c.nombres,
           c.apellidos,
           c.dialCode,
           c.telefono,
           SUM(cr.valor) AS total_credito,
           GROUP_CONCAT(
               CONCAT(
                   cr.descripcion,
                   " : $", FORMAT(cr.valor, 0),
                   " (", DATE_FORMAT(cr.fecha, "%d de %M del %Y"), ")"
               )
               ORDER BY cr.fecha
               SEPARATOR "\\n"
           ) AS detalle_creditos
    FROM clientes c
    JOIN creditos cr ON cr.idCliente = c.id
    WHERE c.borrado = 0
      AND c.estado  = "activo"
      AND c.congelado = 0      
      AND cr.estado = 0
    GROUP BY c.id
    HAVING total_credito > 0
    ORDER BY c.apellidos, c.nombres
';



    $stmt = db()->prepare($sql);
    $stmt->execute();
    $creditReminders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*--- RESPUESTA JSON (o úsalo como array en tu flujo) ---*/
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'data'   => $creditReminders
    ]);

    /*-------------------------------------------------------
     * Si quieres disparar mensajes de WhatsApp automáticamente,
     * descomenta la línea inferior y asegúrate de que el script
     * whatsapp/creditReminder.php espera la variable
     * $creditReminders.
     *------------------------------------------------------*/
    include(__DIR__ . '/../../whatsapp/creditReminder.php');

} catch (PDOException $e) {
    header('Content-Type: application/json');
    echo json_encode([
        'status'  => 'error',
        'message' => 'Error en la conexión: ' . $e->getMessage()
    ]);
    exit;
}
?>

