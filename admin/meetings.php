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
?>

<h2>Gestión de Reuniones</h2>

<div class="form-container">
    <h3>Crear Nueva Reunión</h3>
    <form method="post">
        <label for="name">Nombre de la Reunión:</label>
        <input type="text" id="name" name="name" required>

        <label for="date">Fecha y Hora:</label>
        <input type="datetime-local" id="date" name="date" required>

        <label for="quorum">Quorum Requerido (%):</label>
        <input type="number" id="quorum" name="quorum" min="1" max="100" step="0.01" required>

        <button type="submit" name="create_meeting">Crear Reunión</button>
    </form>
</div>

<div class="data-table">
    <h3>Reuniones Creadas</h3>
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
                <td><?php echo $meeting['meeting_date']; ?></td>
                <td><?php echo $meeting['required_quorum_percentage']; ?>%</td>
                <td><span class="status-<?php echo $meeting['status']; ?>"><?php echo htmlspecialchars($meeting['status']); ?></span></td>
                <td class="actions">
    <?php if ($meeting['status'] === 'created'): ?>
        <button class="action-btn open-meeting" data-id="<?php echo $meeting['id']; ?>">Abrir</button>
    <?php endif; ?>
    <a href="polls.php?meeting_id=<?php echo $meeting['id']; ?>" class="action-btn">Votaciones</a>
    
    <a href="powers.php?meeting_id=<?php echo $meeting['id']; ?>" class="action-btn">Poderes</a>
    
    <a href="report.php?meeting_id=<?php echo $meeting['id']; ?>" class="action-btn" target="_blank">Reporte</a>
</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
// Añadir esto al script.js o aquí mismo
document.querySelectorAll('.open-meeting').forEach(button => {
    button.addEventListener('click', async (e) => {
        const meetingId = e.target.dataset.id;
        if (confirm('¿Estás seguro de que quieres abrir esta reunión? Se cerrará cualquier otra que esté activa.')) {
            const formData = new FormData();
            formData.append('action', 'open_meeting');
            formData.append('meeting_id', meetingId);
            
            const response = await fetch('../api/admin_actions.php', {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            alert(result.message);
            if (result.success) {
                window.location.reload();
            }
        }
    });
});
</script>

<?php
require_once 'includes/footer.php';
?>