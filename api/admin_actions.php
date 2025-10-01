<?php
// api/admin_actions.php
require_once __DIR__ . '/../admin/includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/cache_updater.php'; // Incluir el actualizador de caché

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Acción no válida.'];
$action = $_POST['action'] ?? '';
$meeting_id = $_POST['meeting_id'] ?? null;
$poll_id = $_POST['poll_id'] ?? null;

try {
    // NOTA PARA FUTUROS DESARROLLADORES:
    // Cualquier nueva acción que modifique el estado de la sesión de los usuarios
    // (ej. desconectar a un solo usuario) DEBE llamar a `update_meeting_cache()`
    // al final para mantener la consistencia de los datos en tiempo real.

    if ($action === 'open_meeting' && $meeting_id) {
        // Al abrir una reunión, no se deben eliminar las sesiones existentes.
        // Los usuarios pueden haberse unido a la sala de espera antes de que se abra formalmente.
        // FIX: Se elimina la línea que borraba todas las sesiones de usuario al abrir la reunión.
        // $stmt_delete = $pdo->prepare("DELETE FROM user_sessions WHERE meeting_id = ?");
        // $stmt_delete->execute([$meeting_id]);

        // 1. Asegurarse que no haya otra reunión abierta (esto es correcto)
        $pdo->exec("UPDATE meetings SET status = 'closed' WHERE status = 'opened'");
        
        // 3. Calcular el coeficiente total de toda la propiedad para guardarlo.
        $total_coefficient_query = $pdo->query("SELECT SUM(REPLACE(coefficient, ',', '.')) FROM properties");
        $total_coefficient = $total_coefficient_query->fetchColumn();

        // 4. Abrir la nueva, registrar la hora de inicio y el coeficiente total.
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
    elseif ($action === 'open_poll' && $poll_id) {
        $stmt = $pdo->prepare("UPDATE polls SET status = 'opened' WHERE id = ?");
        $stmt->execute([$poll_id]);
        $response = ['success' => true, 'message' => 'Votación abierta.'];
    }
    elseif ($action === 'close_poll' && $poll_id) {
        // 1. Cerrar la votación
        $stmt = $pdo->prepare("UPDATE polls SET status = 'closed' WHERE id = ?");
        $stmt->execute([$poll_id]);

        // 2. Calcular los resultados basados en el coeficiente
        $stmt_votes = $pdo->prepare("SELECT selected_option, SUM(coefficient_applied) as total_coefficient FROM votes WHERE poll_id = ? GROUP BY selected_option");
        $stmt_votes->execute([$poll_id]);
        $results = $stmt_votes->fetchAll(PDO::FETCH_KEY_PAIR);

        // 3. Guardar los resultados en formato JSON
        $stmt_save = $pdo->prepare("UPDATE polls SET results = ? WHERE id = ?");
        $stmt_save->execute([json_encode($results), $poll_id]);
        
        $response = ['success' => true, 'message' => 'Votación cerrada y resultados calculados.', 'results' => $results];
    }

} catch (PDOException $e) {
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
}

echo json_encode($response);
?>