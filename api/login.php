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
        $stmt = $pdo->prepare("SELECT id, owner_name, house_number, owner_email FROM properties WHERE owner_id_card = ?");
        write_log("Ejecutando la consulta.");
        $stmt->execute([$id_card]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        write_log("Consulta ejecutada. Resultado: " . ($user ? "Usuario encontrado" : "Usuario NO encontrado"));

        if ($user) {
            // --- Verificación de Sesión Activa ---
            $stmt_session = $pdo->prepare("SELECT id FROM user_sessions WHERE property_id = ? AND status = 'connected'");
            $stmt_session->execute([$user['id']]);
            if ($stmt_session->fetch()) {
                $response['message'] = 'Este usuario ya ha ingresado a la plataforma, no se permite más de una sesión por usuario.';
                write_log("Acceso denegado: El usuario con cédula {$id_card} ya tiene una sesión activa.");
                echo json_encode($response);
                exit;
            }
            // --- Fin de Verificación ---

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
    $id_card = $_POST['id_card'] ?? '';
    write_log("Iniciando 'send_code' para la cédula: '$id_card'");

    if (empty($id_card)) {
        $response['message'] = 'No se proporcionó una cédula.';
        echo json_encode($response);
        exit;
    }

    try {
        // Obtener el email del usuario
        $stmt = $pdo->prepare("SELECT owner_email FROM properties WHERE owner_id_card = ?");
        $stmt->execute([$id_card]);
        $recipient_email = $stmt->fetchColumn();

        if (!$recipient_email) {
            $response['message'] = 'No se pudo encontrar el usuario para enviar el código.';
            write_log("Error en send_code: No se encontró un usuario con la cédula {$id_card}.");
            echo json_encode($response);
            exit;
        }

        // Generar y guardar el código de login
        $login_code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $pdo->prepare("UPDATE properties SET login_code = ?, login_code_expires_at = ? WHERE owner_id_card = ?");
        $stmt->execute([$login_code, $expires_at, $id_card]);
        
        write_log("Código generado ({$login_code}) para {$recipient_email}. Intentando enviar email...");

        // Enviar el email
        if (send_login_code($recipient_email, $login_code)) {
            $response = ['success' => true, 'message' => 'Código enviado correctamente.'];
        } else {
            $response['message'] = 'Hubo un error al enviar el código de acceso por correo. Por favor, contacta a soporte.';
        }

    } catch (PDOException $e) {
        write_log("¡ERROR DE PDO en send_code! Mensaje: " . $e->getMessage());
        $response['message'] = 'Error de base de datos. Por favor, asegúrate de que la migración de la base de datos se haya ejecutado.';
    } catch (Exception $e) {
        write_log("¡ERROR GENERAL en send_code! Mensaje: " . $e->getMessage());
        $response['message'] = 'Ocurrió un error inesperado al enviar el código.';
    }
} elseif ($action === 'verify_code') {
    $id_card = $_POST['id_card'] ?? '';
    $code = $_POST['code'] ?? '';
    write_log("Iniciando 'verify_code' para la cédula: '$id_card' con el código: '$code'");

    if (empty($id_card) || empty($code)) {
        $response['message'] = 'Por favor, proporciona la cédula y el código.';
        echo json_encode($response);
        exit;
    }

    try {
        // Obtener el código, la fecha de expiración y el ID de la propiedad
        $stmt = $pdo->prepare("SELECT id, login_code, login_code_expires_at FROM properties WHERE owner_id_card = ?");
        $stmt->execute([$id_card]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_data) {
            $response['message'] = 'Usuario no encontrado.';
            write_log("Error en verify_code: No se encontró usuario con la cédula {$id_card}.");
            echo json_encode($response);
            exit;
        }

        $stored_code = $user_data['login_code'];
        $expires_at_str = $user_data['login_code_expires_at'];
        
        // Verificar si el código es correcto y no ha expirado
        if ($stored_code === $code && (new DateTime() < new DateTime($expires_at_str))) {
            // El código es válido. Obtener la reunión activa o la próxima a iniciar.
            $stmt_meeting = $pdo->prepare("SELECT id FROM meetings WHERE status IN ('opened', 'created') ORDER BY meeting_date DESC, id DESC LIMIT 1");
            $stmt_meeting->execute();
            $active_meeting = $stmt_meeting->fetch(PDO::FETCH_ASSOC);

            if (!$active_meeting) {
                $response['message'] = 'No hay ninguna reunión programada en este momento. Por favor, inténtalo más tarde.';
                echo json_encode($response);
                exit;
            }

            // Iniciar sesión
            $_SESSION['user_id'] = $user_data['id']; // ID de la propiedad
            $_SESSION['logged_in'] = true;

            // Lógica de "Upsert" para la sesión del usuario
            $stmt_check = $pdo->prepare("SELECT id FROM user_sessions WHERE property_id = ? AND meeting_id = ?");
            $stmt_check->execute([$user_data['id'], $active_meeting['id']]);
            $existing_session_id = $stmt_check->fetchColumn();

            if ($existing_session_id) {
                // Si ya existe, actualizarla (re-conectar)
                $stmt_upsert = $pdo->prepare("UPDATE user_sessions SET status = 'connected', login_time = NOW(), logout_time = NULL WHERE id = ?");
                $stmt_upsert->execute([$existing_session_id]);
            } else {
                // Si no existe, insertarla
                $stmt_upsert = $pdo->prepare("INSERT INTO user_sessions (property_id, meeting_id, status, login_time) VALUES (?, ?, 'connected', NOW())");
                $stmt_upsert->execute([$user_data['id'], $active_meeting['id']]);
            }
            
            // Limpiar el código de la base de datos después de usarlo
            $stmt_clean = $pdo->prepare("UPDATE properties SET login_code = NULL, login_code_expires_at = NULL WHERE owner_id_card = ?");
            $stmt_clean->execute([$id_card]);

            write_log("Verificación exitosa para la cédula {$id_card}. Redirigiendo a meeting.php.");
            $response = [
                'success' => true,
                'redirect' => 'meeting.php' // URL real de la reunión
            ];
        } else {
            // El código es incorrecto o ha expirado
            write_log("Error en verify_code: El código para la cédula {$id_card} es incorrecto o ha expirado.");
            $response['message'] = 'El código es incorrecto o ha expirado. Por favor, inténtalo de nuevo.';
        }

    } catch (PDOException $e) {
        write_log("¡ERROR DE PDO en verify_code! Mensaje: " . $e->getMessage());
        $response['message'] = 'Error de base de datos al verificar el código.';
    } catch (Exception $e) {
        write_log("¡ERROR GENERAL en verify_code! Mensaje: " . $e->getMessage());
        $response['message'] = 'Ocurrió un error inesperado al verificar el código.';
    }
}

write_log("Respuesta final enviada: " . json_encode($response));
echo json_encode($response);
?>