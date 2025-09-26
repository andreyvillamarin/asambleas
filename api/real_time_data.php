<?php
// api/real_time_data.php
session_start();
require '../includes/db.php';

// Validar que el usuario esté logueado
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

// Obtener la reunión activa (la última abierta)
$stmt_meeting = $pdo->prepare("SELECT * FROM meetings WHERE status = 'opened' ORDER BY id DESC LIMIT 1");
$stmt_meeting->execute();
$meeting = $stmt_meeting->fetch(PDO::FETCH_ASSOC);

if (!$meeting) {
    echo json_encode(['error' => 'No hay una reunión activa.']);
    exit;
}

// 1. Calcular el total de coeficientes de todos los usuarios CONECTADOS
$stmt_quorum = $pdo->prepare(
    "SELECT SUM(p.coefficient) AS current_coefficient 
     FROM user_sessions us
     JOIN properties p ON us.property_id = p.id
     WHERE us.meeting_id = ? AND us.status = 'connected'"
);
$stmt_quorum->execute([$meeting['id']]);
$current_coefficient_sum = $stmt_quorum->fetchColumn() ?: 0;

// 2. Calcular el total de TODOS los coeficientes de la propiedad horizontal
$total_coefficient = $pdo->query("SELECT SUM(coefficient) FROM properties")->fetchColumn();

// 3. Calcular el porcentaje del quórum
$quorum_percentage = ($total_coefficient > 0) ? ($current_coefficient_sum / $total_coefficient) * 100 : 0;

// 4. Obtener lista de usuarios conectados
$stmt_users = $pdo->prepare(
    "SELECT p.owner_name, p.house_number 
     FROM user_sessions us
     JOIN properties p ON us.property_id = p.id
     WHERE us.meeting_id = ? AND us.status = 'connected' ORDER BY p.owner_name ASC"
);
$stmt_users->execute([$meeting['id']]);
$connected_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

// 5. Verificar si hay una votación activa
$stmt_poll = $pdo->prepare("SELECT id, question, options, duration_seconds FROM polls WHERE meeting_id = ? AND status = 'opened'");
$stmt_poll->execute([$meeting['id']]);
$active_poll = $stmt_poll->fetch(PDO::FETCH_ASSOC);

// Preparar respuesta JSON
$response = [
    'meeting_name' => $meeting['name'],
    'quorum' => [
        'percentage' => round($quorum_percentage, 2),
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