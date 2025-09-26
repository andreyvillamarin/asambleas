<?php
// api/admin_actions.php
require_once __DIR__ . '/../admin/includes/auth_check.php'; // Usa el auth_check para seguridad
require_once __DIR__ . '/../includes/db.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Acción no válida.'];
$action = $_POST['action'] ?? '';
$meeting_id = $_POST['meeting_id'] ?? null;
$poll_id = $_POST['poll_id'] ?? null;

try {
    if ($action === 'open_meeting' && $meeting_id) {
        // Asegurarse que no haya otra reunión abierta
        $pdo->exec("UPDATE meetings SET status = 'closed' WHERE status = 'opened'");
        // Abrir la nueva
        $stmt = $pdo->prepare("UPDATE meetings SET status = 'opened' WHERE id = ?");
        $stmt->execute([$meeting_id]);
        $response = ['success' => true, 'message' => 'Reunión abierta correctamente.'];
    } 
    elseif ($action === 'close_meeting' && $meeting_id) {
        $stmt = $pdo->prepare("UPDATE meetings SET status = 'closed' WHERE id = ?");
        $stmt->execute([$meeting_id]);
        // Opcional: Desconectar a todos los usuarios al cerrar
        $stmt_disconnect = $pdo->prepare("DELETE FROM user_sessions WHERE meeting_id = ?");
        $stmt_disconnect->execute([$meeting_id]);
        $response = ['success' => true, 'message' => 'Reunión cerrada.'];
    }
    elseif ($action === 'disconnect_all' && $meeting_id) {
        $stmt = $pdo->prepare("DELETE FROM user_sessions WHERE meeting_id = ?");
        $stmt->execute([$meeting_id]);
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