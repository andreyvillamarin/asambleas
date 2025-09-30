<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/db.php';

// Asegurarse de que el usuario est��esta logueado para consultar el estado
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    echo json_encode(['status' => 'error', 'message' => 'No autorizado']);
    exit;
}

try {
    // Consultar si hay alguna reunion con estado 'opened'
    $stmt = $pdo->prepare("SELECT status FROM meetings WHERE status = 'opened' LIMIT 1");
    $stmt->execute();
    $meeting = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($meeting) {
        // Si se encuentra una reunion abierta, devolver 'opened'
        echo json_encode(['status' => 'opened']);
    } else {
        // Si no, devolver 'inactive'
        echo json_encode(['status' => 'inactive']);
    }
} catch (PDOException $e) {
    // Manejo de errores de base de datos
    echo json_encode(['status' => 'error', 'message' => 'Error en la base de datos: ' . $e->getMessage()]);
}
?>