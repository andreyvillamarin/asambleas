<?php
// api/get_setting.php
header('Content-Type: application/json');
require_once '../includes/db.php';

$response = ['success' => false, 'value' => null];
$key = $_GET['key'] ?? '';

if (empty($key)) {
    $response['message'] = 'No se proporcionó ninguna clave de ajuste.';
    echo json_encode($response);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
    $stmt->execute([$key]);
    $value = $stmt->fetchColumn();

    if ($value !== false) {
        $response = [
            'success' => true,
            'value' => $value
        ];
    } else {
        $response['message'] = 'La clave de ajuste no fue encontrada.';
    }

} catch (PDOException $e) {
    // En un entorno de producción, sería mejor loguear el error que mostrarlo.
    $response['message'] = 'Error en la base de datos.';
    // error_log('Error en get_setting.php: ' . $e->getMessage());
}

echo json_encode($response);
?>