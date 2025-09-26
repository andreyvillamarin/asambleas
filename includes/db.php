<?php
// includes/db.php
require_once __DIR__ . '/../../../../config.php';

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    // Configurar PDO para que lance excepciones en caso de error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Emular consultas preparadas para mayor seguridad si la versión de MySQL es antigua
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    // En producción, no muestres el error detallado. Regístralo en un archivo de log.
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
?>