<?php
require_once __DIR__ . '/../../inc/config.php';

date_default_timezone_set('America/Bogota');

$hora    = date('H:i');
$minutos = (int) date('i');

switch (true) {
    case ($hora === '00:00'): // tareas de medianoche
        include 'closed_caja.php'; // Cerrar cajas abiertas
        include 'bk.php';          // Backup BD
        break;

    case ($hora === '05:00'): // 05:00 → inactivar clientes vencidos
        include 'inactive_client.php';
        break;

    case ($hora === '08:00'): // 08:00 → recordatorios + cumpleaños
        include 'reminderMain.php';
        include 'birthday.php';
        break;
		
	case ($hora === '12:00'): // 12:00 → Backup Sistema
        include 'bk.php';
        break;

    case ($hora === $wa_creditReminder_hour && date('N') == $wa_creditReminder_day):
        include 'creditReminder.php';
        break;

    case ($minutos % 5 === 0): // cada 5 minutos → Intento envio mensajes de WS
    include 'try_ws.php';
    
    // Envío masivo con probabilidad del 25% (aprox cada 20 min)
    // Ajusta el número: rand(1,4)=25%, rand(1,3)=33%, rand(1,5)=20%
    if (rand(1, 4) === 1) {
        include 'send_massive_pending.php';
    }
    break;

    default:
        echo "$hora No es la hora programada.";
}





