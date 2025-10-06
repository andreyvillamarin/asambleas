<?php
// api/admin_actions.php
require_once __DIR__ . '/../admin/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cache_updater.php'; // Incluir el actualizador de caché

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Acción no válida.'];
$action = $_POST['action'] ?? '';
$meeting_id = $_POST['meeting_id'] ?? null;

try {
    // NOTA PARA FUTUROS DESARROLLADORES:
    // Cualquier nueva acción que modifique el estado de la sesión de los usuarios
    // (ej. desconectar a un solo usuario) DEBE llamar a `update_meeting_cache()`
    // al final para mantener la consistencia de los datos en tiempo real.

    if ($action === 'open_meeting' && $meeting_id) {
        // 1. Asegurarse que no haya otra reunión abierta
        $pdo->exec("UPDATE meetings SET status = 'closed' WHERE status = 'opened'");
        
        // 2. Calcular el coeficiente total de toda la propiedad para guardarlo.
        $total_coefficient_query = $pdo->query("SELECT SUM(REPLACE(coefficient, ',', '.')) FROM properties");
        $total_coefficient = $total_coefficient_query->fetchColumn();

        // 3. Abrir la nueva, registrar la hora de inicio y el coeficiente total.
        $stmt = $pdo->prepare(
            "UPDATE meetings SET status = 'opened', start_time = NOW(), total_coefficient = ? WHERE id = ?"
        );
        $stmt->execute([$total_coefficient, $meeting_id]);
        
        update_meeting_cache(); // Actualizar caché
        $response = ['success' => true, 'message' => 'Reunión abierta correctamente.'];
    } 
    elseif ($action === 'close_meeting' && $meeting_id) {
        // 1. Cerrar la reunión
        $stmt_meeting = $pdo->prepare("UPDATE meetings SET status = 'closed' WHERE id = ?");
        $stmt_meeting->execute([$meeting_id]);

        // 2. Desconectar a todos los usuarios de esa reunión.
        $stmt_sessions = $pdo->prepare(
            "UPDATE user_sessions SET status = 'disconnected', logout_time = NOW() WHERE meeting_id = ? AND status = 'connected'"
        );
        $stmt_sessions->execute([$meeting_id]);
        
        update_meeting_cache(); // Actualizar caché
        $response = ['success' => true, 'message' => 'Reunión cerrada y todos los usuarios desconectados.'];
    }
    elseif ($action === 'disconnect_all' && $meeting_id) {
        // Eliminar todas las sesiones de la reunión, pero no cerrar la reunión en sí.
        $stmt_delete = $pdo->prepare("DELETE FROM user_sessions WHERE meeting_id = ?");
        $stmt_delete->execute([$meeting_id]);
        
        update_meeting_cache(); // Actualizar caché
        $response = ['success' => true, 'message' => 'Todos los usuarios han sido desconectados.'];
    }
    elseif ($action === 'delete_meeting' && $meeting_id) {
        $pdo->beginTransaction();
        
        // Obtener los IDs de las votaciones (polls) asociadas a la reunión
        $stmt_get_polls = $pdo->prepare("SELECT id FROM polls WHERE meeting_id = ?");
        $stmt_get_polls->execute([$meeting_id]);
        $poll_ids = $stmt_get_polls->fetchAll(PDO::FETCH_COLUMN);

        if (!empty($poll_ids)) {
            // Eliminar los votos (votes) asociados a esas votaciones
            $placeholders = implode(',', array_fill(0, count($poll_ids), '?'));
            $stmt_votes = $pdo->prepare("DELETE FROM votes WHERE poll_id IN ($placeholders)");
            $stmt_votes->execute($poll_ids);
        }

        // Eliminar las votaciones (polls) asociadas
        $stmt_polls = $pdo->prepare("DELETE FROM polls WHERE meeting_id = ?");
        $stmt_polls->execute([$meeting_id]);

        // Eliminar poderes asociados
        $stmt_powers = $pdo->prepare("DELETE FROM powers WHERE meeting_id = ?");
        $stmt_powers->execute([$meeting_id]);

        // Eliminar sesiones de usuario asociadas
        $stmt_sessions = $pdo->prepare("DELETE FROM user_sessions WHERE meeting_id = ?");
        $stmt_sessions->execute([$meeting_id]);

        // Eliminar la reunión
        $stmt_meeting = $pdo->prepare("DELETE FROM meetings WHERE id = ?");
        $stmt_meeting->execute([$meeting_id]);

        $pdo->commit();
        
        $response = ['success' => true, 'message' => 'Reunión y todos sus datos asociados eliminados exitosamente.'];
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
}

echo json_encode($response);
?>