<?php
// admin/meetings.php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

// Manejar la creación de una nueva reunión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_meeting'])) {
    $name = $_POST['name'];
    $date = $_POST['date'];
    $quorum = $_POST['quorum'];

    $stmt = $pdo->prepare("INSERT INTO meetings (name, meeting_date, required_quorum_percentage) VALUES (?, ?, ?)");
    if ($stmt->execute([$name, $date, $quorum])) {
        echo "<div class='message success'>Reunión creada exitosamente.</div>";
    } else {
        echo "<div class='message error'>Error al crear la reunión.</div>";
    }
}

// Obtener todas las reuniones
$meetings = $pdo->query("SELECT * FROM meetings ORDER BY meeting_date DESC")->fetchAll(PDO::FETCH_ASSOC);

$success_msg = '';
if (isset($_GET['status']) && $_GET['status'] === 'success_update') {
    $success_msg = 'Reunión actualizada exitosamente.';
}
?>

<?php if ($success_msg): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-plus-circle"></i> Crear Nueva Reunión</h2>
    </div>
    <div class="card-body">
        <form method="post" class="form-container">
            <label for="name">Nombre de la Reunión:</label>
            <input type="text" id="name" name="name" required>

            <label for="date">Fecha y Hora:</label>
            <input type="datetime-local" id="date" name="date" required>

            <label for="quorum">Quorum Requerido (%):</label>
            <input type="number" id="quorum" name="quorum" min="1" max="100" step="0.01" required>

            <button type="submit" name="create_meeting" class="btn btn-success"><i class="fas fa-save"></i> Crear Reunión</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-list"></i> Reuniones Creadas</h2>
    </div>
    <div class="card-body">
        <table>
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Fecha</th>
                    <th>Quorum Req.</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($meetings as $meeting): ?>
                <tr>
                    <td><?php echo htmlspecialchars($meeting['name']); ?></td>
                    <td><?php echo date("d/m/Y h:i A", strtotime($meeting['meeting_date'])); ?></td>
                    <td><?php echo $meeting['required_quorum_percentage']; ?>%</td>
                    <td><span class="status-<?php echo $meeting['status']; ?>"><?php echo ucfirst(htmlspecialchars($meeting['status'])); ?></span></td>
                    <td class="action-buttons">
                        <a href="edit_meeting.php?id=<?php echo $meeting['id']; ?>" class="btn"><i class="fas fa-edit"></i> Editar</a>
                        <?php if ($meeting['status'] === 'created' || $meeting['status'] === 'closed'): ?>
                            <button class="btn btn-success open-meeting" data-id="<?php echo $meeting['id']; ?>"><i class="fas fa-play-circle"></i> Abrir</button>
                        <?php elseif ($meeting['status'] === 'opened'): ?>
                            <button class="btn btn-danger close-meeting" data-id="<?php echo $meeting['id']; ?>"><i class="fas fa-stop-circle"></i> Cerrar</button>
                        <?php endif; ?>
                        <a href="polls.php?meeting_id=<?php echo $meeting['id']; ?>" class="btn"><i class="fas fa-poll"></i> Votaciones</a>
                        <a href="powers.php?meeting_id=<?php echo $meeting['id']; ?>" class="btn"><i class="fas fa-gavel"></i> Poderes</a>
                        <a href="report.php?meeting_id=<?php echo $meeting['id']; ?>" class="btn btn-secondary" target="_blank"><i class="fas fa-file-alt"></i> Reporte</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Añadir esto al script.js o aquí mismo
document.addEventListener('DOMContentLoaded', () => {
    // Botón para Abrir Reunión
    document.querySelectorAll('.open-meeting').forEach(button => {
        button.addEventListener('click', async (e) => {
            const meetingId = e.target.dataset.id;
            if (confirm('¿Estás seguro de que quieres abrir esta reunión? Se cerrará cualquier otra que esté activa.')) {
                const formData = new FormData();
                formData.append('action', 'open_meeting');
                formData.append('meeting_id', meetingId);
                
                const response = await fetch('../api/admin_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    window.location.reload();
                }
            }
        });
    });

    // Botón para Cerrar Reunión
    document.querySelectorAll('.close-meeting').forEach(button => {
        button.addEventListener('click', async (e) => {
            const meetingId = e.target.dataset.id;
            if (confirm('¿Estás seguro de que quieres cerrar esta reunión?')) {
                const formData = new FormData();
                formData.append('action', 'close_meeting');
                formData.append('meeting_id', meetingId);

                const response = await fetch('../api/admin_actions.php', { method: 'POST', body: formData });
                const result = await response.json();
                alert(result.message);
                if (result.success) {
                    window.location.reload();
                }
            }
        });
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>