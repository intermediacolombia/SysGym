<?php
require_once __DIR__ . '/../login/session.php';
//include('../login/restriction.php');


// Indicamos que la respuesta será JSON
header('Content-Type: application/json');

try {
    // Conexión a la base de datos usando PDO con utf8mb4 para soportar emojis
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES 'utf8mb4'");
    
    // Consulta preparada para actualizar la configuración
    $sql = "UPDATE system_settings 
            SET 
                wa_api = :wa_api,
                wa_consent = :wa_consent,
                wa_client_pay = :wa_client_pay,
                wa_client_pay_general = :wa_client_pay_general,
                wa_hbd = :wa_hbd,
                wa_notify_expired = :wa_notify_expired,
                wa_paymentReminder = :wa_paymentReminder,
                wa_valoracion = :wa_valoracion,
                wa_creditReminder = :wa_creditReminder,
                wa_creditReminder_day = :wa_creditReminder_day,
                wa_creditReminder_hour = :wa_creditReminder_hour
            LIMIT 1";
    $stmt = $pdo->prepare($sql);
    
    // Vincular los parámetros
    $stmt->bindParam(':wa_api', $_POST['wa_api'], PDO::PARAM_STR);
    $stmt->bindParam(':wa_consent', $_POST['wa_consent'], PDO::PARAM_STR);
    $stmt->bindParam(':wa_client_pay', $_POST['wa_client_pay'], PDO::PARAM_STR);
    $stmt->bindParam(':wa_client_pay_general', $_POST['wa_client_pay_general'], PDO::PARAM_STR);
    $stmt->bindParam(':wa_hbd', $_POST['wa_hbd'], PDO::PARAM_STR);
    $stmt->bindParam(':wa_notify_expired', $_POST['wa_notify_expired'], PDO::PARAM_STR);
    $stmt->bindParam(':wa_paymentReminder', $_POST['wa_paymentReminder'], PDO::PARAM_STR);
    $stmt->bindParam(':wa_valoracion', $_POST['wa_valoracion'], PDO::PARAM_STR);
    $stmt->bindParam(':wa_creditReminder', $_POST['wa_creditReminder'], PDO::PARAM_STR);
    $stmt->bindParam(':wa_creditReminder_day', $_POST['wa_creditReminder_day'], PDO::PARAM_STR);
    $stmt->bindParam(':wa_creditReminder_hour', $_POST['wa_creditReminder_hour'], PDO::PARAM_STR);
    
    $stmt->execute();
	
	// LOGS
require_once __DIR__ . '/../inc/log_action.php';
$desc = json_encode([
			'accion' => 'Actualizo COnfiguraciones del sistema'			
], JSON_UNESCAPED_UNICODE);
log_action('Actualizar Configuraciones', $desc, 'Configuraciones');
// END LOGS
    
    echo json_encode([
         "success" => true,
         "message" => "La configuración ha sido actualizada exitosamente."
    ]);
    exit;
    
} catch (PDOException $e) {
    echo json_encode([
         "success" => false,
         "message" => "Error al guardar la configuración: " . $e->getMessage()
    ]);
    exit;
}
?>