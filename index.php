<?php
session_start();

// Si el usuario ya está logueado, redirigirlo directamente a la reunión.
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: meeting.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso a la Asamblea</title>
    <link rel="stylesheet" href="https://qdos.network/demos/asambleas/assets/css/style.css">
    <link rel="icon" href="https://qdos.network/demos/asambleas/assets/favicon.ico" type="image/x-icon">
</head>
<body>
    <div id="login-container" class="login-container">
        <!-- Logo de la empresa -->
        <img src="https://qdos.network/demos/asambleas/assets/logo.png" alt="Logo de la Empresa" style="max-width: 150px; margin-bottom: 1.5rem;">
        
        <h1>Bienvenido a la Asamblea Virtual</h1>

        <!-- Paso 1: Ingresar Cédula -->
        <div id="step-1">
            <p>Por favor, ingresa tu número de cédula para comenzar.</p>
            <form id="form-check-id">
                <label for="id_card">Número de Cédula:</label>
                <input type="text" id="id_card" name="id_card" required placeholder="Ej: 123456789">
                <button type="submit" id="btn-check-id">Consultar</button>
            </form>
        </div>

        <!-- Paso 2: Confirmar Datos -->
        <div id="step-2" class="hidden">
            <h3>¿Son correctos tus datos?</h3>
            <p><strong>Nombre:</strong> <span id="user-name"></span></p>
            <p><strong>Propiedad:</strong> <span id="user-house"></span></p>
            <p><strong>Correo (pista):</strong> <span id="user-email-hint"></span></p>
            <button id="btn-confirm-data">Sí, son correctos</button>
            <button id="btn-reject-data">No, son incorrectos</button>
        </div>
        
        <!-- Paso 3: Ingresar Código -->
        <div id="step-3" class="hidden">
            <p>Hemos enviado un código de 6 dígitos a tu correo. Ingrésalo a continuación para acceder.</p>
            <form id="form-verify-code">
                <label for="login_code">Código de Verificación:</label>
                <input type="text" id="login_code" name="login_code" maxlength="6" placeholder="******">
                <button type="submit" id="btn-verify-code">Ingresar</button>
            </form>
        </div>
        
        <!-- Contenedor de Mensajes -->
        <div id="error-message" class="error"></div>
        
        <!-- Contacto por WhatsApp -->
        <div id="whatsapp-contact" class="hidden" style="margin-top: 1.5rem;">
            <p>Si tus datos no son correctos, por favor contacta al administrador para que podamos ayudarte.</p>
            <a href="https://wa.me/NUMERO_DE_WHATSAPP" target="_blank" class="whatsapp-button">Contactar por WhatsApp</a>
        </div>
    </div>

    <script src="https://qdos.network/demos/asambleas/assets/js/main.js?v=<?php echo time(); ?>"></script>
</body>
</html>