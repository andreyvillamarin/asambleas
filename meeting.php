<?php
session_start();

// 1. Verificar que el usuario tenga una sesión completamente válida.
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true || !isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// 2. Obtener los datos del usuario para mostrarlos en la página.
require_once 'includes/db.php'; // Incluir la conexión a la BD
$user_name = 'Usuario';
$house_number = 'N/A';
$coefficient = 'N/A';

if (isset($_SESSION['user_id'])) { // Usar user_id (ID de la propiedad) que se establece en el login
    $stmt = $pdo->prepare("SELECT owner_name, house_number, coefficient FROM properties WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        $user_name = $user['owner_name'];
        $house_number = $user['house_number'];
        $coefficient = $user['coefficient'];
    }
}
$user_property_id_for_js = $_SESSION['user_id'] ?? 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asamblea en Vivo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="assets/css/meeting.css?v=<?php echo time(); ?>">
    <link rel="icon" href="assets/favicon.png" type="image/png">
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
        <div id="meeting-timer-container" style="display: none;">
            <i class="fas fa-stopwatch"></i>
            <span>Tiempo de Reunión: <strong id="meeting-timer">00:00:00</strong></span>
        </div>
        <a href="logout.php" class="logout-button">Cerrar Sesión</a>
    </header>

    <main>
        <!-- Sala de Espera -->
        <div id="waiting-room">
            <div class="waiting-icon">
                <i class="fas fa-clock"></i>
            </div>
            <h1>La reunión aún no ha empezado</h1>
            <p>El anfitrión iniciará la reunión pronto. Por favor, espera.</p>
        </div>

        <!-- Vista de la Reunión (inicialmente oculta) -->
        <div id="meeting-view">
            <h1 id="meeting-name">Cargando nombre de la reunión...</h1>
            <div class="dashboard-grid">
                <div class="dashboard-card quorum-display">
                    <h2><i class="fas fa-chart-pie"></i> QUÓRUM</h2>
                    <div id="quorum-percentage" class="percentage">0%</div>
                    <div id="quorum-status" class="status no-quorum">Calculando...</div>
                </div>
                <div class="dashboard-card coefficient-display">
                    <h2><i class="fas fa-balance-scale"></i> COEFICIENTE</h2>
                    <div id="total-coefficient" class="percentage">0.00</div>
                </div>
                <div class="dashboard-card users-display">
                    <h2><i class="fas fa-user-friends"></i> ASISTENTES</h2>
                    <div id="connected-users-count" class="percentage">0</div>
                </div>
                <div class="dashboard-card user-list full-width">
                    <h2><i class="fas fa-users"></i> Lista de Asistentes</h2>
                    <table id="connected-users-table">
                        <thead>
                            <tr>
                                <th>Propietario</th>
                                <th>Propiedad</th>
                                <th>Coeficiente</th>
                                <th>Poder Otorgado por</th>
                                <th>Coeficiente Total</th>
                            </tr>
                        </thead>
                        <tbody id="connected-users-tbody">
                            <!-- Las filas se insertarán dinámicamente con JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Script para la hora de Bogotá
        function updateTime() {
            const now = new Date();
            const options = { timeZone: 'America/Bogota', hour: '2-digit', minute: '2-digit', second: '2-digit' };
            document.getElementById('current-time').textContent = now.toLocaleTimeString('es-CO', options);
        }
        setInterval(updateTime, 1000);
        updateTime();

        // Script para verificar el estado de la reunión
        const waitingRoom = document.getElementById('waiting-room');
        const meetingView = document.getElementById('meeting-view');

        async function checkMeetingStatus() {
            try {
                const response = await fetch(`api/meeting_status.php?t=${new Date().getTime()}`);
                const data = await response.json();

                if (data.status === 'opened') {
                    if (waitingRoom.style.display !== 'none') {
                        waitingRoom.style.display = 'none';
                        meetingView.style.display = 'block';
                        // Iniciar la actualización del dashboard solo cuando la reunión está activa
                        setInterval(updateUserDashboard, 5000);
                        updateUserDashboard();
                    }
                } else {
                    // Si el usuario estaba en la reunión y esta se cerró, forzar el logout.
                    if (meetingView.style.display === 'block') {
                        alert('La reunión ha finalizado. Serás redirigido a la página de inicio.');
                        window.location.href = 'logout.php';
                    } else {
                        // Si el usuario estaba en la sala de espera, mantenerlo ahí.
                        waitingRoom.style.display = 'block';
                        meetingView.style.display = 'none';
                    }
                }
            } catch (error) {
                console.error('Error al verificar el estado de la reunión:', error);
                // Mantener la vista de espera en caso de error para evitar una pantalla en blanco
                waitingRoom.style.display = 'block';
                meetingView.style.display = 'none';
            }
        }

        // Verificar el estado cada 5 segundos
        setInterval(checkMeetingStatus, 5000);
        // Verificar inmediatamente al cargar la página
        checkMeetingStatus();

        let timerInterval = null; // Variable para controlar el intervalo del temporizador

        async function updateUserDashboard() {
            try {
                const response = await fetch(`api/real_time_data.php?t=${new Date().getTime()}`);
                const data = await response.json();

                if (data.error) {
                    console.error('Error al cargar datos del dashboard:', data.error);
                    return;
                }

                // Iniciar el temporizador si no está ya iniciado y tenemos una hora de inicio
                if (data.start_time && !timerInterval) {
                    const startTime = new Date(data.start_time.replace(' ', 'T') + 'Z'); // Ajustar para compatibilidad
                    const timerContainer = document.getElementById('meeting-timer-container');
                    const timerEl = document.getElementById('meeting-timer');
                    
                    timerContainer.style.display = 'flex';

                    timerInterval = setInterval(() => {
                        const now = new Date();
                        const diff = now - startTime;
                        
                        const hours = String(Math.floor(diff / 3600000)).padStart(2, '0');
                        const minutes = String(Math.floor((diff % 3600000) / 60000)).padStart(2, '0');
                        const seconds = String(Math.floor((diff % 60000) / 1000)).padStart(2, '0');
                        
                        timerEl.textContent = `${hours}:${minutes}:${seconds}`;
                    }, 1000);
                }

                document.getElementById('meeting-name').textContent = data.meeting_name;
                
                const quorumPercentageEl = document.getElementById('quorum-percentage');
                quorumPercentageEl.textContent = `${data.quorum.percentage}%`;
                
                // Actualizar el nuevo campo de coeficiente total
                document.getElementById('total-coefficient').textContent = data.quorum.current_coefficient;

                // Actualizar el nuevo campo de contador de usuarios
                document.getElementById('connected-users-count').textContent = data.users.connected_count;

                // --- Verificación de Desconexión Forzada ---
                const currentUserPropertyId = <?php echo $user_property_id_for_js; ?>;
                const isCurrentUserConnected = data.users.list.some(user => user.property_id == currentUserPropertyId);
                
                if (!isCurrentUserConnected && timerInterval) { // timerInterval asegura que esto solo se ejecute si ya estaba en la reunión
                    alert('El administrador ha finalizado tu sesión.');
                    window.location.href = 'logout.php';
                    return; // Detener la ejecución para evitar más actualizaciones
                }
                // --- Fin de la Verificación ---

                const quorumStatusEl = document.getElementById('quorum-status');
                if (data.quorum.has_quorum) {
                    quorumStatusEl.textContent = 'Quórum Alcanzado';
                    quorumStatusEl.className = 'status has-quorum';
                } else {
                    quorumStatusEl.textContent = 'Esperando Quórum';
                    quorumStatusEl.className = 'status no-quorum';
                }

                const tableBody = document.getElementById('connected-users-tbody');
                tableBody.innerHTML = ''; // Limpiar tabla

                if (data.users.list.length > 0) {
                    data.users.list.forEach(user => {
                        const powerGiverInfo = user.power_giver_house 
                            ? `Prop. ${user.power_giver_house} (Coef: ${user.power_giver_coefficient})` 
                            : 'N/A';

                        const userCoeff = parseFloat(String(user.coefficient).replace(',', '.'));
                        const giverCoeff = user.power_giver_coefficient 
                            ? parseFloat(String(user.power_giver_coefficient).replace(',', '.')) 
                            : 0;
                        const totalCoeff = (userCoeff + giverCoeff).toFixed(5);

                        const row = `<tr>
                            <td data-label="Propietario">${user.owner_name}</td>
                            <td data-label="Propiedad">${user.house_number}</td>
                            <td data-label="Coeficiente">${user.coefficient}</td>
                            <td data-label="Poder Otorgado por">${powerGiverInfo}</td>
                            <td data-label="Coeficiente Total"><strong>${totalCoeff}</strong></td>
                        </tr>`;
                        tableBody.innerHTML += row;
                    });
                } else {
                    const row = `<tr><td colspan="5" style="text-align:center;">No hay asistentes conectados.</td></tr>`;
                    tableBody.innerHTML = row;
                }

            } catch (error) {
                console.error('Error fatal al actualizar el dashboard del usuario:', error);
            }
        }
    </script>
</body>
</html>