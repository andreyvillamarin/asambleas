<?php
// includes/functions.php

// Cargar el autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

function send_login_code($recipient_email, $code) {
    global $pdo; // Usar la conexión PDO global definida en db.php

    try {
        write_log("Iniciando send_login_code para {$recipient_email}.");

        // 1. Obtener la API Key de la base de datos
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'brevo_api_key'");
        $stmt->execute();
        $api_key = $stmt->fetchColumn();

        if (!$api_key) {
            write_log("Error Crítico: La API Key de Brevo (sendinblue) no se encontró en la base de datos.");
            return false;
        }
        write_log("API Key obtenida correctamente.");

        // 2. Configurar el cliente de la API de Sendinblue (nombre antiguo de Brevo)
        // Se usa "SendinBlue" porque es la librería que está instalada en vendor/.
        $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $api_key);
        $apiInstance = new SendinBlue\Client\Api\TransactionalEmailsApi(new GuzzleHttp\Client(), $config);
        write_log("Cliente de la API de Sendinblue configurado.");

        // 3. Crear el objeto del email
        $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail([
            'to' => [['email' => $recipient_email]],
            'sender' => ['email' => 'no-reply@qdos.network', 'name' => 'Sistema de Asambleas'],
            'subject' => 'Tu código de acceso a la asamblea',
            'htmlContent' => "<html><body><h1>Tu código de acceso es:</h1><p style='font-size: 24px; font-weight: bold;'>{$code}</p><p>Este código expira en 5 minutos.</p></body></html>",
        ]);
        write_log("Objeto de email creado. Listo para enviar.");

        // 4. Enviar el email
        $apiInstance->sendTransacEmail($sendSmtpEmail);
        write_log("Llamada a sendTransacEmail completada exitosamente para {$recipient_email}.");
        return true;

    } catch (Throwable $e) { // Capturar cualquier tipo de error (Exception o Error)
        write_log("¡ERROR FATAL en send_login_code! Mensaje: " . $e->getMessage() . " | Archivo: " . $e->getFile() . " | Línea: " . $e->getLine());
        return false;
    }
}