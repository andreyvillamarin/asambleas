<?php
// includes/db.php
// includes/db.php

// La ruta correcta para acceder a config.php fuera de public_html
$configPath = '/home/qdosnetw/config.php';

if (!file_exists($configPath)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error cr赤tico: No se pudo encontrar el archivo de configuraci車n.']);
    exit;
}

require_once $configPath;

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // En un entorno de producci車n, es mejor registrar este error que mostrarlo.
    // error_log('Error de conexi車n a la base de datos: ' . $e->getMessage());
    
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error de conexi車n con la base de datos.']);
    exit;
}
?>