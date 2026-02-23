<?php
/**
 * ============================================================
 *  WEBHOOK WHATSAPP — SYSGYM
 * ============================================================
 */
require_once __DIR__ . '/../inc/config.php';

defined('API_KEY')            || define('API_KEY',            $api_ws);
defined('API_URL')            || define('API_URL',            rtrim(WA_API_URL, '/') . '/send');
defined('LOG_FILE')           || define('LOG_FILE',           __DIR__ . '/webhook-ws.log');
defined('ESTADOS_FILE')       || define('ESTADOS_FILE',       __DIR__ . '/estados_ws.json');
defined('MENU_TIMEOUT_SECS')  || define('MENU_TIMEOUT_SECS',  5 * 60);
defined('ASESOR_TIMEOUT_SECS')|| define('ASESOR_TIMEOUT_SECS',2 * 60 * 60);

defined('HORARIOS_GYM') || define('HORARIOS_GYM',
    "🕐 *Horarios de atención:*\n\n" .
    "Lunes a Viernes:  5:00 am – 10:00 pm\n" .
    "Sábados:          6:00 am –  8:00 pm\n" .
    "Domingos:         8:00 am –  2:00 pm\n\n" .
    "📍 " . NAME_GYM . "\n\n" .
    "¡Te esperamos para ayudarte a alcanzar tus metas! 💪"
);

// ════════════════════════════════════════════════════════════════
//  UTILIDADES
// ════════════════════════════════════════════════════════════════
function wlog($msg) {
    file_put_contents(LOG_FILE, '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

function esReset($mensaje, $mensajeLower) {
    return preg_match('/^\s*(0|cancelar|menu|menú|inicio|volver)\s*$/i', $mensaje)
        || preg_match('/\b(hola|hi|buenas|buenos dias|buenas tardes|buenas noches|start)\b/i', $mensajeLower);
}

// ════════════════════════════════════════════════════════════════
//  ENVÍO WS
// ════════════════════════════════════════════════════════════════
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
    wlog("wsSend $telefono HTTP=$code success=" . ($success ? 'SI' : 'NO') . " msg=" . mb_substr($mensaje, 0, 60));
    return $success;
}

// ════════════════════════════════════════════════════════════════
//  ESTADOS
// ════════════════════════════════════════════════════════════════
function jsonLeer() {
    if (!file_exists(ESTADOS_FILE)) return [];
    $arr = json_decode(file_get_contents(ESTADOS_FILE), true);
    return is_array($arr) ? $arr : [];
}

function jsonEscribir(array $arr) {
    file_put_contents(ESTADOS_FILE, json_encode($arr, JSON_PRETTY_PRINT));
}

function obtenerEstado($key) {
    $arr = jsonLeer();
    if (!isset($arr[$key])) return null;

    $e       = $arr[$key];
    $elapsed = time() - intval($e['timestamp']);
    $limite  = $e['estado'] === 'asesor'
        ? ASESOR_TIMEOUT_SECS
        : (in_array($e['estado'], ['espera_doc_plan','espera_doc_pago'])
            ? MENU_TIMEOUT_SECS * 2
            : MENU_TIMEOUT_SECS);

    if ($elapsed > $limite) {
        unset($arr[$key]);
        jsonEscribir($arr);
        wlog("Estado expirado: $key");
        return null;
    }
    return $e;
}

function guardarEstado($key, $nuevoEstado, array $data = []) {
    $arr = jsonLeer();
    if ($nuevoEstado === null) {
        unset($arr[$key]);
        wlog("Estado borrado: $key");
    } else {
        $arr[$key] = ['estado' => $nuevoEstado, 'data' => $data, 'timestamp' => time()];
        wlog("Estado: $key → $nuevoEstado");
    }
    jsonEscribir($arr);
}

function resetMenu($key, $nombre) {
    $arr         = jsonLeer();
    $arr[$key]   = ['estado' => 'menu_principal', 'data' => [], 'timestamp' => time()];
    jsonEscribir($arr);
    wlog("RESET menu_principal: $key");
    return menuPrincipal($nombre);
}

// ════════════════════════════════════════════════════════════════
//  MENÚ PRINCIPAL
// ════════════════════════════════════════════════════════════════
function menuPrincipal($nombre = '') {
    $saludo = $nombre ? "¡Hola *{$nombre}*! 👋 Bienvenido a *" . NAME_GYM . "*\n\n" : "👋 ¡Bienvenido a *" . NAME_GYM . "*!\n\n";
    return $saludo .
        "Estamos aquí para ayudarte a lograr tus metas. ¿En qué te podemos ayudar hoy?\n\n" .
        "1️⃣  Ver Planes\n" .
        "2️⃣  Horarios\n" .
        "3️⃣  Consultar mi Plan\n" .
        "4️⃣  Realizar Pago\n" .
        "5️⃣  Hablar con un Asesor\n\n" .
        "_Escribe el número de la opción que deseas._";
}

// ════════════════════════════════════════════════════════════════
//  CONSULTAS BD
// ════════════════════════════════════════════════════════════════
function planesDisponibles() {
    try {
        $rows = db()->query(
            "SELECT nombre, precio, frecuencia FROM planes
              WHERE estado='activo' AND borrado=0 AND precio > 0
              ORDER BY precio ASC"
        )->fetchAll();

        if (empty($rows)) return "⚠️ No hay planes disponibles en este momento. Escríbenos *Menú* para volver.";

        $txt = "💪 *NUESTROS PLANES — " . NAME_GYM . "*\n\n";
        foreach ($rows as $p) {
            $precio  = '$' . number_format($p['precio'], 0, ',', '.');
            $periodo = $p['frecuencia'] == 1 ? 'Mensual' : ($p['frecuencia'] == 12 ? 'Anual' : $p['frecuencia'] . ' mes(es)');
            $txt .= "▸ *{$p['nombre']}*\n  💰 {$precio}  |  📅 {$periodo}\n\n";
        }

        $txt .= "¿Listo para dar el primer paso? 🏆\n";
        $txt .= "Escribe *5* para hablar con un asesor o *Menú* para volver.";
        return $txt;

    } catch (Exception $ex) {
        wlog("ERROR planesDisponibles: " . $ex->getMessage());
        return "⚠️ No fue posible cargar los planes en este momento. Intenta más tarde.";
    }
}

function consultarPlanCliente($doc) {
    try {
        $st = db()->prepare(
            "SELECT c.nombres, c.apellidos, c.vencimiento_plan, c.congelado,
                    p.nombre AS plan_nombre, p.precio AS plan_precio
               FROM clientes c LEFT JOIN planes p ON p.id = c.plan
              WHERE c.identificacion = :doc AND c.borrado = 0 LIMIT 1"
        );
        $st->execute([':doc' => $doc]);
        $c = $st->fetch();

        if (!$c) {
            return
                "❌ No encontramos ningún cliente con el documento *{$doc}*.\n\n" .
                "Verifica que sea correcto e inténtalo de nuevo, o escribe *Menú* para volver.\n\n" .
                "Si aún no eres miembro, ¡es el momento perfecto para unirte! 💪";
        }

        $nombre = trim($c['nombres'] . ' ' . $c['apellidos']);
        $hoy    = new DateTime(date('Y-m-d'));
        $venc   = new DateTime($c['vencimiento_plan']);
        $diff   = (int)$hoy->diff($venc)->format('%r%a');
        $vTxt   = $venc->format('d/m/Y');

        if ($c['congelado']) {
            $est     = "🧊 *MEMBRESÍA CONGELADA*";
            $consejo = "Contáctanos para reactivar tu plan y retomar tu entrenamiento. 💪";
        } elseif ($diff < 0) {
            $est     = "🔴 *VENCIDA*";
            $consejo = "¡No pierdas tu ritmo! Renueva tu plan y sigue entrenando. 🏃";
        } elseif ($diff === 0) {
            $est     = "🟡 *Vence HOY*";
            $consejo = "Renueva hoy para no perder ni un día de entrenamiento. ⚡";
        } elseif ($diff <= 5) {
            $est     = "🟡 *Vence pronto*";
            $consejo = "¡Renueva pronto y mantén tu racha! 🔥";
        } else {
            $est     = "🟢 *ACTIVA*";
            $consejo = "¡Sigue así, vas muy bien! 💪";
        }

        $pNombre = $c['plan_nombre'] ?? 'Sin plan asignado';
        $pPrecio = $c['plan_precio'] ? '$' . number_format($c['plan_precio'], 0, ',', '.') : '—';

        return
            "👤 *{$nombre}*\n\n" .
            "📋 Plan: *{$pNombre}*\n" .
            "💰 Valor: {$pPrecio}\n" .
            "📅 Vencimiento: {$vTxt}\n" .
            "Estado: {$est}\n\n" .
            "_{$consejo}_\n\n" .
            "Escribe *Menú* para volver al menú principal.";

    } catch (Exception $ex) {
        wlog("ERROR consultarPlanCliente: " . $ex->getMessage());
        return "⚠️ Ocurrió un error al consultar. Por favor intenta más tarde.";
    }
}

function gestionarPago($doc) {
    try {
        $st = db()->prepare(
            "SELECT c.nombres, c.apellidos, c.vencimiento_plan, c.congelado
               FROM clientes c WHERE c.identificacion = :doc AND c.borrado = 0 LIMIT 1"
        );
        $st->execute([':doc' => $doc]);
        $c = $st->fetch();

        if (!$c) {
            return
                "❌ No encontramos ningún cliente con el documento *{$doc}*.\n\n" .
                "Verifica que sea correcto e inténtalo de nuevo, o escribe *Menú* para volver.\n\n" .
                "¿Aún no eres miembro? ¡Escríbenos y te ayudamos a inscribirte! 🏋️";
        }

        if ($c['congelado']) {
            return
                "🧊 Tu membresía está *congelada*.\n\n" .
                "Contáctanos para reactivarla y volver a entrenar. ¡Te esperamos!\n\n" .
                "Escribe *5* para hablar con un asesor.";
        }

        $hoy    = new DateTime(date('Y-m-d'));
        $venc   = new DateTime($c['vencimiento_plan']);
        $diff   = (int)$hoy->diff($venc)->format('%r%a');
        $nombre = trim($c['nombres'] . ' ' . $c['apellidos']);
        $vTxt   = $venc->format('d/m/Y');
        $dias   = DAYS_ALLOWED_BEFORE_DUE;

        if ($diff <= $dias) {
            $link = "https://sysgym.intermediacolombia.com/pay/?doc={$doc}";
            return
                "✅ ¡Hola *{$nombre}*! Tu pago ya está disponible.\n\n" .
                "🔗 *Enlace de pago:*\n{$link}\n\n" .
                "📅 Vencimiento actual: *{$vTxt}*\n\n" .
                "_El proceso es rápido, seguro y en línea._ 🔒\n\n" .
                "¡Gracias por renovar tu compromiso con tu salud! 💪\n\n" .
                "Escribe *Menú* para volver al menú principal.";
        } else {
            $en = $diff - $dias;
            return
                "⏳ ¡Hola *{$nombre}*! Tu pago aún no está habilitado.\n\n" .
                "📅 Tu plan vence el *{$vTxt}*.\n\n" .
                "El sistema habilita el pago *{$dias} día(s) antes* del vencimiento.\n" .
                "Podrás realizar tu pago en aproximadamente *{$en} día(s)*.\n\n" .
                "_Te avisaremos cuando esté disponible._ 😊\n\n" .
                "Escribe *Menú* para volver al menú principal.";
        }

    } catch (Exception $ex) {
        wlog("ERROR gestionarPago: " . $ex->getMessage());
        return "⚠️ Ocurrió un error al procesar. Por favor intenta más tarde.";
    }
}

// ════════════════════════════════════════════════════════════════
//  ENTRADA DEL WEBHOOK
// ════════════════════════════════════════════════════════════════
$rawInput = file_get_contents('php://input');
wlog("RECIBIDO: $rawInput");

$data = json_decode($rawInput, true);
if (!$data) { http_response_code(400); exit('Invalid JSON'); }

$telefono  = trim($data['from']      ?? '');
$mensaje   = trim($data['message']   ?? '');
$nombre    = $data['pushName']        ?? '';
$clientId  = $data['client_id']       ?? 'default';

if (empty($telefono) || empty($mensaje)) { http_response_code(200); exit('OK'); }

$sesKey       = $telefono . '_' . $clientId;
$mensajeLower = mb_strtolower($mensaje);

wlog("[$clientId] $telefono ($nombre) → \"$mensaje\"");

$sesData = obtenerEstado($sesKey);
$estado  = $sesData['estado'] ?? null;
wlog("[$clientId] Estado: " . ($estado ?? 'NINGUNO'));

$respuesta = null;

// ── A. Bot silenciado (asesor activo) ─────────────────────────
if ($estado === 'asesor') {
    wlog("[$clientId] Silenciado — asesor activo");
    http_response_code(200); exit('OK');
}

// ── B. Detección de agente humano respondiendo ────────────────
if (preg_match('/\b(te atiendo|en que puedo ayudarte|cuentame|dime|hola soy|te ayudo|un momento|ya te atiendo)\b/i', $mensajeLower)) {
    guardarEstado($sesKey, 'asesor', ['agente' => true]);
    wlog("[$clientId] Humano detectado");
    http_response_code(200); exit('OK');
}

// ── C. Reset: 0, cancelar, menú, hola, etc. ──────────────────
if (esReset($mensaje, $mensajeLower)) {
    wlog("[$clientId] Reset por: \"$mensaje\"");
    $respuesta = resetMenu($sesKey, $nombre);

// ── D. Menú principal ─────────────────────────────────────────
} elseif ($estado === 'menu_principal') {
    switch ($mensaje) {
        case '1':
            $respuesta = planesDisponibles();
            guardarEstado($sesKey, 'menu_principal');
            break;
        case '2':
            $respuesta = HORARIOS_GYM;
            guardarEstado($sesKey, 'menu_principal');
            break;
        case '3':
            $respuesta =
                "🔍 *Consultar mi Plan*\n\n" .
                "Por favor envíame tu *número de documento* de identidad.\n\n" .
                "⚠️ Solo números, sin espacios, sin puntos, sin comas.\n" .
                "_Ejemplo: 1094914578_\n\n" .
                "Escribe *Cancelar* si deseas volver al menú.";
            guardarEstado($sesKey, 'espera_doc_plan');
            break;
        case '4':
            $respuesta =
                "💳 *Realizar Pago*\n\n" .
                "Por favor envíame tu *número de documento* de identidad.\n\n" .
                "⚠️ Solo números, sin espacios, sin puntos, sin comas.\n" .
                "_Ejemplo: 1094914578_\n\n" .
                "Escribe *Cancelar* si deseas volver al menú.";
            guardarEstado($sesKey, 'espera_doc_pago');
            break;
        case '5':
            $respuesta =
                "🧑‍💼 *¡Conectando con un asesor!*\n\n" .
                "En breve alguien de nuestro equipo en *" . NAME_GYM . "* te atenderá personalmente.\n\n" .
                "📞 También puedes llamarnos al: *" . TEL_GYM . "*\n\n" .
                "_Por favor espera, no es necesario escribir más._ 😊\n\n" .
                "Escribe *Menú* si deseas volver al menú principal.";
            guardarEstado($sesKey, 'asesor', ['solicitado' => time()]);
            wlog("[$clientId] ASESOR SOLICITADO: $nombre ($telefono)");
            break;
        default:
            $respuesta =
                "⚠️ No reconocemos esa opción.\n\n" .
                menuPrincipal($nombre);
            guardarEstado($sesKey, 'menu_principal');
    }

// ── E. Esperando documento — consultar plan ───────────────────
} elseif ($estado === 'espera_doc_plan') {
    if (preg_match('/^\d{5,15}$/', $mensaje)) {
        $respuesta = consultarPlanCliente($mensaje);
        wlog("[$clientId] CONSULTA PLAN doc=$mensaje");
        guardarEstado($sesKey, 'menu_principal');
    } else {
        $respuesta =
            "⚠️ El documento ingresado no es válido.\n\n" .
            "Por favor envía *solo números*, sin espacios ni caracteres especiales.\n" .
            "_Ejemplo: 1094914578_\n\n" .
            "Inténtalo de nuevo o escribe *Cancelar* para volver al menú.";
        // Mantenemos el estado espera_doc_plan para que pueda reintentar
        guardarEstado($sesKey, 'espera_doc_plan');
    }

// ── F. Esperando documento — pago ────────────────────────────
} elseif ($estado === 'espera_doc_pago') {
    if (preg_match('/^\d{5,15}$/', $mensaje)) {
        $respuesta = gestionarPago($mensaje);
        wlog("[$clientId] SOLICITUD PAGO doc=$mensaje");
        guardarEstado($sesKey, 'menu_principal');
    } else {
        $respuesta =
            "⚠️ El documento ingresado no es válido.\n\n" .
            "Por favor envía *solo números*, sin espacios ni caracteres especiales.\n" .
            "_Ejemplo: 1094914578_\n\n" .
            "Inténtalo de nuevo o escribe *Cancelar* para volver al menú.";
        // Mantenemos el estado espera_doc_pago para que pueda reintentar
        guardarEstado($sesKey, 'espera_doc_pago');
    }

// ── G. Sin estado (primera vez o sesión expirada) ─────────────
} else {
    wlog("[$clientId] Sin estado — menú inicial");
    $respuesta = resetMenu($sesKey, $nombre);
}

// ── Enviar ────────────────────────────────────────────────────
if ($respuesta) {
    if (!wsSend($telefono, $respuesta)) {
        wlog("[$clientId] ERROR enviando a $telefono");
    }
}

http_response_code(200);
echo 'OK';
