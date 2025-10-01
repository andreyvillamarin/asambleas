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

        // 2. Configurar el cliente de la API de Brevo (anteriormente Sendinblue)
        $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $api_key);
        // --- FIX: Forzar el host de la API de Brevo ---
        // La librería antigua puede estar apuntando a un endpoint obsoleto y lento.
        // Se establece explícitamente el host correcto para asegurar una comunicación rápida.
        $config->setHost('https://api.brevo.com/v3');
        
        // --- FIX: Añadir timeouts al cliente Guzzle ---
        // Se establece un timeout de conexión y de respuesta para evitar que la
        // aplicación se quede colgada si la API de Brevo no responde a tiempo.
        $guzzleClient = new GuzzleHttp\Client([
            'timeout' => 10, // Timeout de respuesta en segundos
            'connect_timeout' => 10, // Timeout de conexión en segundos
        ]);
        $apiInstance = new SendinBlue\Client\Api\TransactionalEmailsApi($guzzleClient, $config);
        write_log("Cliente de la API de Brevo configurado con timeouts y el host: " . $config->getHost());


        // 3. Crear el objeto del email
        $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail([
            'to' => [['email' => $recipient_email]],
            'sender' => ['email' => 'no-reply@qdos.network', 'name' => 'Sistema de Asambleas'],
            'subject' => 'Tu código de acceso a la asamblea',
            'htmlContent' => "<html><body><h1>Tu código de acceso es:</h1><p style='font-size: 24px; font-weight: bold;'>{$code}</p><p>Este código expira en 5 minutos.</p></body></html>",
        ]);
        write_log("Objeto de email creado. Listo para enviar.");

        // 4. Enviar el email y registrar la respuesta
        $result = $apiInstance->sendTransacEmail($sendSmtpEmail);
        write_log("Llamada a sendTransacEmail completada. Respuesta de la API: " . $result);
        
        // Comprobar si la respuesta contiene un ID de mensaje, lo que indica éxito.
        if ($result->getMessageId()) {
            write_log("Email enviado exitosamente a {$recipient_email} con Message ID: " . $result->getMessageId());
            return true;
        } else {
            write_log("La API no devolvió un Message ID. El envío pudo haber fallado silenciosamente.");
            return false;
        }

    } catch (Throwable $e) { // Capturar y registrar cualquier tipo de error (Exception o Error)
        // Se registra el error con más detalle para facilitar la depuración futura.
        write_log("¡ERROR FATAL en send_login_code! Mensaje: " . $e->getMessage() . " | Archivo: " . $e->getFile() . " | Línea: " . $e->getLine() . " | Trace: " . $e->getTraceAsString());
        return false;
    }
}