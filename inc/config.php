<?php
date_default_timezone_set('America/Bogota');

# Config base de datos
require_once __DIR__ . '/url_bd.php';

# Definiciones generales
if (!defined('URLBASE'))   define('URLBASE', $url_site);
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));



$url  = URLBASE;
$hoy  = date('Y-m-d');
$hora = date('H:i:s');

try {
    // Conexión a la base de datos usando PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
    $pdo->exec("SET NAMES 'utf8mb4'");

    // Nueva consulta: obtener todas las configuraciones (nombre → valor)
    $stmt = $pdo->query("SELECT setting_name, value FROM system_settings WHERE enabled = 1");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_name']] = $row['value'];
    }

    // === Asignación directa a las variables existentes ===
    $api_ws                 = $settings['wa_api'] ?? '';
    $wa_consent             = $settings['wa_consent'] ?? '';
    $wa_client_pay          = $settings['wa_client_pay'] ?? '';
    $wa_client_pay_general  = $settings['wa_client_pay_general'] ?? '';
    $wa_hbd                 = $settings['wa_hbd'] ?? '';
    $wa_notify_expired      = $settings['wa_notify_expired'] ?? '';
    $wa_paymentReminder     = $settings['wa_paymentReminder'] ?? '';
    $wa_valoracion          = $settings['wa_valoracion'] ?? '';
    $wa_creditReminder      = $settings['wa_creditReminder'] ?? '';
    $wa_creditReminder_day  = $settings['wa_creditReminder_day'] ?? '';
    $wa_creditReminder_hour = $settings['wa_creditReminder_hour'] ?? '';
	$wa_consent_pending		= $settings['wa_consent_pending'] ?? ''; 
	
	
	define('CONSENT', $settings['wa_consent_html']);
	define('SITE_LOGO', '/admin/uploads/'.$settings['system_logo'].'') ;
	define('SITE_ICON', '/admin/uploads/'.$settings['system_favicon'].'') ;
	define('SITE_ICON', '/admin/uploads/'.$settings['system_favicon'].'') ;
	
	define('SYSTEM_COLOR_PRIMARY', $settings['system_color_primary'] ?? '#000');
	define('SYSTEM_COLOR_SECONDARY', $settings['system_color_secondary'] ?? '#000');
	define('SYSTEM_COLOR_PRIMARY_DARK', $settings['system_color_primary_dark'] ?? '#000');
	define('SYSTEM_COLOR_SECONDARY_DARK', $settings['system_color_secondary_dark'] ?? '#000');
	
	/* ===== MERCADO PAGO CONFIG ===== */
	define('MP_ACCESS_TOKEN', 'APP_USR-7898771052924983-110621-286a6d387e40342f363f0e7132a7b9a6-2972976646');
	define('MP_PUBLIC_KEY',  'APP_USR-8cbe21e1-3aad-4e8e-9caf-d79d8eaf71e8');


} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}
?>
