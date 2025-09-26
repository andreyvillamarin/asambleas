<?php
// api/login.php
session_start();

// --- Función de Depuración ---
function write_log($message) {
    $log_file = __DIR__ . '/debug.log';
    $timestamp = date('Y-m-d H:i:s');
    // Usar FILE_APPEND para añadir al archivo, LOCK_EX para evitar escrituras simultáneas
    file_put_contents($log_file, "[$timestamp] " . $message . "\n", FILE_APPEND | LOCK_EX);
}

write_log("--- Nueva solicitud a login.php ---");

// --- Inclusión de Dependencias ---
// Colocar el require después de la función de log para poder registrar errores de inclusión
try {
    require_once '../includes/db.php';
    require_once '../includes/functions.php';
    write_log("Dependencias cargadas correctamente.");
} catch (Exception $e) {
    write_log("Error fatal al cargar dependencias: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error interno del servidor.']);
    exit;
}


header('Content-Type: application/json');
$response = ['success' => false, 'message' => 'Solicitud inválida.'];
$action = $_POST['action'] ?? '';
write_log("Acción recibida: '$action'");

if ($action === 'check_id') {
    $id_card = $_POST['id_card'] ?? '';
    write_log("Iniciando 'check_id' para la cédula: '$id_card'");

    if (empty($id_card)) {
        write_log("Error: La cédula está vacía.");
        $response['message'] = 'Por favor, proporciona un número de cédula.';
        echo json_encode($response);
        exit;
    }

    try {
        write_log("Preparando la consulta a la base de datos.");
        $stmt = $pdo->prepare("SELECT owner_name, house_number, owner_email FROM properties WHERE owner_id_card = ?");
        write_log("Ejecutando la consulta.");
        $stmt->execute([$id_card]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        write_log("Consulta ejecutada. Resultado: " . ($user ? "Usuario encontrado" : "Usuario NO encontrado"));

        if ($user) {
            $email_parts = explode('@', $user['owner_email']);
            $name_part = substr($email_parts[0], 0, 1) . str_repeat('*', max(0, strlen($email_parts[0]) - 1));
            $domain_parts = explode('.', $email_parts[1]);
            $domain_name = substr($domain_parts[0], 0, 1) . str_repeat('*', max(0, strlen($domain_parts[0]) - 1));
            $domain_ext = end($domain_parts);
            $email_hint = "$name_part@$domain_name.$domain_ext";

            $response = [
                'success' => true,
                'data' => [
                    'name' => $user['owner_name'],
                    'house' => $user['house_number'],
                    'email_hint' => $email_hint
                ]
            ];
            write_log("Datos del usuario preparados para la respuesta.");
        } else {
            $response['message'] = 'La cédula no se encuentra registrada.';
            write_log("Respondiendo que la cédula no está registrada.");
        }
    } catch (PDOException $e) {
        write_log("¡ERROR DE PDO! Mensaje: " . $e->getMessage());
        $response['message'] = 'Error al consultar la base de datos.';
    } catch (Exception $e) {
        write_log("¡ERROR GENERAL! Mensaje: " . $e->getMessage());
        $response['message'] = 'Ocurrió un error inesperado.';
    }

} elseif ($action === 'send_code') {
    // ... (lógica futura)
    write_log("Acción 'send_code' invocada.");
    $response = ['success' => true, 'message' => 'Código enviado (simulado).'];

} elseif ($action === 'verify_code') {
    // ... (lógica futura)
    write_log("Acción 'verify_code' invocada.");
    $response = ['success' => true, 'redirect' => 'meeting.php (simulado)'];
}

write_log("Respuesta final enviada: " . json_encode($response));
echo json_encode($response);
?>