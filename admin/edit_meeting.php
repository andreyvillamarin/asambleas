<?php
// admin/edit_meeting.php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$meeting_id = $_GET['id'] ?? null;
if (!$meeting_id) {
    echo "<div class='alert alert-danger'>No se especificó ninguna reunión para editar.</div>";
    require_once 'includes/footer.php';
    exit;
}

// Lógica de actualización
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_meeting'])) {
    $meeting_id_to_update = $_POST['meeting_id'];
    $name = $_POST['name'];
    $date = $_POST['date'];
    $quorum = $_POST['quorum'];

    $stmt = $pdo->prepare("UPDATE meetings SET name = ?, meeting_date = ?, required_quorum_percentage = ? WHERE id = ?");
    
    if ($stmt->execute([$name, $date, $quorum, $meeting_id_to_update])) {
        // Redirigir con un mensaje de éxito
        header("Location: meetings.php?status=success_update");
        exit;
    } else {
        echo "<div class='alert alert-danger'>Error al actualizar la reunión.</div>";
    }
}

// Obtener los datos actuales de la reunión
$stmt = $pdo->prepare("SELECT * FROM meetings WHERE id = ?");
$stmt->execute([$meeting_id]);
$meeting = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$meeting) {
    echo "<div class='alert alert-danger'>Reunión no encontrada.</div>";
    require_once 'includes/footer.php';
    exit;
}
?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-edit"></i> Editar Reunión</h2>
    </div>
    <div class="card-body">
        <form method="post" class="form-container">
            <input type="hidden" name="meeting_id" value="<?php echo $meeting['id']; ?>">

            <label for="name">Nombre de la Reunión:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($meeting['name']); ?>" required>

            <label for="date">Fecha y Hora:</label>
            <input type="datetime-local" id="date" name="date" value="<?php echo date('Y-m-d\TH:i', strtotime($meeting['meeting_date'])); ?>" required>

            <label for="quorum">Quorum Requerido (%):</label>
            <input type="number" id="quorum" name="quorum" value="<?php echo htmlspecialchars($meeting['required_quorum_percentage']); ?>" min="1" max="100" step="0.01" required>

            <button type="submit" name="update_meeting" class="btn btn-primary"><i class="fas fa-save"></i> Guardar Cambios</button>
            <a href="meetings.php" class="btn btn-secondary"><i class="fas fa-times-circle"></i> Cancelar</a>
        </form>
    </div>
</div>

<?php
require_once 'includes/footer.php';
?>