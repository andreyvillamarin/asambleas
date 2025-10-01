<?php
// includes/session_cleaner.php

/**
 * Marca como 'disconnected' las sesiones de una reunión que no han sido vistas recientemente.
 *
 * @param PDO $pdo La instancia de la conexión a la base de datos.
 * @param int $meeting_id El ID de la reunión para limpiar.
 * @param int $threshold_seconds El umbral en segundos para considerar una sesión como obsoleta.
 * @return int El número de filas (sesiones) actualizadas.
 */
function cleanup_stale_sessions(PDO $pdo, int $meeting_id, int $threshold_seconds = 35): int {
    if ($meeting_id <= 0) {
        return 0;
    }

    try {
        // La sintaxis de SQLite para la manipulación de fechas es diferente a la de MySQL.
        // Se usa strftime('%s', 'now') para obtener el timestamp Unix actual.
        $stmt = $pdo->prepare(
            "UPDATE user_sessions
             SET status = 'disconnected', logout_time = CURRENT_TIMESTAMP
             WHERE meeting_id = ?
               AND status = 'connected'
               AND (strftime('%s', 'now') - strftime('%s', last_seen_at)) > ?"
        );
        
        $stmt->execute([$meeting_id, $threshold_seconds]);
        
        return $stmt->rowCount();

    } catch (PDOException $e) {
        // En un caso real, se podría loguear el error.
        // error_log('Error al limpiar sesiones obsoletas: ' . $e->getMessage());
        return 0;
    }
}
?>