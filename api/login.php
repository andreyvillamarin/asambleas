<?php
// api/login.php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Solicitud inválida.'];
$action = $_POST['action'] ?? '';

if ($action === 'check_id') {
    $id_card = $_POST['id_card'] ?? '';
    $stmt = $pdo->prepare("SELECT owner_name, house_number, owner_email FROM properties WHERE owner_id_card = ?");
    $stmt->execute([$id_card]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generar pista del correo: j****@g****.com
        $email_parts = explode('@', $user['owner_email']);
        $name = substr($email_parts[0], 0, 1) . str_repeat('*', strlen($email_parts[0]) - 1);
        $domain_parts = explode('.', $email_parts[1]);
        $domain = substr($domain_parts[0], 0, 1) . str_repeat('*', strlen($domain_parts[0]) - 1) . '.' . end($domain_parts);
        
        $response = [
            'success' => true,
            'data' => [
                'name' => $user['owner_name'],
                'house' => $user['house_number'],
                'email_hint' => $name . '@' . $domain
            ]
        ];
    } else {
        $response['message'] = 'La cédula no se encuentra registrada.';
    }
} elseif ($action === 'send_code') {
    $id_card = $_POST['id_card'] ?? '';
    // ... (Código para generar un código de 6 dígitos, guardarlo en la tabla `login_codes` con una expiración de 5 minutos y enviarlo por email usando la API de Brevo)
    // $email = ... (obtener email de la BD con $id_card)
    // send_login_code($email, $code); // Función que usa Brevo
    $response = ['success' => true, 'message' => 'Se ha enviado un código a tu correo.'];

} elseif ($action === 'verify_code') {
    $id_card = $_POST['id_card'] ?? '';
    $code = $_POST['code'] ?? '';
    // ... (Código para verificar que el código existe, no ha expirado y corresponde al usuario)
    // Si es válido:
    // 1. Obtener datos del usuario desde la tabla `properties`
    // 2. Iniciar sesión:
    $_SESSION['user_id'] = $property['id'];
    $_SESSION['user_name'] = $property['owner_name'];
    // 3. Registrar la sesión en la tabla `user_sessions`
    // 4. Devolver éxito
    $response = ['success' => true, 'redirect' => 'meeting.php'];
}

echo json_encode($response);
?>