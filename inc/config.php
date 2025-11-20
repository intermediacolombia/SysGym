<?php
date_default_timezone_set('America/Bogota');

# ===============================
#  BASE DE DATOS
# ===============================
require_once __DIR__ . '/url_bd.php';

# ===============================
#  DEFINICIONES GENERALES
# ===============================
if (!defined('URLBASE'))   define('URLBASE', $url_site);
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

$url  = URLBASE;
$hoy  = date('Y-m-d');
$hora = date('H:i:s');

# ============================================================
#  FUNCIÓN GLOBAL DE CONEXIÓN PDO (REUTILIZA UNA SOLA CONEXIÓN)
# ============================================================

function db() {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    try {

        // Extraer variables desde config
        $host   = $GLOBALS['host'];
        $dbname = $GLOBALS['dbname'];
        $dbuser = $GLOBALS['dbuser'];
        $dbpass = $GLOBALS['dbpass'];

        $pdo = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $dbuser,
            $dbpass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );

        return $pdo;

    } catch (PDOException $e) {

        // Detectar si es AJAX o API
        $isAjax =
            (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) &&
             strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            ||
            (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json'));

        if ($isAjax) {
            header('Content-Type: application/json');
            echo json_encode([
                'status'  => 'error',
                'message' => 'Error de conexión a la base de datos.',
                'code'    => $e->getCode()
            ]);
            exit;
        }

        // Página normal HTML
        die("Error de conexión. Intenta más tarde.");
    }
}


function fechaBonita($fecha) {
    if (empty($fecha)) return 'No registrado';

    try {
        $dt = new DateTime($fecha);
    } catch (Exception $e) {
        return 'Fecha inválida';
    }

    $fmt = new IntlDateFormatter(
        'es_ES',
        IntlDateFormatter::LONG,
        IntlDateFormatter::NONE,
        'America/Bogota',
        IntlDateFormatter::GREGORIAN,
        "d 'de' MMMM 'de' yyyy"
    );

    $texto = $fmt->format($dt);

    // Convertir la primera letra a mayúscula
    return ucfirst($texto);
}


# ===============================
#  CARGAR SETTINGS DEL SISTEMA
# ===============================
try {
    $stmt = db()->query("SELECT setting_name, value FROM system_settings WHERE enabled = 1");
    $settings = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_name']] = $row['value'];
    }
} catch (PDOException $e) {
    die("Error cargando configuración: " . $e->getMessage());
}


# ============================================================
#  CONTROL DINÁMICO DE ERRORES SEGÚN LA CONFIGURACIÓN
# ============================================================
$debug = isset($settings['debug_mode']) && $settings['debug_mode'] == 1;

if ($debug) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}


# ===============================
#  VARIABLES DE CONFIGURACIÓN
# ===============================
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
$wa_consent_pending     = $settings['wa_consent_pending'] ?? '';

# ===============================
#  CONSTANTES GENERALES
# ===============================

define('CONSENT', $settings['wa_consent_html'] ?? '');

define('NAME_GYM', $settings['name_gym'] ?? '');
define('EMAIL_GYM', $settings['email_gym'] ?? '');
define('NIT_GYM', $settings['nit_gym'] ?? '');
define('TEL_GYM', $settings['tel_gym'] ?? '');
define('SITE_LOGO', '/admin/uploads/' . ($settings['system_logo'] ?? ''));
define('SITE_ICON', '/admin/uploads/' . ($settings['system_favicon'] ?? ''));

# ===============================
#  SMTP
# ===============================
define('SMTP_USERNAME',   $settings['smtp_username']   ?? '');
define('SMTP_HOST',       $settings['smtp_host']       ?? '');
define('SMTP_PASSWORD',   $settings['smtp_password']   ?? '');
define('SMTP_PORT',       $settings['smtp_port']       ?? '');

define('SMTP_FROM',       $settings['smtp_from']       ?? '');
define('SMTP_FROM_NAME',  $settings['smtp_from_name']  ?? '');

# ===============================
#  COLORES DEL SISTEMA
# ===============================
define('SYSTEM_COLOR_PRIMARY',        $settings['system_color_primary']        ?? '#000');
define('SYSTEM_COLOR_SECONDARY',      $settings['system_color_secondary']      ?? '#000');
define('SYSTEM_COLOR_PRIMARY_DARK',   $settings['system_color_primary_dark']   ?? '#000');
define('SYSTEM_COLOR_SECONDARY_DARK', $settings['system_color_secondary_dark'] ?? '#000');

# ===============================
#  MERCADO PAGO
# ===============================
define('ADDITIONAL_PERCENTAGE_PAYMENT', (float)($settings['additional_percentage_payment'] ?? 0));
define('MP_ACCESS_TOKEN', $settings['mp_access_token'] ?? '');
define('MP_PUBLIC_KEY',   $settings['mp_public_key']   ?? '');
define('DAYS_ALLOWED_BEFORE_DUE', (int)($settings['days_allowed_before_due'] ?? 0));

?>

