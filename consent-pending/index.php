<?php
require_once __DIR__ . '/../inc/config.php';
header('Content-Type: text/html; charset=utf-8');

$token = $_GET['token'] ?? '';

if (!$token) die('<h3>Token no proporcionado</h3>');

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Buscar token válido
    $stmt = $pdo->prepare("SELECT tc.*, c.nombres, c.apellidos, c.id AS cliente_id 
                           FROM tokens_consent tc
                           JOIN clientes c ON c.id = tc.cliente_id
                           WHERE tc.token = :token AND tc.usado = 0
                           AND tc.fecha_expiracion > NOW()
                           LIMIT 1");
    $stmt->execute([':token' => $token]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$tokenData) {
        die('<h3>El enlace ha expirado o no es válido.</h3>');
    }

    // Verificar si el cliente ya firmó
    $stmtForm = $pdo->prepare("SELECT id FROM formularios WHERE cliente_id = :id LIMIT 1");
    $stmtForm->execute([':id' => $tokenData['cliente_id']]);
    $form = $stmtForm->fetch();

    if ($form) {
        echo '<h3>Ya existe un consentimiento firmado para este cliente.</h3>';
        exit;
    }

} catch (Exception $e) {
    die('<h3>Error al procesar el token: ' . $e->getMessage() . '</h3>');
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Consentimiento Informado - SysGym</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container">
  <div class="card mx-auto shadow-sm" style="max-width:600px;">
    <div class="card-body">
      <h4 class="text-center mb-3">Consentimiento Informado</h4>
      <p>Hola <strong><?= htmlspecialchars($tokenData['nombres'] . ' ' . $tokenData['apellidos']) ?></strong>,</p>
      <p>Por favor confirma tu consentimiento informado antes de continuar:</p>
		<?php echo CONSENT;?>
      <form method="POST" action="submit.php">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" name="acepto" required>
          <label class="form-check-label">He leído y acepto los términos del consentimiento informado.</label>
        </div>
        <div class="mb-3">
          <label>Firma digital (nombre completo)</label>
          <input type="text" name="firma" class="form-control" required placeholder="Escribe tu nombre completo">
        </div>
        <button type="submit" class="btn btn-success w-100">Enviar</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
