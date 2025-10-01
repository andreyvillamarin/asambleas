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

    // 3. Lógica de "Upsert": Insertar o actualizar la sesión del usuario
    // Primero, verificar si ya existe una sesión para este usuario en esta reunión.
    $stmt_check = $pdo->prepare("SELECT id FROM user_sessions WHERE property_id = ? AND meeting_id = ?");
    $stmt_check->execute([$property_id, $meeting_id]);
    $existing_session_id = $stmt_check->fetchColumn();

    if ($existing_session_id) {
        // Si ya existe, simplemente se actualiza el estado y la hora de login (reconexión).
        $stmt_upsert = $pdo->prepare("UPDATE user_sessions SET status = 'connected', login_time = NOW(), logout_time = NULL WHERE id = ?");
        $success = $stmt_upsert->execute([$existing_session_id]);
    } else {
        // Si no existe, se inserta un nuevo registro.
        $stmt_upsert = $pdo->prepare("INSERT INTO user_sessions (property_id, meeting_id, status, login_time) VALUES (?, ?, 'connected', NOW())");
        $success = $stmt_upsert->execute([$property_id, $meeting_id]);
    }

    if ($success) {
        update_meeting_cache(); // Actualizar la caché para reflejar el nuevo usuario
        $response = ['success' => true, 'message' => 'Sesión registrada correctamente.'];
    } else {
        $response['message'] = 'Error al guardar los datos de la sesión en la base de datos.';
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