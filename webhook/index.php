<?php
/**
 * ============================================================
 *  WEBHOOK WHATSAPP — SYSGYM
 * ============================================================
 */

require_once __DIR__ . '/../inc/config.php';

define('API_KEY',      $api_ws);
define('API_URL',      rtrim(WA_API_URL, '/') . '/send');
define('LOG_FILE',     __DIR__ . '/webhook-ws.log');
define('ESTADOS_FILE', __DIR__ . '/estados_ws.json');

define('MENU_TIMEOUT_SECS',   5 * 60);
define('ASESOR_TIMEOUT_SECS', 2 * 60 * 60);

define('HORARIOS_GYM',
    "🕐 *Horarios de atención:*\n\n" .
    "Lunes a Viernes:  5:00 am – 10:00 pm\n" .
    "Sábados:          6:00 am –  8:00 pm\n" .
    "Domingos:         8:00 am –  2:00 pm\n\n" .
    "📍 " . NAME_GYM
);

// ════════════════════════════════════════════════════════════════
//  LOG
// ════════════════════════════════════════════════════════════════
function wlog($msg) {
    file_put_contents(LOG_FILE, '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
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
//  ESTADOS — leer/escribir JSON
// ════════════════════════════════════════════════════════════════
function jsonLeer() {
    if (!file_exists(ESTADOS_FILE)) return [];
    $raw = file_get_contents(ESTADOS_FILE);
    $arr = json_decode($raw, true);
    return is_array($arr) ? $arr : [];
}

function jsonEscribir(array $arr) {
    file_put_contents(ESTADOS_FILE, json_encode($arr, JSON_PRETTY_PRINT));
}

/**
 * Lee el estado del usuario. Si expiró lo borra y retorna null.
 */
function obtenerEstado($key) {
    $arr = jsonLeer();
    if (!isset($arr[$key])) return null;

    $e       = $arr[$key];
    $elapsed = time() - intval($e['timestamp']);

    $limite = ($e['estado'] === 'asesor')
        ? ASESOR_TIMEOUT_SECS
        : (in_array($e['estado'], ['espera_doc_plan','espera_doc_pago'])
            ? MENU_TIMEOUT_SECS * 2
            : MENU_TIMEOUT_SECS);

    if ($elapsed > $limite) {
        unset($arr[$key]);
        jsonEscribir($arr);
        wlog("Estado expirado y borrado: $key");
        return null;
    }

    return $e;
}

/**
 * Guarda el estado. $nuevoEstado = null → borra la clave.
 */
function guardarEstado($key, $nuevoEstado, array $data = []) {
    $arr = jsonLeer();
    if ($nuevoEstado === null) {
        unset($arr[$key]);
        wlog("Estado borrado: $key");
    } else {
        $arr[$key] = [
            'estado'    => $nuevoEstado,
            'data'      => $data,
            'timestamp' => time(),
        ];
        wlog("Estado guardado: $key → $nuevoEstado");
    }
    jsonEscribir($arr);
}

/**
 * Resetear: borra y escribe menu_principal en una sola operación.
 */
function resetMenu($key, $nombre) {
    $arr = jsonLeer();
    $arr[$key] = [
        'estado'    => 'menu_principal',
        'data'      => [],
        'timestamp' => time(),
    ];
    jsonEscribir($arr);
    wlog("RESET menu_principal: $key");
    return menuPrincipal($nombre);
}

// ════════════════════════════════════════════════════════════════
//  MENÚ
// ════════════════════════════════════════════════════════════════
function menuPrincipal($nombre = '') {
    $saludo = $nombre ? "Hola *{$nombre}*! 👋\n\n" : "👋 Bienvenido!\n\n";
    return $saludo .
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
//  CONSULTAS BD
// ════════════════════════════════════════════════════════════════
function planesDisponibles() {
    try {
        $rows = db()->query(
            "SELECT nombre, precio, frecuencia FROM planes
              WHERE estado='activo' AND borrado=0 ORDER BY precio ASC"
        )->fetchAll();

        if (empty($rows)) return "⚠️ No hay planes disponibles en este momento.";

        $txt = "💪 *PLANES DISPONIBLES — " . NAME_GYM . "*\n\n";
        foreach ($rows as $p) {
            $precio  = '$' . number_format($p['precio'], 0, ',', '.');
            $periodo = $p['frecuencia'] == 1 ? 'Mensual' : ($p['frecuencia'] == 12 ? 'Anual' : $p['frecuencia'] . ' mes(es)');
            $txt .= "▸ *{$p['nombre']}*\n  💰 {$precio}  |  📅 {$periodo}\n\n";
        }
        return $txt . "Escribe *0* para volver al menú.";
    } catch (Exception $ex) {
        wlog("ERROR planesDisponibles: " . $ex->getMessage());
        return "⚠️ No fue posible cargar los planes. Intenta más tarde.";
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
        if (!$c) return "❌ No encontré ningún cliente con el documento *{$doc}*.\n\nVerifica que sea correcto.";

        $nombre = trim($c['nombres'] . ' ' . $c['apellidos']);
        $hoy    = new DateTime(date('Y-m-d'));
        $venc   = new DateTime($c['vencimiento_plan']);
        $diff   = (int)$hoy->diff($venc)->format('%r%a');
        $vTxt   = $venc->format('d/m/Y');

        if ($c['congelado'])   $est = "🧊 *CONGELADO*";
        elseif ($diff < 0)     $est = "🔴 *VENCIDO* hace " . abs($diff) . " día(s)";
        elseif ($diff === 0)   $est = "🟡 *Vence HOY*";
        elseif ($diff <= 5)    $est = "🟡 *Vence pronto* — quedan {$diff} día(s)";
        else                   $est = "🟢 *ACTIVO* — quedan {$diff} día(s)";

        $pNombre = $c['plan_nombre'] ?? 'Sin plan asignado';
        $pPrecio = $c['plan_precio'] ? '$' . number_format($c['plan_precio'], 0, ',', '.') : '—';

        return "👤 *{$nombre}*\n\n📋 Plan: *{$pNombre}*\n💰 Valor: {$pPrecio}\n📅 Vencimiento: {$vTxt}\nEstado: {$est}\n\nEscribe *0* para volver al menú.";
    } catch (Exception $ex) {
        wlog("ERROR consultarPlanCliente: " . $ex->getMessage());
        return "⚠️ Error al consultar. Intenta más tarde.";
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
        if (!$c) return "❌ No encontré ningún cliente con el documento *{$doc}*.\n\nVerifica que sea correcto.";
        if ($c['congelado']) return "🧊 Tu membresía está *congelada*. Comunícate con nosotros para reactivarla.";

        $hoy    = new DateTime(date('Y-m-d'));
        $venc   = new DateTime($c['vencimiento_plan']);
        $diff   = (int)$hoy->diff($venc)->format('%r%a');
        $nombre = trim($c['nombres'] . ' ' . $c['apellidos']);
        $vTxt   = $venc->format('d/m/Y');
        $dias   = DAYS_ALLOWED_BEFORE_DUE;

        if ($diff <= $dias) {
            $link = "https://sysgym.intermediacolombia.com/pay/?doc={$doc}";
            return "✅ *{$nombre}*, aquí tienes tu enlace de pago:\n\n🔗 {$link}\n\n📅 Tu plan vence el *{$vTxt}*.\n\n_El pago es seguro y en línea._\n\nEscribe *0* para volver al menú.";
        } else {
            $en = $diff - $dias;
            return "⏳ *{$nombre}*, aún no está disponible el pago.\n\n📅 Tu plan vence el *{$vTxt}* (faltan *{$diff}* días).\n\nEl pago se habilita *{$dias} día(s) antes* del vencimiento.\nPodrás pagar en aproximadamente *{$en} día(s)*.\n\nEscribe *0* para volver al menú.";
        }
    } catch (Exception $ex) {
        wlog("ERROR gestionarPago: " . $ex->getMessage());
        return "⚠️ Error al procesar. Intenta más tarde.";
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
$mensajeLower = mb_strtolower(trim($mensaje));

wlog("[$clientId] $telefono ($nombre) → \"$mensaje\"");

// ── Leer estado ───────────────────────────────────────────────
$sesData = obtenerEstado($sesKey);
$estado  = $sesData['estado'] ?? null;
wlog("[$clientId] Estado actual: " . ($estado ?? 'NINGUNO'));

$respuesta = null;

// ── A. Bot silenciado (esperando asesor) ──────────────────────
if ($estado === 'asesor') {
    wlog("[$clientId] Silenciado — asesor activo");
    http_response_code(200); exit('OK');
}

// ── B. Detección de agente humano ─────────────────────────────
if (preg_match('/\b(te atiendo|en que puedo ayudarte|cuentame|dime|hola soy|te ayudo|un momento|ya te atiendo)\b/i', $mensajeLower)) {
    guardarEstado($sesKey, 'asesor', ['agente' => true]);
    wlog("[$clientId] Humano detectado");
    http_response_code(200); exit('OK');
}

// ── C. 0 o saludo → RESET SIEMPRE (sin importar estado) ──────
if ($mensaje === '0' || preg_match('/\b(hola|hi|buenas|buenos dias|buenas tardes|buenas noches|menu|inicio|start)\b/i', $mensajeLower)) {
    wlog("[$clientId] Trigger reset: \"$mensaje\"");
    $respuesta = resetMenu($sesKey, $nombre);

// ── D. Menú principal ─────────────────────────────────────────
} elseif ($estado === 'menu_principal') {
    switch ($mensaje) {
        case '1':
            $respuesta = planesDisponibles();
            guardarEstado($sesKey, 'menu_principal');
            break;
        case '2':
            $respuesta = HORARIOS_GYM . "\n\nEscribe *0* para volver al menú.";
            guardarEstado($sesKey, 'menu_principal');
            break;
        case '3':
            $respuesta = "🔍 *Consultar mi Plan*\n\nPor favor envíame tu *número de documento*.\n\n⚠️ Sin espacios, sin puntos, sin comas.\n_Ejemplo: 1094914578_\n\nEscribe *0* para cancelar.";
            guardarEstado($sesKey, 'espera_doc_plan');
            break;
        case '4':
            $respuesta = "💳 *Realizar Pago*\n\nPor favor envíame tu *número de documento*.\n\n⚠️ Sin espacios, sin puntos, sin comas.\n_Ejemplo: 1094914578_\n\nEscribe *0* para cancelar.";
            guardarEstado($sesKey, 'espera_doc_pago');
            break;
        case '5':
            $respuesta = "🧑‍💼 *Conectando con un asesor...*\n\nEn un momento alguien del equipo de *" . NAME_GYM . "* te atenderá.\n\n📞 También puedes llamarnos al: " . TEL_GYM . "\n\n_Por favor espera, no es necesario escribir más._";
            guardarEstado($sesKey, 'asesor', ['solicitado' => time()]);
            wlog("[$clientId] ASESOR SOLICITADO: $nombre ($telefono)");
            break;
        default:
            $respuesta = "⚠️ Opción no válida.\n\n" . menuPrincipal($nombre);
            guardarEstado($sesKey, 'menu_principal');
    }

// ── E. Espera de documento — consultar plan ───────────────────
} elseif ($estado === 'espera_doc_plan') {
    if (preg_match('/^\d{5,15}$/', $mensaje)) {
        $respuesta = consultarPlanCliente($mensaje);
        wlog("[$clientId] CONSULTA PLAN doc=$mensaje");
        guardarEstado($sesKey, 'menu_principal');
    } else {
        $respuesta = "⚠️ Documento inválido.\n\nEnvía *solo números*, sin espacios ni caracteres especiales.\n_Ejemplo: 1094914578_\n\nEscribe *0* para cancelar.";
    }

// ── F. Espera de documento — pago ────────────────────────────
} elseif ($estado === 'espera_doc_pago') {
    if (preg_match('/^\d{5,15}$/', $mensaje)) {
        $respuesta = gestionarPago($mensaje);
        wlog("[$clientId] SOLICITUD PAGO doc=$mensaje");
        guardarEstado($sesKey, 'menu_principal');
    } else {
        $respuesta = "⚠️ Documento inválido.\n\nEnvía *solo números*, sin espacios ni caracteres especiales.\n_Ejemplo: 1094914578_\n\nEscribe *0* para cancelar.";
    }

// ── G. Sin estado (primera vez o expirado) ────────────────────
} else {
    wlog("[$clientId] Sin estado — mostrando menú inicial");
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