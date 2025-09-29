<?php
// api/vote.php
session_start();
require '../includes/db.php';

header('Content-Type: application/json');

// 1. Validar que el usuario esté logueado y su sesión activa en la base de datos
if (!isset($_SESSION['user_id']) || !isset($_SESSION['meeting_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado.']);
    exit;
}

// Verificación CRÍTICA: Asegurarse de que la sesión esté 'conectada' en la base de datos.
$stmt_session = $pdo->prepare("SELECT 1 FROM user_sessions WHERE property_id = ? AND meeting_id = ? AND status = 'connected'");
$stmt_session->execute([$_SESSION['user_id'], $_SESSION['meeting_id']]);
if ($stmt_session->fetchColumn() === false) {
    echo json_encode(['success' => false, 'message' => 'Tu sesión ha sido finalizada por el administrador y no puedes votar.']);
    exit;
}

$property_id = $_SESSION['user_id'];
$poll_id = $_POST['poll_id'] ?? null;
$selected_option = $_POST['selected_option'] ?? null;

// 2. Validar datos de entrada
if (!$poll_id || !$selected_option) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos.']);
    exit;
}

// 3. Verificar que la votación esté abierta y obtener el meeting_id
$stmt_poll = $pdo->prepare("SELECT meeting_id FROM polls WHERE id = ? AND status = 'opened'");
$stmt_poll->execute([$poll_id]);
$meeting_id = $stmt_poll->fetchColumn();

if (!$meeting_id) {
    echo json_encode(['success' => false, 'message' => 'La votación no está activa o no existe.']);
    exit;
}

// 4. Verificar que el usuario no haya votado ya en esta encuesta
$stmt_check = $pdo->prepare("SELECT id FROM votes WHERE poll_id = ? AND property_id = ?");
$stmt_check->execute([$poll_id, $property_id]);
if ($stmt_check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Ya has emitido un voto en esta encuesta.']);
    exit;
}

try {
    // 5. CALCULAR COEFICIENTE TOTAL (propio + poderes recibidos)
    // 5.1 Coeficiente propio
    $stmt_own_coeff = $pdo->prepare("SELECT coefficient FROM properties WHERE id = ?");
    $stmt_own_coeff->execute([$property_id]);
    $total_coefficient = (float) $stmt_own_coeff->fetchColumn();

    // 5.2 Sumar coeficientes de poderes recibidos
    $stmt_powers_coeff = $pdo->prepare(
        "SELECT SUM(p.coefficient) 
         FROM powers pw 
         JOIN properties p ON pw.giver_property_id = p.id
         WHERE pw.meeting_id = ? AND pw.receiver_property_id = ?"
    );
    $stmt_powers_coeff->execute([$meeting_id, $property_id]);
    $powers_coefficient = (float) $stmt_powers_coeff->fetchColumn();
    $total_coefficient += $powers_coefficient;

    // 6. Insertar el voto en la base de datos
    $stmt_insert = $pdo->prepare(
        "INSERT INTO votes (poll_id, property_id, selected_option, coefficient_applied) 
         VALUES (?, ?, ?, ?)"
    );
    $stmt_insert->execute([$poll_id, $property_id, $selected_option, $total_coefficient]);

    echo json_encode(['success' => true, 'message' => '¡Voto registrado con éxito!']);

} catch (PDOException $e) {
    // En producción, registrar el error en un log en lugar de mostrarlo
    echo json_encode(['success' => false, 'message' => 'Error al registrar el voto: ' . $e->getMessage()]);
}