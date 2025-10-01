<?php
// includes/cache_updater.php

function update_meeting_cache() {
    require_once __DIR__ . '/db.php';

    $cache_file_path = __DIR__ . '/../cache/meeting_data.json';
    $cache_dir = dirname($cache_file_path);

    // Asegurarse de que el directorio de caché exista
    if (!is_dir($cache_dir)) {
        mkdir($cache_dir, 0755, true);
    }

    // Obtener la reunión activa
    $stmt_meeting = $pdo->prepare("SELECT * FROM meetings WHERE status = 'opened' ORDER BY id DESC LIMIT 1");
    $stmt_meeting->execute();
    $meeting = $stmt_meeting->fetch(PDO::FETCH_ASSOC);

    if (!$meeting) {
        // Si no hay reunión activa, guardar un estado de "cerrada" en la caché
        file_put_contents($cache_file_path, json_encode(['status' => 'closed']));
        return;
    }

    // --- INICIO DE CÁLCULOS (se ejecuta solo cuando es necesario) ---

    // 1. Obtener asistentes actuales y sus poderes para calcular el coeficiente actual
    $stmt_attendees = $pdo->prepare("SELECT property_id FROM user_sessions WHERE meeting_id = ? AND status = 'connected'");
    $stmt_attendees->execute([$meeting['id']]);
    $attendee_property_ids = $stmt_attendees->fetchAll(PDO::FETCH_COLUMN);

    $represented_property_ids = $attendee_property_ids;
    if (!empty($attendee_property_ids)) {
        $placeholders = implode(',', array_fill(0, count($attendee_property_ids), '?'));
        $stmt_powers = $pdo->prepare("SELECT giver_property_id FROM powers WHERE meeting_id = ? AND receiver_property_id IN ($placeholders)");
        $stmt_powers->execute(array_merge([$meeting['id']], $attendee_property_ids));
        $power_giver_ids = $stmt_powers->fetchAll(PDO::FETCH_COLUMN);
        $represented_property_ids = array_unique(array_merge($attendee_property_ids, $power_giver_ids));
    }

    $current_coefficient_sum = 0;
    if (!empty($represented_property_ids)) {
        $placeholders_final = implode(',', array_fill(0, count($represented_property_ids), '?'));
        $stmt_total_coeff = $pdo->prepare("SELECT SUM(REPLACE(coefficient, ',', '.')) FROM properties WHERE id IN ($placeholders_final)");
        $stmt_total_coeff->execute(array_values($represented_property_ids));
        $current_coefficient_sum = $stmt_total_coeff->fetchColumn() ?: 0;
    }

    // 2. Obtener el coeficiente total (con fallback para reuniones antiguas)
    $total_coefficient = $meeting['total_coefficient'];
    if (empty($total_coefficient)) {
        $total_coefficient_query = $pdo->query("SELECT SUM(REPLACE(coefficient, ',', '.')) FROM properties");
        $total_coefficient = $total_coefficient_query->fetchColumn();
    }

    // 3. Calcular porcentaje de quórum
    $quorum_percentage = ($total_coefficient > 0) ? ($current_coefficient_sum / $total_coefficient) * 100 : 0;

    // 4. Obtener lista de usuarios conectados
    $stmt_users = $pdo->prepare(
        "SELECT p.id AS property_id, p.owner_name, p.house_number, p.coefficient,
                giver.house_number AS power_giver_house, giver.coefficient AS power_giver_coefficient
         FROM user_sessions us
         JOIN properties p ON us.property_id = p.id
         LEFT JOIN powers pow ON us.property_id = pow.receiver_property_id AND pow.meeting_id = us.meeting_id
         LEFT JOIN properties giver ON pow.giver_property_id = giver.id
         WHERE us.meeting_id = ? AND us.status = 'connected' ORDER BY p.owner_name ASC"
    );
    $stmt_users->execute([$meeting['id']]);
    $connected_users = $stmt_users->fetchAll(PDO::FETCH_ASSOC);

    // 5. Obtener votación activa
    $stmt_poll = $pdo->prepare("SELECT id, question, options, duration_seconds FROM polls WHERE meeting_id = ? AND status = 'opened'");
    $stmt_poll->execute([$meeting['id']]);
    $active_poll = $stmt_poll->fetch(PDO::FETCH_ASSOC);

    // --- FIN DE CÁLCULOS ---

    // Preparar el array de datos para la caché
    $cached_data = [
        'status' => 'opened',
        'meeting_name' => $meeting['name'],
        'start_time' => $meeting['start_time'] ? strtotime($meeting['start_time']) : null,
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
        'active_poll' => $active_poll
    ];

    // Guardar los datos en el archivo de caché
    file_put_contents($cache_file_path, json_encode($cached_data, JSON_PRETTY_PRINT));
}
?>