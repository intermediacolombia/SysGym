<?php
require_once __DIR__ . '/../login/session.php';
require_once __DIR__ . '/../../inc/config.php';


try {

    $stmt = db()->prepare("
        SELECT HOUR(hora) AS hora, COUNT(*) AS total
        FROM asistencias
        WHERE fecha = :hoy
        GROUP BY HOUR(hora)
        ORDER BY hora ASC
    ");

    $stmt->execute([':hoy' => $hoy]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Rellenar horas 5am → 11pm
    $data = [];
    for ($i = 5; $i <= 23; $i++) {
        $data[] = [
            "hora"  => $i,
            "total" => 0
        ];
    }

    foreach ($rows as $r) {
        $h = (int)$r["hora"];
        foreach ($data as &$d) {
            if ($d["hora"] === $h) {
                $d["total"] = (int)$r["total"];
                break;
            }
        }
    }

    header("Content-Type: application/json; charset=utf-8");
    echo json_encode(["data" => $data], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => $e->getMessage()]);
}
