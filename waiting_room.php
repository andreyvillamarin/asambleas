<?php
session_start();

// 1. Verificar que el usuario tenga una sesión válida.
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// 2. Obtener los datos del usuario para mostrarlos en la página.
require_once 'includes/db.php';
$user_name = 'Usuario';
$house_number = 'N/A';
$coefficient = 'N/A';

if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT owner_name, house_number, coefficient FROM properties WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $user_name = $user['owner_name'];
        $house_number = $user['house_number'];
        $coefficient = $user['coefficient'];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sala de Espera - Asamblea</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/meeting.css?v=<?php echo time(); ?>">
    <link rel="icon" href="assets/favicon.png" type="image/png">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            background-color: #f4f7f6;
        }
        main {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            flex-grow: 1;
            text-align: center;
            padding: 20px;
        }
        .waiting-box {
            padding: 40px;
            border-radius: 10px;
            background: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            max-width: 600px;
        }
        .waiting-icon {
            font-size: 4rem;
            color: #007bff;
            margin-bottom: 20px;
        }
        .waiting-box h1 {
            font-size: 2rem;
            color: #333;
        }
        .waiting-box p {
            font-size: 1.2rem;
            color: #666;
        }
    </style>
</head>
<body>
    <header class="dashboard-header">
        <div class="logo">
            <img src="https://qdos.network/demos/asambleas/assets/logo.png" alt="Logo de la Empresa">
        </div>
        <div class="user-info">
            Bienvenido, <strong><?php echo htmlspecialchars($user_name); ?></strong> (Propiedad: <?php echo htmlspecialchars($house_number); ?> | Coeficiente: <?php echo htmlspecialchars($coefficient); ?>) |
            Hora Actual: <span id="current-time"></span>
        </div>
        <a href="logout.php" class="logout-button">Cerrar Sesión</a>
    </header>

    <main>
        <div class="waiting-box">
            <div class="waiting-icon">
                <i class="fas fa-clock fa-spin"></i>
            </div>
            <h1>Sala de Espera</h1>
            <p>La reunión aún no ha comenzado. Serás redirigido automáticamente cuando el anfitrión la inicie.</p>
        </div>
    </main>

    <script>
        // Script para la hora de Bogotá
        function updateTime() {
            const now = new Date();
            const options = { timeZone: 'America/Bogota', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('current-time').textContent = now.toLocaleTimeString('es-CO', options);
        }
        setInterval(updateTime, 1000);
        updateTime();

        const STATUS_CHECK_INTERVAL = 5000; // 5 segundos

        // Esta función intenta registrar la sesión del usuario.
        async function registerSessionAndRedirect() {
            try {
                const response = await fetch('api/register_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    }
                });

                const result = await response.json();

                if (result.success) {
                    // Solo si el registro es exitoso, se detiene el chequeo y se redirige.
                    clearInterval(statusInterval);
                    window.location.href = 'meeting.php';
                } else {
                    // Si falla, el error se loguea en la consola y el intervalo seguirá activo para reintentar.
                    console.error('Error al registrar la sesión:', result.message);
                }
            } catch (error) {
                console.error('Error de red al registrar la sesión:', error);
            }
        }

        // Esta función verifica el estado de la reunión periódicamente.
        async function checkMeetingStatus() {
            try {
                const response = await fetch(`api/meeting_status.php?t=${new Date().getTime()}`);
                const data = await response.json();

                if (data.status === 'opened') {
                    // Si la reunión está abierta, intenta registrar la sesión.
                    // El intervalo solo se detendrá si el registro es exitoso.
                    await registerSessionAndRedirect();
                }
            } catch (error) {
                console.error('Error al verificar el estado de la reunión:', error);
            }
        }

        // Iniciar la verificación inmediatamente y luego cada 5 segundos.
        const statusInterval = setInterval(checkMeetingStatus, STATUS_CHECK_INTERVAL);
        checkMeetingStatus(); // Llamada inicial
    </script>
</body>
</html>