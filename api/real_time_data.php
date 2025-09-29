<?php
// api/real_time_data.php
session_start();
require '../includes/db.php';

// Validar que el solicitante sea un usuario logueado o un administrador
$is_user = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true && isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;

if (!$is_user && !$is_admin) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Si es un usuario normal, verificar que su sesión esté 'conectada'.
if ($is_user) {
    $stmt_session = $pdo->prepare("SELECT status FROM user_sessions WHERE property_id = ? AND meeting_id = ?");
    $stmt_session->execute([$_SESSION['user_id'], $_SESSION['meeting_id']]);
    $session_status = $stmt_session->fetchColumn();

    if ($session_status !== 'connected') {
        // Si la sesión no está 'connected' (p. ej., 'disconnected'), enviar una respuesta
        // específica que el cliente pueda interpretar para forzar el cierre de sesión.
        header('Content-Type: application/json');
        echo json_encode(['status' => 'disconnected']);
        exit;
    }
}

// Obtener la reunión activa (la última abierta)
$stmt_meeting = $pdo->prepare("SELECT * FROM meetings WHERE status = 'opened' ORDER BY id DESC LIMIT 1");
$stmt_meeting->execute();
$meeting = $stmt_meeting->fetch(PDO::FETCH_ASSOC);

if (!$meeting) {
    echo json_encode(['error' => 'No hay una reunión activa.']);
    exit;
}

// 1. Calcular el coeficiente total correctamente, incluyendo los poderes.

// Primero, obtener los IDs de las propiedades de los asistentes conectados.
$stmt_attendees = $pdo->prepare("SELECT property_id FROM user_sessions WHERE meeting_id = ? AND status = 'connected'");
$stmt_attendees->execute([$meeting['id']]);
$attendee_property_ids = $stmt_attendees->fetchAll(PDO::FETCH_COLUMN);

$represented_property_ids = $attendee_property_ids;

if (!empty($attendee_property_ids)) {
    // Segundo, obtener los IDs de las propiedades que han dado poder a los asistentes.
    $placeholders = implode(',', array_fill(0, count($attendee_property_ids), '?'));
    $stmt_powers = $pdo->prepare(
        "SELECT giver_property_id FROM powers WHERE meeting_id = ? AND receiver_property_id IN ($placeholders)"
    );
    $params = array_merge([$meeting['id']], $attendee_property_ids);
    $stmt_powers->execute($params);
    $power_giver_ids = $stmt_powers->fetchAll(PDO::FETCH_COLUMN);
    
    // Combinar todas las propiedades representadas (asistentes + poderes) y eliminar duplicados.
    $represented_property_ids = array_unique(array_merge($attendee_property_ids, $power_giver_ids));
}

$current_coefficient_sum = 0;
if (!empty($represented_property_ids)) {
    // Tercero, sumar los coeficientes de todas las propiedades representadas.
    $placeholders_final = implode(',', array_fill(0, count($represented_property_ids), '?'));
    $stmt_total_coeff = $pdo->prepare(
        "SELECT SUM(REPLACE(coefficient, ',', '.')) FROM properties WHERE id IN ($placeholders_final)"
    );
    $stmt_total_coeff->execute(array_values($represented_property_ids));
    $current_coefficient_sum = $stmt_total_coeff->fetchColumn() ?: 0;
}

// 2. Calcular el total de TODOS los coeficientes de la propiedad horizontal
$total_coefficient_query = $pdo->query("SELECT SUM(REPLACE(coefficient, ',', '.')) FROM properties");
$total_coefficient = $total_coefficient_query->fetchColumn();


// 3. Calcular el porcentaje del quórum
$quorum_percentage = ($total_coefficient > 0) ? ($current_coefficient_sum / $total_coefficient) * 100 : 0;

// 4. Obtener lista de usuarios conectados, incluyendo información de poderes recibidos
$stmt_users = $pdo->prepare(
    "SELECT 
        p.id AS property_id,
        p.owner_name, 
        p.house_number, 
        p.coefficient,
        giver.house_number AS power_giver_house,
        giver.coefficient AS power_giver_coefficient
     FROM user_sessions us
     JOIN properties p ON us.property_id = p.id
     LEFT JOIN powers pow ON us.property_id = pow.receiver_property_id AND pow.meeting_id = us.meeting_id
     LEFT JOIN properties giver ON pow.giver_property_id = giver.id
     WHERE us.meeting_id = ? AND us.status = 'connected' 
     ORDER BY p.owner_name ASC"
);
$stmt_users->execute([$meeting['id']]);
$connected_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// 5. Verificar si hay una votación activa
$stmt_poll = $pdo->prepare("SELECT id, question, options, duration_seconds FROM polls WHERE meeting_id = ? AND status = 'opened'");
$stmt_poll->execute([$meeting['id']]);
$active_poll = $stmt_poll->fetch(PDO::FETCH_ASSOC);

// 6. Obtener la hora de inicio de la reunión (primer login)
$stmt_start_time = $pdo->prepare("SELECT MIN(login_time) FROM user_sessions WHERE meeting_id = ? AND status = 'connected'");
$stmt_start_time->execute([$meeting['id']]);
$start_time = $stmt_start_time->fetchColumn();

// Preparar respuesta JSON
$response = [
    'meeting_name' => $meeting['name'],
    'start_time' => $start_time,
    'quorum' => [
        'percentage' => round($quorum_percentage, 2),
        'current_coefficient' => round($current_coefficient_sum, 5),
        'required' => (float)$meeting['required_quorum_percentage'],
        'has_quorum' => $quorum_percentage >= $meeting['required_quorum_percentage']
    ],
    'users' => [
        'connected_count' => count($connected_users),
        'list' => $connected_users
    ],
    'active_poll' => $active_poll // Será null si no hay votación activa
];

header('Content-Type: application/json');
echo json_encode($response);
?>