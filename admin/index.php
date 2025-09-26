<?php
// admin/index.php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Obtener la reunión activa
$stmt = $pdo->prepare("SELECT * FROM meetings WHERE status = 'opened' ORDER BY id DESC LIMIT 1");
$stmt->execute();
$active_meeting = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<h2>Dashboard - Reunión Activa</h2>

<?php if ($active_meeting): ?>
<div class="meeting-controls" data-meeting-id="<?php echo $active_meeting['id']; ?>">
    <h3>Controlando: <?php echo htmlspecialchars($active_meeting['name']); ?></h3>
    <button id="btn-close-meeting">Cerrar Reunión</button>
    <button id="btn-disconnect-all" class="danger">Desconectar a Todos</button>
</div>

<div class="live-dashboard">
    <div class="stat-card">
        <h4>Usuarios Conectados</h4>
        <p id="connected-users-count">...</p>
    </div>
    <div class="stat-card">
        <h4>Coeficiente Actual</h4>
        <p id="current-coefficient">...</p>
    </div>
    <div class="stat-card">
        <h4>Quorum en Tiempo Real</h4>
        <p id="quorum-percentage">...</p>
    </div>
</div>

<div class="polls-control">
    <h3>Votaciones para esta Reunión</h3>
    <div id="polls-list"></div>
</div>

<?php else: ?>
<p>No hay ninguna reunión activa en este momento. Ve a la sección de <a href="meetings.php">Reuniones</a> para abrir una.</p>
<?php endif; ?>

<?php
require_once 'includes/footer.php';
?>