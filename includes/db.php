<?php
// includes/db.php

// Establecer la zona horaria para todas las funciones de fecha/hora en PHP.
// Esto es CRÍTICO para asegurar que las comparaciones de tiempo entre la BD y PHP sean consistentes.
date_default_timezone_set('America/Bogota');

// Cargar la configuración local de forma segura.
// Se utiliza __DIR__ para asegurar que la ruta sea siempre relativa al archivo actual.
require_once __DIR__ . '/config.php';

try {
    // Establecer la conexión PDO con la base de datos.
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    
    // Configurar atributos de PDO para un manejo de errores robusto.
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Desactivar la emulación de sentencias preparadas para mayor seguridad.
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

} catch (PDOException $e) {
    // Enviar una respuesta JSON genérica en caso de error de conexión.
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de conexión con el servidor.']);
    exit;
}
?>