<?php
// api/register_session.php
session_start();
header('Content-Type: application/json');

// 1. Verificar que el usuario esté logueado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado. Por favor, inicie sesión de nuevo.']);
    exit;
}

require_once '../includes/db.php';
require_once '../includes/cache_updater.php'; // Incluir el actualizador de caché

$property_id = $_SESSION['user_id'];
$response = ['success' => false, 'message' => 'No se pudo registrar la sesión.'];

try {
    // 2. Encontrar la reunión activa
    $stmt_meeting = $pdo->prepare("SELECT id FROM meetings WHERE status = 'opened' ORDER BY id DESC LIMIT 1");
    $stmt_meeting->execute();
    $active_meeting = $stmt_meeting->fetch(PDO::FETCH_ASSOC);

    if (!$active_meeting) {
        $response['message'] = 'No se encontró una reunión activa en este momento.';
        echo json_encode($response);
        exit;
    }

    $meeting_id = $active_meeting['id'];
    $action = $_GET['action'] ?? 'register'; // Por defecto, la acción es registrar

    // 3. Lógica de "Upsert" o "Heartbeat"
    $stmt_check = $pdo->prepare("SELECT id FROM user_sessions WHERE property_id = ? AND meeting_id = ?");
    $stmt_check->execute([$property_id, $meeting_id]);
    $session_id = $stmt_check->fetchColumn();

    if ($action === 'heartbeat') {
        if ($session_id) {
            // Acción de Heartbeat: solo actualizar 'last_seen_at' y el estado a 'connected'
            $stmt_heartbeat = $pdo->prepare("UPDATE user_sessions SET status = 'connected', last_seen_at = NOW() WHERE id = ?");
            $success = $stmt_heartbeat->execute([$session_id]);
            $response = ['success' => $success, 'message' => 'Heartbeat recibido.'];
        } else {
            // Si se envía un heartbeat para una sesión no registrada, se ignora.
            $response = ['success' => false, 'message' => 'Sesión no encontrada para el heartbeat.'];
        }
    } elseif ($action === 'register') {
        if ($session_id) {
            // Si ya existe, se actualiza el estado, la hora de login y 'last_seen_at' (reconexión)
            $stmt_upsert = $pdo->prepare("UPDATE user_sessions SET status = 'connected', login_time = NOW(), last_seen_at = NOW(), logout_time = NULL WHERE id = ?");
            $success = $stmt_upsert->execute([$session_id]);
        } else {
            // Si no existe, se inserta un nuevo registro con 'last_seen_at'
            $stmt_upsert = $pdo->prepare("INSERT INTO user_sessions (property_id, meeting_id, status, login_time, last_seen_at) VALUES (?, ?, 'connected', NOW(), NOW())");
            $success = $stmt_upsert->execute([$property_id, $meeting_id]);
        }

        if ($success) {
            update_meeting_cache(); // Actualizar la caché para reflejar el nuevo usuario
            $response = ['success' => true, 'message' => 'Sesión registrada correctamente.'];
        } else {
            $response['message'] = 'Error al guardar los datos de la sesión en la base de datos.';
        }
    }

} catch (PDOException $e) {
    // Manejar errores de la base de datos
    // En un entorno de producción, sería bueno loguear el error en lugar de mostrarlo.
    $response['message'] = 'Error de base de datos: ' . $e->getMessage();
} catch (Exception $e) {
    $response['message'] = 'Error inesperado: ' . $e->getMessage();
}

echo json_encode($response);
?>