<?php
// logout.php

// Cargar la configuración de la sesión ANTES de iniciarla.
require_once 'includes/session_config.php';

session_start();

if (isset($_SESSION['user_id'])) {
    require 'includes/db.php';
    require_once 'includes/cache_updater.php'; // Incluir el actualizador de caché
    
    // Marcar como desconectado en la tabla de sesiones activas
    $stmt = $pdo->prepare("UPDATE user_sessions SET status = 'disconnected', logout_time = NOW() WHERE property_id = ? AND status = 'connected'");
    $stmt->execute([$_SESSION['user_id']]);

    update_meeting_cache(); // Actualizar caché para reflejar la salida del usuario
}

// Destruir todas las variables de sesión.
$_SESSION = array();

// Si se desea destruir la sesión completamente, borre también la cookie de sesión.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión.
session_destroy();

// Redirigir a la página de login.
header("Location: index.php");
exit;
?>