<?php
// api/meeting_status.php

// Cargar la configuración de la sesión ANTES de iniciarla.
require_once '../includes/session_config.php';

// Este script ahora limpia sesiones obsoletas antes de reportar el estado.
session_start(); // Necesario para la sesión, aunque no se use para validar el acceso aquí.

header('Content-Type: application/json');

require_once '../includes/db.php';
require_once '../includes/session_cleaner.php';
require_once '../includes/cache_updater.php';

$response = ['status' => 'closed']; // Estado por defecto

try {
    // 1. Encontrar la reunión activa para obtener su ID.
    $stmt_meeting = $pdo->prepare("SELECT id FROM meetings WHERE status = 'opened' ORDER BY id DESC LIMIT 1");
    $stmt_meeting->execute();
    $active_meeting = $stmt_meeting->fetch(PDO::FETCH_ASSOC);

    if ($active_meeting) {
        $meeting_id = $active_meeting['id'];

        // 2. Limpiar sesiones obsoletas para esta reunión.
        $cleaned_sessions_count = cleanup_stale_sessions($pdo, $meeting_id, 35); // 35 segundos de umbral

        // 3. Si se limpió alguna sesión, la caché de datos debe actualizarse.
        if ($cleaned_sessions_count > 0) {
            update_meeting_cache();
        }

        // 4. Como encontramos una reunión activa, el estado es 'opened'.
        $response['status'] = 'opened';
    }
    // Si no hay reunión activa, el estado por defecto 'closed' es correcto.

} catch (Exception $e) {
    // En caso de un error grave, se reporta 'closed' para seguridad.
    // Se podría loguear el error.
    // error_log("Error en meeting_status.php: " . $e->getMessage());
    $response = ['status' => 'closed', 'error' => $e->getMessage()];
}

echo json_encode($response);
?>