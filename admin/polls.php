<?php
// admin/polls.php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$meeting_id = $_GET['meeting_id'] ?? null;
if (!$meeting_id) {
    die("ID de reunión no especificado.");
}

// Obtener info de la reunión
$stmt_meeting = $pdo->prepare("SELECT name FROM meetings WHERE id = ?");
$stmt_meeting->execute([$meeting_id]);
$meeting_name = $stmt_meeting->fetchColumn();


// Manejar creación de votación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_poll'])) {
    $question = $_POST['question'];
    $duration = $_POST['duration'];
    // Convertir las opciones (separadas por nueva línea) en un array JSON
    $options = array_filter(array_map('trim', explode("\n", $_POST['options'])));
    $options_json = json_encode($options);

    $stmt = $pdo->prepare("INSERT INTO polls (meeting_id, question, options, duration_seconds) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$meeting_id, $question, $options_json, $duration])) {
        echo "<div class='message success'>Votación creada.</div>";
    }
}

// Obtener votaciones de esta reunión
$polls = $pdo->prepare("SELECT * FROM polls WHERE meeting_id = ? ORDER BY id");
$polls->execute([$meeting_id]);
?>

<a href="meetings.php">&larr; Volver a Reuniones</a>
<h2>Gestión de Votaciones para: "<?php echo htmlspecialchars($meeting_name); ?>"</h2>

<div class="form-container">
    <h3>Crear Nueva Votación</h3>
    <form method="post">
        <label for="question">Pregunta:</label>
        <textarea id="question" name="question" rows="3" required></textarea>

        <label for="options">Opciones (una por línea):</label>
        <textarea id="options" name="options" rows="4" required></textarea>

        <label for="duration">Duración (segundos):</label>
        <input type="number" id="duration" name="duration" value="60" required>

        <button type="submit" name="create_poll">Crear Votación</button>
    </form>
</div>

<div class="data-table">
    <h3>Votaciones Creadas</h3>
    </div>

<?php
require_once 'includes/footer.php';
?>