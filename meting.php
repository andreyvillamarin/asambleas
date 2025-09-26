<?php
session_start();
// Si el usuario no está logueado, redirigirlo al login
if (!isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <title>Asamblea en Vivo</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="user-info">
            Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong> |
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

    <script src="assets/js/main.js"></script>
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