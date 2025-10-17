<?php
date_default_timezone_set('America/Bogota');
#Config database
if (!isset($host))   $host   = 'localhost';
if (!isset($dbname)) $dbname = 'intermed_sysgym';
if (!isset($dbuser)) $dbuser = 'intermed_sysgym';
if (!isset($dbpass)) $dbpass = 'OGFq%V?F,a!lFF!&';

#whatsapp
//$api_ws = '6RCr52QDJehDjsNVoFmXYyORu3lOF2eeoPW';
//$api_ws = '8veaR5zoXDZYkBRsHfo1jc7xitzKI9Fjy0U';


if (!defined('URLBASE'))   define('URLBASE', 'https://sysgym.intermediacolombia.com');

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

define('SITE_LOGO', '/admin/images/logo-black.png');

$url = URLBASE;

$hoy = date('Y-m-d');
$hora = date("H:i:s");


try {
    // Conexión a la base de datos usando PDO
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
$pdo = new PDO($dsn, $dbuser, $dbpass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec("SET NAMES 'utf8mb4'");
    // Consulta de la columna wa_consent
    $query = "SELECT * FROM system_settings";
    $stmt = $pdo->query($query);

    // Suponiendo que la consulta retorna solo un registro:
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
		
		$api_ws = $row['wa_api'];
        $wa_consent = $row['wa_consent'];
		$wa_client_pay = $row['wa_client_pay'];
		$wa_client_pay_general = $row['wa_client_pay_general'];
		$wa_hbd = $row['wa_hbd'];
		$wa_notify_expired = $row['wa_notify_expired'];
		$wa_paymentReminder =$row['wa_paymentReminder'];
		$wa_valoracion =$row['wa_valoracion'];
		$wa_creditReminder =$row['wa_creditReminder'];
		$wa_creditReminder_day =$row['wa_creditReminder_day'];
		$wa_creditReminder_hour =$row['wa_creditReminder_hour'];
		
		
		//echo $wa_hbd;
        
    } else {
        echo " ";
    }
} catch (PDOException $e) {
    echo "Error de conexión: " . $e->getMessage();
}


?>