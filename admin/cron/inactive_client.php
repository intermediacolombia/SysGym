<?php
require_once __DIR__ . '/../../inc/config.php';

try {
    $hoy = date('Y-m-d');

    $stmtSelect = db()->prepare("
        SELECT *
          FROM clientes
         WHERE borrado = 0
           AND estado  = 'activo'
           AND congelado = 0
           AND DATE(vencimiento_plan) < :hoy
    ");
    $stmtSelect->execute([':hoy'=>$hoy]);
    $clientsToUpdate = $stmtSelect->fetchAll(PDO::FETCH_ASSOC);

    if ($clientsToUpdate) {
        $ids = array_column($clientsToUpdate,'id');
        $placeholders = implode(',', array_fill(0,count($ids),'?'));

        $stmtUpdate = db()->prepare(
            "UPDATE clientes
                SET estado='inactivo', updated_at=NOW()
              WHERE borrado=0 AND id IN ($placeholders)"
        );
        $stmtUpdate->execute($ids);

        echo "Actualizados {$stmtUpdate->rowCount()} clientes.<br>";

        $reminders = array_filter($clientsToUpdate,
            fn($c)=>!empty($c['notificaciones'])
        );

        if ($reminders) {
            include __DIR__.'/../../whatsapp/notify_expired.php';
        } else {
            echo "No hay clientes con notificaciones activadas.";
        }
    } else {
        echo "No hay clientes vencidos antes de hoy ($hoy).";
    }
} catch (PDOException $e){
    echo "Error: ".$e->getMessage();
    exit;
}

?>






