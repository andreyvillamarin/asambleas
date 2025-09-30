<?php
// admin/index.php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Obtener la reunión activa
$stmt = $pdo->prepare("SELECT * FROM meetings WHERE status = 'opened' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$active_meeting = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<div class="meeting-controls" data-meeting-id="<?php echo $active_meeting['id'] ?? ''; ?>">
    <h2>Dashboard</h2>
    <button id="btn-disconnect-all" class="btn btn-danger" <?php if (!$active_meeting) echo 'disabled'; ?>>
        <i class="fas fa-power-off"></i> Desconectar a Todos
    </button>
</div>

<?php if ($active_meeting): ?>
<div class="active-meeting-info">
    <h3><i class="fas fa-broadcast-tower"></i> Reunión Activa: <?php echo htmlspecialchars($active_meeting['name']); ?></h3>
    <button id="btn-close-meeting" class="btn btn-secondary"><i class="fas fa-stop-circle"></i> Cerrar Reunión Actual</button>
</div>

<div class="dashboard-grid">
    <div class="card stat-card">
        <div class="card-header">
            <h2><i class="fas fa-users"></i> Usuarios Conectados</h2>
        </div>
        <div class="stat-value" id="connected-users-count">...</div>
    </div>
    <div class="card stat-card">
        <div class="card-header">
            <h2><i class="fas fa-balance-scale"></i> Coeficiente Actual</h2>
        </div>
        <div class="stat-value" id="current-coefficient">...</div>
    </div>
    <div class="card stat-card">
        <div class="card-header">
            <h2><i class="fas fa-chart-pie"></i> Quorum en Tiempo Real</h2>
        </div>
        <div class="stat-value" id="quorum-percentage">...</div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-user-check"></i> Asistentes Conectados</h2>
    </div>
    <div class="card-body">
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
            <tbody>
                <!-- Las filas se insertarán dinámicamente con JavaScript -->
            </tbody>
        </table>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-poll"></i> Votaciones para esta Reunión</h2>
    </div>
    <div class="card-body">
        <div id="polls-list"></div>
    </div>
</div>

<?php else: ?>
<p>No hay ninguna reunión activa en este momento. Ve a la sección de <a href="meetings.php">Reuniones</a> para abrir una.</p>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
?>