<?php
/**
 * ============================================================
 *  WEBHOOK WHATSAPP — SYSGYM
 *  Integración con API de mensajería intermediahost
 * ============================================================
 */

// ── Cargar configuración del sistema (BD, settings, constantes) ──
require_once __DIR__ . '/../inc/config.php';

// ── Configuración del webhook ────────────────────────────────────
define('API_KEY', $api_ws); // viene de config.php → $settings['wa_api']
define('API_URL', rtrim(WA_API_URL, '/') . '/send');
define('LOG_FILE',  __DIR__ . '/webhook-ws.log');

// ── Horarios del gimnasio (edita aquí) ───────────────────────────
define('HORARIOS_GYM',
    "🕐 *Horarios de atención:*\n\n" .
    "Lunes a Viernes:  5:00 am – 10:00 pm\n" .
    "Sábados:          6:00 am –  8:00 pm\n" .
    "Domingos:         8:00 am –  2:00 pm\n\n" .
    "📍 " . NAME_GYM
);

// ── Tiempos de sesión ────────────────────────────────────────────
define('MENU_TIMEOUT_SECS',   5 * 60);       // 5 minutos sin actividad
define('ASESOR_TIMEOUT_SECS', 2 * 60 * 60);  // 2 horas con asesor

// ── Archivo de estados de conversación ──────────────────────────
define('ESTADOS_FILE', __DIR__ . '/estados_ws.json');


// ════════════════════════════════════════════════════════════════
//  UTILIDADES
// ════════════════════════════════════════════════════════════════

function wlog($msg) {
    $ts = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$ts] $msg\n", FILE_APPEND);
}

function wsSend($telefono, $mensaje) {
    $ch = curl_init(API_URL);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . API_KEY,
            'Content-Type: application/json',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'phonenumber' => $telefono,
            'text'        => $mensaje,
        ], JSON_UNESCAPED_UNICODE),
    ]);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $success = false;
    if ($code >= 200 && $code < 300) {
        $decoded = json_decode($response, true);
        $success = !empty($decoded['success']);
    }

    wlog("Enviado a $telefono [HTTP $code] success=" . ($success ? 'true' : 'false') . ": " . mb_substr($mensaje, 0, 80));
    return $success;
}


// ════════════════════════════════════════════════════════════════
//  GESTIÓN DE ESTADOS — operaciones directas sobre el JSON
// ════════════════════════════════════════════════════════════════

function estadosCargar() {
    if (!file_exists(ESTADOS_FILE)) return [];
    $d = json_decode(file_get_contents(ESTADOS_FILE), true);
    return is_array($d) ? $d : [];
}

function estadosGuardar(array $estados) {
    file_put_contents(ESTADOS_FILE, json_encode($estados, JSON_PRETTY_PRINT));
}

/** Escribe o borra el estado de un usuario. $estado=null → elimina */
function estadoGuardar($key, $estado, array $data = []) {
    $estados = estadosCargar();
    if ($estado === null) {
        unset($estados[$key]);
    } else {
        $estados[$key] = [
            'estado'    => $estado,
            'data'      => $data,
            'timestamp' => time(),
        ];
    }
    estadosGuardar($estados);
}

/**
 * Devuelve el estado activo o null si no existe / expiró.
 * Estado 'asesor'           → expira en ASESOR_TIMEOUT_SECS
 * Estados 'espera_doc_*'   → expiran en MENU_TIMEOUT_SECS * 2
 * Resto                    → expiran en MENU_TIMEOUT_SECS
 */
function estadoActivo($key) {
    $estados = estadosCargar();
    if (!isset($estados[$key])) return null;

    $e       = $estados[$key];
    $elapsed = time() - ($e['timestamp'] ?? 0);

    if ($e['estado'] === 'asesor') {
        if ($elapsed > ASESOR_TIMEOUT_SECS) {
            unset($estados[$key]);
            estadosGuardar($estados);
            return null;
        }
        return $e;
    }

    if (in_array($e['estado'], ['espera_doc_plan', 'espera_doc_pago'])) {
        if ($elapsed > MENU_TIMEOUT_SECS * 2) {
            unset($estados[$key]);
            estadosGuardar($estados);
            return null;
        }
        return $e;
    }

    // menu_principal y cualquier otro
    if ($elapsed > MENU_TIMEOUT_SECS) {
        unset($estados[$key]);
        estadosGuardar($estados);
        return null;
    }

    return $e;
}

/**
 * Resetear sesión completamente: borra el JSON y devuelve el menú.
 * Luego guarda estado menu_principal fresco.
 */
function resetearSesion($key, $nombre) {
    // Borrar directamente del JSON
    $estados = estadosCargar();
    unset($estados[$key]);
    estadosGuardar($estados);

    // Guardar estado nuevo limpio
    $estados[$key] = [
        'estado'    => 'menu_principal',
        'data'      => [],
        'timestamp' => time(),
    ];
    estadosGuardar($estados);

    return menuPrincipal($nombre);
}


// ════════════════════════════════════════════════════════════════
//  CONSULTAS A BD
// ════════════════════════════════════════════════════════════════

function planesDisponibles() {
    try {
        $stmt = db()->query(
            "SELECT nombre, precio, frecuencia
               FROM planes
              WHERE estado = 'activo' AND borrado = 0
              ORDER BY precio ASC"
        );
        $planes = $stmt->fetchAll();

        if (empty($planes)) {
            return "⚠️ No hay planes disponibles en este momento.";
        }

        $txt = "💪 *PLANES DISPONIBLES — " . NAME_GYM . "*\n\n";
        foreach ($planes as $p) {
            $precio = '$' . number_format($p['precio'], 0, ',', '.');
            if ($p['frecuencia'] == 1)       $periodo = "Mensual";
            elseif ($p['frecuencia'] == 12)  $periodo = "Anual";
            else                             $periodo = $p['frecuencia'] . " mes(es)";

            $txt .= "▸ *{$p['nombre']}*\n";
            $txt .= "  💰 {$precio}  |  📅 {$periodo}\n\n";
        }

        $txt .= "Escribe *0* para volver al menú.";
        return $txt;
    } catch (Exception $e) {
        wlog("ERROR planesDisponibles: " . $e->getMessage());
        return "⚠️ No fue posible cargar los planes. Intenta más tarde.";
    }
}

function consultarPlanCliente($documento) {
    try {
        $stmt = db()->prepare(
            "SELECT c.nombres, c.apellidos, c.vencimiento_plan, c.congelado,
                    p.nombre AS plan_nombre, p.precio AS plan_precio
               FROM clientes c
          LEFT JOIN planes p ON p.id = c.plan
              WHERE c.identificacion = :doc AND c.borrado = 0
              LIMIT 1"
        );
        $stmt->execute([':doc' => $documento]);
        $c = $stmt->fetch();

        if (!$c) {
            return "❌ No encontré ningún cliente con el documento *{$documento}*.\n\nVerifica que sea correcto y vuelve a intentarlo.";
        }

        $nombre    = trim($c['nombres'] . ' ' . $c['apellidos']);
        $hoy       = new DateTime(date('Y-m-d'));
        $vencim    = new DateTime($c['vencimiento_plan']);
        $diff      = (int)$hoy->diff($vencim)->format('%r%a');
        $vencimTxt = $vencim->format('d/m/Y');

        if ($c['congelado'])       $estadoTxt = "🧊 *CONGELADO*";
        elseif ($diff < 0)         $estadoTxt = "🔴 *VENCIDO* hace " . abs($diff) . " día(s)";
        elseif ($diff === 0)       $estadoTxt = "🟡 *Vence HOY*";
        elseif ($diff <= 5)        $estadoTxt = "🟡 *Vence pronto* — quedan {$diff} día(s)";
        else                       $estadoTxt = "🟢 *ACTIVO* — quedan {$diff} día(s)";

        $planNombre = $c['plan_nombre'] ?? 'Sin plan asignado';
        $planPrecio = $c['plan_precio'] ? '$' . number_format($c['plan_precio'], 0, ',', '.') : '—';

        return
            "👤 *{$nombre}*\n\n" .
            "📋 Plan: *{$planNombre}*\n" .
            "💰 Valor: {$planPrecio}\n" .
            "📅 Vencimiento: {$vencimTxt}\n" .
            "Estado: {$estadoTxt}\n\n" .
            "Escribe *0* para volver al menú principal.";

    } catch (Exception $e) {
        wlog("ERROR consultarPlanCliente: " . $e->getMessage());
        return "⚠️ Error al consultar. Intenta más tarde.";
    }
}

function gestionarPago($documento) {
    try {
        $stmt = db()->prepare(
            "SELECT c.nombres, c.apellidos, c.vencimiento_plan, c.congelado
               FROM clientes c
              WHERE c.identificacion = :doc AND c.borrado = 0
              LIMIT 1"
        );
        $stmt->execute([':doc' => $documento]);
        $c = $stmt->fetch();

        if (!$c) {
            return "❌ No encontré ningún cliente con el documento *{$documento}*.\n\nVerifica que sea correcto e intenta de nuevo.";
        }

        if ($c['congelado']) {
            return "🧊 Tu membresía está *congelada*. Comunícate con nosotros para reactivarla.";
        }

        $hoy    = new DateTime(date('Y-m-d'));
        $vencim = new DateTime($c['vencimiento_plan']);
        $diff   = (int)$hoy->diff($vencim)->format('%r%a'); // negativo = ya venció

        $diasPermitidos = DAYS_ALLOWED_BEFORE_DUE;
        $nombre         = trim($c['nombres'] . ' ' . $c['apellidos']);
        $vencimTxt      = $vencim->format('d/m/Y');

        // Puede pagar si: ya venció ($diff < 0) O faltan <= $diasPermitidos días
        if ($diff <= $diasPermitidos) {
            $linkPago = "https://sysgym.intermediacolombia.com/pay/?doc={$documento}";
            return
                "✅ *{$nombre}*, aquí tienes tu enlace de pago:\n\n" .
                "🔗 {$linkPago}\n\n" .
                "📅 Tu plan vence el *{$vencimTxt}*.\n\n" .
                "_El pago es seguro y en línea._\n\n" .
                "Escribe *0* para volver al menú.";
        } else {
            $disponibleEn = $diff - $diasPermitidos;
            return
                "⏳ *{$nombre}*, aún no está disponible el pago.\n\n" .
                "📅 Tu plan vence el *{$vencimTxt}* (faltan *{$diff}* días).\n\n" .
                "El pago se habilita *{$diasPermitidos} día(s) antes* del vencimiento.\n" .
                "Podrás pagar en aproximadamente *{$disponibleEn} día(s)*.\n\n" .
                "Escribe *0* para volver al menú.";
        }

    } catch (Exception $e) {
        wlog("ERROR gestionarPago: " . $e->getMessage());
        return "⚠️ Error al procesar. Intenta más tarde.";
    }
}


// ════════════════════════════════════════════════════════════════
//  MENÚS
// ════════════════════════════════════════════════════════════════

function menuPrincipal($nombre = '') {
    $saludo = $nombre ? "Hola *{$nombre}*! 👋\n\n" : "👋 Bienvenido!\n\n";
    return
        $saludo .
        "🏋️ *" . NAME_GYM . "*\n\n" .
        "¿En qué te podemos ayudar?\n\n" .
        "1️⃣  Ver Planes\n" .
        "2️⃣  Horarios\n" .
        "3️⃣  Consultar mi Plan\n" .
        "4️⃣  Realizar Pago\n" .
        "5️⃣  Hablar con un Asesor\n\n" .
        "_Escribe solo el número de la opción._";
}


// ════════════════════════════════════════════════════════════════
//  ENTRY POINT
// ════════════════════════════════════════════════════════════════

$rawInput = file_get_contents('php://input');
wlog("RECIBIDO: " . $rawInput);

$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
}

$telefono  = isset($data['from'])      ? trim($data['from'])  : '';
$mensaje   = isset($data['message'])   ? trim($data['message']) : '';
$nombre    = isset($data['pushName'])  ? $data['pushName']    : '';
$clientId  = isset($data['client_id']) ? $data['client_id']   : 'default';

if (empty($telefono) || empty($mensaje)) {
    http_response_code(200);
    exit('OK');
}

$sesKey       = $telefono . '_' . $clientId;
$mensajeLower = strtolower($mensaje);

wlog("[$clientId] De: $telefono ($nombre) → \"$mensaje\"");

// ── Obtener estado actual ─────────────────────────────────────
$sesData = estadoActivo($sesKey);
$estado  = $sesData ? $sesData['estado'] : null;

wlog("[$clientId] Estado actual: " . ($estado ?? 'ninguno'));

$respuesta = null;

// ── 1. Bot silenciado por asesor ──────────────────────────────
if ($estado === 'asesor') {
    wlog("[$clientId] En espera de asesor — bot silenciado");
    http_response_code(200);
    exit('OK');
}

// ── 2. Detección de agente humano respondiendo ────────────────
if (preg_match('/\b(te atiendo|en que puedo ayudarte|cuentame|dime|hola soy|te ayudo|un momento|ya te atiendo)\b/i', $mensajeLower)) {
    estadoGuardar($sesKey, 'asesor', ['agente' => true]);
    wlog("[$clientId] Humano detectado — sesión marcada como asesor");
    http_response_code(200);
    exit('OK');
}

// ── 3. Comando 0 o palabras de bienvenida → RESET SIEMPRE ─────
$esReset  = ($mensaje === '0');
$esSaludo = (bool)preg_match('/\b(hola|hi|buenas|buenos dias|buenas tardes|buenas noches|menu|inicio|start)\b/i', $mensajeLower);

if ($esReset || $esSaludo) {
    wlog("[$clientId] Reset de sesión por: \"$mensaje\"");
    $respuesta = resetearSesion($sesKey, $nombre);

// ── 4. Opciones del menú principal ───────────────────────────
} elseif ($estado === 'menu_principal') {

    switch ($mensaje) {

        case '1':
            $respuesta = planesDisponibles();
            estadoGuardar($sesKey, 'menu_principal');
            break;

        case '2':
            $respuesta = HORARIOS_GYM . "\n\nEscribe *0* para volver al menú.";
            estadoGuardar($sesKey, 'menu_principal');
            break;

        case '3':
            $respuesta =
                "🔍 *Consultar mi Plan*\n\n" .
                "Por favor envíame tu *número de documento*.\n\n" .
                "⚠️ Sin espacios, sin puntos, sin comas.\n" .
                "_Ejemplo: 1094914578_\n\n" .
                "Escribe *0* para cancelar.";
            estadoGuardar($sesKey, 'espera_doc_plan');
            break;

        case '4':
            $respuesta =
                "💳 *Realizar Pago*\n\n" .
                "Por favor envíame tu *número de documento*.\n\n" .
                "⚠️ Sin espacios, sin puntos, sin comas.\n" .
                "_Ejemplo: 1094914578_\n\n" .
                "Escribe *0* para cancelar.";
            estadoGuardar($sesKey, 'espera_doc_pago');
            break;

        case '5':
            $respuesta =
                "🧑‍💼 *Conectando con un asesor...*\n\n" .
                "En un momento alguien del equipo de *" . NAME_GYM . "* te atenderá.\n\n" .
                "📞 También puedes llamarnos al: " . TEL_GYM . "\n\n" .
                "_Por favor espera, no es necesario escribir más._";
            estadoGuardar($sesKey, 'asesor', ['solicitado' => time()]);
            wlog("[$clientId] ASESOR SOLICITADO: $nombre ($telefono)");
            break;

        default:
            $respuesta = "⚠️ Opción no válida.\n\n" . menuPrincipal($nombre);
            estadoGuardar($sesKey, 'menu_principal');
            break;
    }

// ── 5. Esperando documento para consultar plan ────────────────
} elseif ($estado === 'espera_doc_plan') {

    if (preg_match('/^\d{5,15}$/', $mensaje)) {
        $respuesta = consultarPlanCliente($mensaje);
        wlog("[$clientId] CONSULTA PLAN: $nombre ($telefono) doc=$mensaje");
        estadoGuardar($sesKey, 'menu_principal');
    } else {
        $respuesta =
            "⚠️ Documento inválido.\n\n" .
            "Envía *solo números*, sin espacios ni caracteres especiales.\n" .
            "_Ejemplo: 1094914578_\n\n" .
            "Escribe *0* para cancelar.";
    }

// ── 6. Esperando documento para pago ─────────────────────────
} elseif ($estado === 'espera_doc_pago') {

    if (preg_match('/^\d{5,15}$/', $mensaje)) {
        $respuesta = gestionarPago($mensaje);
        wlog("[$clientId] SOLICITUD PAGO: $nombre ($telefono) doc=$mensaje");
        estadoGuardar($sesKey, 'menu_principal');
    } else {
        $respuesta =
            "⚠️ Documento inválido.\n\n" .
            "Envía *solo números*, sin espacios ni caracteres especiales.\n" .
            "_Ejemplo: 1094914578_\n\n" .
            "Escribe *0* para cancelar.";
    }

// ── 7. Sin estado (primera vez o expirado) ────────────────────
} else {
    wlog("[$clientId] Sin estado válido — mostrando menú inicial");
    $respuesta = resetearSesion($sesKey, $nombre);
}

// ── Enviar respuesta ──────────────────────────────────────────
if ($respuesta) {
    if (!wsSend($telefono, $respuesta)) {
        wlog("[$clientId] ERROR al enviar a $telefono | API_KEY=" . API_KEY . " | URL=" . API_URL);
    }
}

http_response_code(200);
echo 'OK';