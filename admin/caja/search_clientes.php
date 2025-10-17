<?php
require_once __DIR__ . '/../../inc/config.php';

try {
  $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  if (isset($_GET['query'])) {
    $query = '%' . $_GET['query'] . '%';

    $stmt = $pdo->prepare("
      SELECT id, identificacion, nombres, apellidos 
      FROM clientes 
      WHERE estado = 'activo' 
        AND (identificacion LIKE :query OR nombres LIKE :query OR apellidos LIKE :query)
      LIMIT 10
    ");
    $stmt->execute([':query' => $query]);
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($clientes);
  } else {
    echo json_encode([]);
  }
} catch (Exception $e) {
  echo json_encode(['error' => $e->getMessage()]);
}
?>