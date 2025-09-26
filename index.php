<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ingreso a la Asamblea</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <h1>Bienvenido a la Asamblea Virtual</h1>
        
        <div id="step-1">
            <label for="id_card">Número de Cédula:</label>
            <input type="text" id="id_card" name="id_card" required>
            <button id="btn-check-id">Consultar</button>
        </div>

        <div id="step-2" class="hidden">
            <h3>¿Son correctos tus datos?</h3>
            <p><strong>Nombre:</strong> <span id="user-name"></span></p>
            <p><strong>Propiedad:</strong> <span id="user-house"></span></p>
            <p><strong>Correo:</strong> <span id="user-email-hint"></span></p>
            <button id="btn-confirm-data">Sí, son correctos</button>
            <button id="btn-reject-data">No, son incorrectos</button>
        </div>
        
        <div id="step-3" class="hidden">
            <p>Hemos enviado un código de 6 dígitos a tu correo. Ingrésalo a continuación:</p>
            <label for="login_code">Código de Verificación:</label>
            <input type="text" id="login_code" name="login_code" maxlength="6">
            <button id="btn-verify-code">Ingresar</button>
        </div>
        
        <div id="error-message" class="error"></div>
        <div id="whatsapp-contact" class="hidden">
            <p>Si tus datos son incorrectos, por favor contacta al administrador.</p>
            <a href="https://wa.me/NUMERO_DE_WHATSAPP" target="_blank" class="whatsapp-button">Contactar por WhatsApp</a>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>