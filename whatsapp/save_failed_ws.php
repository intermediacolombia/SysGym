<?php
// save_failed_ws.php

require_once __DIR__ . '/../inc/config.php';

if (!function_exists('saveFailedWSMessage')) {
    function saveFailedWSMessage(string $phone, string $text, ?string $urlCompleta = null): bool
    {
        // Incluido DENTRO: variables del include quedan en el scope de la función
        global $host, $dbname, $dbuser, $dbpass;

        try {
            
            $stmt = db()->prepare("
                INSERT INTO ws_outbox (phonenumber, text, url, created_at)
                VALUES (:phonenumber, :text, :url, NOW())
            ");
            $stmt->execute([
                ':phonenumber' => $phone,
                ':text'        => $text,
                // ¡Usa $urlCompleta (el parámetro), no $url del config!
                ':url'         => $urlCompleta,
            ]);

            return true;
        } catch (PDOException $e) {
            error_log("Error al guardar mensaje WS fallido: " . $e->getMessage());
            return false;
        }
    }
}


