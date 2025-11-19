<?php
/**
 * close_all_open_boxes.php
 * Cierra todas las cajas abiertas.  Si no hubo ventas, guarda ceros.
 */

require_once __DIR__ . '/../../inc/config.php';

try {
    /* ------------------------------------------------------------------
       UPDATE con JOIN – todo en una sola sentencia
    ------------------------------------------------------------------ */
    $sql = "
      UPDATE cajas AS c
      LEFT JOIN (
        SELECT caja_id,
               IFNULL(SUM(valor), 0) AS ventas
        FROM   ventas
        GROUP  BY caja_id
      ) AS v ON v.caja_id = c.id

      SET c.estado        = 0,
          c.fecha_cierre  = '$hoy',
          c.hora_cierre   = '$hora',
          c.total_vendido = IFNULL(v.ventas, 0),
          c.total_cierre  = COALESCE(c.monto_inicial, 0) + IFNULL(v.ventas, 0)

      WHERE c.borrado = 0
        AND c.estado  = 1;          -- sólo cajas abiertas
    ";

    $filas = db()->exec($sql);
    echo "Se cerraron $filas cajas abiertas.\n";

} catch (PDOException $e) {
    echo "Error de BD: " . $e->getMessage() . "\n";
    exit;
}


