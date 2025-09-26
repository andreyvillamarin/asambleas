<?php
session_start();

// 1. Verificar si el usuario está logueado. Si no, redirigir al login.
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: index.php");
    exit;
}

// 2. Obtener los datos del usuario para mostrarlos en la página.
require_once 'includes/db.php'; // Incluir la conexión a la BD
$user_name = 'Usuario'; // Valor por defecto
if (isset($_SESSION['user_id_card'])) {
    $stmt = $pdo->prepare("SELECT owner_name FROM properties WHERE owner_id_card = ?");
    $stmt->execute([$_SESSION['user_id_card']]);
    $user_name = $stmt->fetchColumn() ?: 'Usuario Desconocido';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Asamblea en Vivo</title>
    <!-- Usar rutas absolutas para los assets para evitar errores 404 -->
    <link rel="stylesheet" href="/demos/asambleas/assets/css/style.css?v=<?php echo time(); ?>">
</head>
<body>
    <header>
        <div class="user-info">
            Bienvenido, <strong><?php echo htmlspecialchars($user_name); ?></strong> |
            Hora Actual: <span id="current-time"></span>
        </div>
        <a href="logout.php" class="logout-button">Cerrar Sesión</a>
    </header>

    <main id="meeting-view">
        <h1 id="meeting-name">Cargando nombre de la reunión...</h1>
        
        <div class="dashboard">
            <div class="quorum-display">
                <h2>QUÓRUM EN TIEMPO REAL</h2>
                <div id="quorum-percentage" class="percentage">0%</div>
                <div id="quorum-status" class="status no-quorum">Calculando...</div>
            </div>
            
            <div class="user-list">
                <h3>Asistentes Conectados</h3>
                <ul id="connected-users-list">
                    <li>Cargando...</li>
                </ul>
            </div>
        </div>
    </main>

    <script src="/demos/asambleas/assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Pequeño script para la hora de Bogotá
        function updateTime() {
            const now = new Date();
            const options = { timeZone: 'America/Bogota', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('current-time').textContent = now.toLocaleTimeString('es-CO', options);
        }
        setInterval(updateTime, 1000);
        updateTime();
    </script>
</body>
</html>