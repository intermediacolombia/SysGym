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

// ── Tiempo en minutos antes de reenviar menú (sin asesor) ────────
define('MENU_TIMEOUT_SECS',   5 * 60);   // 5 minutos
// Tiempo activo para sesión en "espera de asesor"
define('ASESOR_TIMEOUT_SECS', 2 * 60 * 60); // 2 horas

// ── Archivo de estados de conversación ──────────────────────────
define('ESTADOS_FILE', __DIR__ . '/estados_ws.json');


// ════════════════════════════════════════════════════════════════
//  UTILIDADES
// ════════════════════════════════════════════════════════════════

function wlog($msg) {
    $ts = date('Y-m-d H:i:s');
    file_put_contents(LOG_FILE, "[$ts] $msg\n", FILE_APPEND);
}

/**
 * Enviar mensaje por WhatsApp
 */
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
//  GESTIÓN DE ESTADOS
// ════════════════════════════════════════════════════════════════

function estadosCargar() {
    if (!file_exists(ESTADOS_FILE)) return [];
    $d = json_decode(file_get_contents(ESTADOS_FILE), true);
    return is_array($d) ? $d : [];
}

function estadosGuardar(array $estados) {
    file_put_contents(ESTADOS_FILE, json_encode($estados, JSON_PRETTY_PRINT));
}

function estadoObtener($key) {
    $estados = estadosCargar();
    return $estados[$key] ?? null;
}

/**
 * Guardar estado de usuario.
 * $estado = null  → borra la sesión
 */
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
 * Retorna el estado activo del usuario o null si expiró.
 * Respeta tiempos diferentes según el tipo de estado.
 */
function estadoActivo($key) {
    $e = estadoObtener($key);
    if (!$e) return null;

    $elapsed = time() - ($e['timestamp'] ?? 0);

    if ($e['estado'] === 'asesor') {
        // Silencio hasta ASESOR_TIMEOUT_SECS
        if ($elapsed > ASESOR_TIMEOUT_SECS) {
            estadoGuardar($key, null);
            return null;
        }
        return $e;
    }

    // Para estados "esperando documento"
    if (in_array($e['estado'], ['espera_doc_plan', 'espera_doc_pago'])) {
        if ($elapsed > MENU_TIMEOUT_SECS * 2) {
            estadoGuardar($key, null);
            return null;
        }
        return $e;
    }

    // Estados de menú: expiran en MENU_TIMEOUT_SECS
    if ($elapsed > MENU_TIMEOUT_SECS) {
        estadoGuardar($key, null);
        return null;
    }

    return $e;
}


// ════════════════════════════════════════════════════════════════
//  CONSULTAS A BD
// ════════════════════════════════════════════════════════════════

/**
 * Devuelve los planes activos formateados para WhatsApp.
 */
function planesDisponibles() {
    try {
        $stmt = db()->query(
            "SELECT nombre, precio, frecuencia, dias 
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

            // Describir periodicidad
            if ($p['frecuencia'] == 1) {
                $periodo = "Mensual";
            } elseif ($p['frecuencia'] == 12) {
                $periodo = "Anual";
            } else {
                $periodo = $p['frecuencia'] . " mes(es)";
            }

            $txt .= "▸ *{$p['nombre']}*\n";
            $txt .= "  💰 {$precio}  |  📅 {$periodo}\n\n";
        }

        $txt .= "Para más información escríbenos o visita:\n🌐 https://www.intermediahost.co";
        return $txt;
    } catch (Exception $e) {
        wlog("ERROR planesDisponibles: " . $e->getMessage());
        return "⚠️ No fue posible cargar los planes. Intenta más tarde.";
    }
}

/**
 * Consultar plan del cliente por número de documento.
 * Retorna texto formateado.
 */
function consultarPlanCliente($documento) {
    try {
        $stmt = db()->prepare(
            "SELECT c.nombres, c.apellidos, c.vencimiento_plan, c.congelado,
                    p.nombre AS plan_nombre, p.precio AS plan_precio
               FROM clientes c
          LEFT JOIN planes p ON p.id = c.plan
              WHERE c.identificacion = :doc
                AND c.borrado = 0
              LIMIT 1"
        );
        $stmt->execute([':doc' => $documento]);
        $c = $stmt->fetch();

        if (!$c) {
            return "❌ No encontré ningún cliente con el documento *{$documento}*.\n\nVerifica que sea correcto y vuelve a intentarlo.";
        }

        $nombre     = trim($c['nombres'] . ' ' . $c['apellidos']);
        $hoy        = new DateTime(date('Y-m-d'));
        $vencim     = new DateTime($c['vencimiento_plan']);
        $diff       = (int)$hoy->diff($vencim)->format('%r%a'); // negativo = vencido
        $vencimTxt  = $vencim->format('d/m/Y');

        if ($c['congelado']) {
            $estadoTxt = "🧊 *CONGELADO*";
        } elseif ($diff < 0) {
            $estadoTxt = "🔴 *VENCIDO* hace " . abs($diff) . " día(s)";
        } elseif ($diff === 0) {
            $estadoTxt = "🟡 *Vence HOY*";
        } elseif ($diff <= 5) {
            $estadoTxt = "🟡 *Vence pronto* — quedan {$diff} día(s)";
        } else {
            $estadoTxt = "🟢 *ACTIVO* — quedan {$diff} día(s)";
        }

        $planNombre = $c['plan_nombre'] ?? 'Sin plan asignado';
        $planPrecio = $c['plan_precio']
            ? '$' . number_format($c['plan_precio'], 0, ',', '.')
            : '—';

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

/**
 * Verificar si el cliente puede pagar hoy según DAYS_ALLOWED_BEFORE_DUE.
 * Si puede → retorna el link de pago.
 * Si no   → retorna mensaje explicativo.
 */
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
        $puedeP = ($diff <= $diasPermitidos);

        if ($puedeP) {
            $linkPago = "https://sysgym.intermediacolombia.com/pay/?doc={$documento}";
            return
                "✅ *{$nombre}*, aquí tienes tu enlace de pago:\n\n" .
                "🔗 {$linkPago}\n\n" .
                "📅 Tu plan vence el *{$vencimTxt}*.\n\n" .
                "_El pago es seguro y en línea._\n\n" .
                "Escribe *0* para volver al menú.";
        } else {
            $diasRestantes = $diff; // positivo = días que faltan
            $disponibleEn  = $diasRestantes - $diasPermitidos;
            return
                "⏳ *{$nombre}*, aún no está disponible el pago.\n\n" .
                "📅 Tu plan vence el *{$vencimTxt}* (faltan *{$diasRestantes}* días).\n\n" .
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
//  ENTRY POINT — PROCESAMIENTO DEL WEBHOOK
// ════════════════════════════════════════════════════════════════

$rawInput = file_get_contents('php://input');
wlog("RECIBIDO: " . $rawInput);

$data = json_decode($rawInput, true);

if (!$data) {
    http_response_code(400);
    exit('Invalid JSON');
	
	
}

$telefono  = isset($data['from'])      ? trim($data['from'])      : '';
$mensaje   = isset($data['message'])   ? trim($data['message'])   : '';
$nombre    = isset($data['pushName'])  ? $data['pushName']        : '';
$clientId  = isset($data['client_id']) ? $data['client_id']       : 'default';

if (empty($telefono) || empty($mensaje)) {
    http_response_code(200);
    exit('OK');
}

// Clave única por teléfono + instancia
$sesKey = $telefono . '_' . $clientId;

wlog("[$clientId] De: $telefono ($nombre) → \"$mensaje\"");

// ── Obtener estado activo ──────────────────────────────────────
$sesData   = estadoActivo($sesKey);
$estado    = $sesData ? $sesData['estado'] : null;
$sesExtra  = $sesData ? ($sesData['data'] ?? []) : [];

$mensajeLower = strtolower(trim($mensaje));
$respuesta    = null;

// ── Si está en cola de asesor: silencio del bot ────────────────
if ($estado === 'asesor') {
    wlog("[$clientId] En espera de asesor — bot silenciado");
    http_response_code(200);
    exit('OK');
}

// ── Detección de agente humano respondiendo ───────────────────
// Si un humano escribe desde el panel (sin estado cliente) lo detectamos
if (preg_match('/\b(te atiendo|en que puedo ayudarte|cuentame|dime|hola soy|te ayudo|un momento|ya te atiendo)\b/i', $mensajeLower) && !$estado) {
    estadoGuardar($sesKey, 'asesor', ['agente' => true, 'timestamp' => time()]);
    wlog("[$clientId] Humano detectado — sesión marcada como asesor");
    http_response_code(200);
    exit('OK');
}

// ── Comando "0": reiniciar al menú principal ──────────────────
if ($mensaje === '0' || $mensajeLower === '0') {
    estadoGuardar($sesKey, null);
    $respuesta = menuPrincipal($nombre);
    estadoGuardar($sesKey, 'menu_principal');

// ── Palabras clave de bienvenida ──────────────────────────────
} elseif (preg_match('/\b(hola|hi|buenas|buenos dias|buenas tardes|buenas noches|menu|inicio|start)\b/i', $mensajeLower)) {
    estadoGuardar($sesKey, null);
    $respuesta = menuPrincipal($nombre);
    estadoGuardar($sesKey, 'menu_principal');

// ════════════════════════════════════════════════════════════════
//  MENÚ PRINCIPAL
// ════════════════════════════════════════════════════════════════
} elseif ($estado === 'menu_principal') {

    switch ($mensaje) {

        // ── 1. Ver Planes ──────────────────────────────────────
        case '1':
            $respuesta = planesDisponibles();
            // Volver a mostrar menú tras MENU_TIMEOUT_SECS si no responde
            estadoGuardar($sesKey, 'menu_principal');
            break;

        // ── 2. Horarios ────────────────────────────────────────
        case '2':
            $respuesta = HORARIOS_GYM . "\n\nEscribe *0* para volver al menú.";
            estadoGuardar($sesKey, 'menu_principal');
            break;

        // ── 3. Consultar mi Plan ───────────────────────────────
        case '3':
            $respuesta =
                "🔍 *Consultar mi Plan*\n\n" .
                "Por favor envíame tu *número de documento*.\n\n" .
                "⚠️ Sin espacios, sin puntos, sin comas.\n" .
                "_Ejemplo: 1094914578_\n\n" .
                "Escribe *0* para cancelar.";
            estadoGuardar($sesKey, 'espera_doc_plan');
            break;

        // ── 4. Realizar Pago ───────────────────────────────────
        case '4':
            $respuesta =
                "💳 *Realizar Pago*\n\n" .
                "Por favor envíame tu *número de documento*.\n\n" .
                "⚠️ Sin espacios, sin puntos, sin comas.\n" .
                "_Ejemplo: 1094914578_\n\n" .
                "Escribe *0* para cancelar.";
            estadoGuardar($sesKey, 'espera_doc_pago');
            break;

        // ── 5. Hablar con Asesor ───────────────────────────────
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

// ════════════════════════════════════════════════════════════════
//  ESPERANDO DOCUMENTO — CONSULTAR PLAN
// ════════════════════════════════════════════════════════════════
} elseif ($estado === 'espera_doc_plan') {

    // Validar que sea numérico y razonable
    if (preg_match('/^\d{5,15}$/', $mensaje)) {
        $respuesta = consultarPlanCliente($mensaje);
        wlog("[$clientId] CONSULTA PLAN: $nombre ($telefono) doc=$mensaje");
        estadoGuardar($sesKey, 'menu_principal'); // permite seguir navegando
    } else {
        $respuesta =
            "⚠️ Documento inválido.\n\n" .
            "Envía *solo números*, sin espacios ni caracteres especiales.\n" .
            "_Ejemplo: 1094914578_\n\n" .
            "Escribe *0* para cancelar.";
    }

// ════════════════════════════════════════════════════════════════
//  ESPERANDO DOCUMENTO — REALIZAR PAGO
// ════════════════════════════════════════════════════════════════
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

// ════════════════════════════════════════════════════════════════
//  SIN ESTADO — Bienvenida inicial
// ════════════════════════════════════════════════════════════════
} else {
    $respuesta = menuPrincipal($nombre);
    estadoGuardar($sesKey, 'menu_principal');
}

// ── Enviar respuesta ──────────────────────────────────────────
if ($respuesta) {
    if (!wsSend($telefono, $respuesta)) {
        wlog("[$clientId] ERROR al enviar mensaje a $telefono api:" .API_KEY ."URL". API_URL);
    }
}

http_response_code(200);
echo 'OK';