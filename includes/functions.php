<?php
// includes/functions.php

// Instala la librería de Brevo con Composer: composer require sendinblue/api-v3-sdk
require_once __DIR__ . '/../vendor/autoload.php';

function send_login_code($recipient_email, $code) {
    // Esta función es un EJEMPLO. Debes adaptarla a la versión actual de la SDK de Brevo.
    
    // Obtener la API Key de la base de datos
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = 'brevo_api_key'");
    $stmt->execute();
    $api_key = $stmt->fetchColumn();

    if (!$api_key) {
        // Manejar el error, la API key no está configurada
        error_log("Brevo API Key no encontrada en la base de datos.");
        return false;
    }

    $config = SendinBlue\Client\Configuration::getDefaultConfiguration()->setApiKey('api-key', $api_key);
    $apiInstance = new SendinBlue\Client\Api\TransactionalEmailsApi(new GuzzleHttp\Client(), $config);
    
    $sendSmtpEmail = new \SendinBlue\Client\Model\SendSmtpEmail([
        'to' => [['email' => $recipient_email]],
        'sender' => ['email' => 'no-reply@qdos.network', 'name' => 'Sistema de Asambleas'],
        'subject' => 'Tu código de acceso a la asamblea',
        'htmlContent' => "<html><body><h1>Tu código de acceso es:</h1><p><b>{$code}</b></p><p>Este código expira en 5 minutos.</p></body></html>"
    ]);

    try {
        $apiInstance->sendTransacEmail($sendSmtpEmail);
        return true;
    } catch (Exception $e) {
        error_log('Excepción al llamar a Brevo: '. $e->getMessage());
        return false;
    }
}