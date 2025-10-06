<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// admin/login.php

// Cargar la configuración de la sesión ANTES de iniciarla.
require_once __DIR__ . '/../includes/session_config.php';

session_start();
require_once __DIR__ . '/../includes/db.php'; // Conexión a la BD
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor, ingresa usuario y contraseña.';
    } else {
        // Buscar el administrador en la nueva tabla
        $stmt = $pdo->prepare("SELECT * FROM administrators WHERE username = ?");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verificar si el usuario existe y la contraseña es correcta
        if ($admin && password_verify($password, $admin['password_hash'])) {
            // ¡Éxito! Iniciar sesión
            $_SESSION['is_admin'] = true;
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: index.php');
            exit;
        } else {
            // Credenciales incorrectas
            $error = 'Usuario o contraseña incorrectos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-container">
        <h1>Acceso de Administrador</h1>
        <form method="post" action="login.php">
            <label for="username">Usuario:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="password">Contraseña:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Ingresar</button>
            
            <?php if ($error): ?>
                <p class="error" style="color:red; text-align:center;"><?php echo $error; ?></p>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>