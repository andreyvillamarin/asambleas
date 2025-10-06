<?php
// admin/edit_power.php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$power_id = $_GET['power_id'] ?? null;
$meeting_id = $_GET['meeting_id'] ?? null;

if (!$power_id || !$meeting_id) {
    die("Faltan parámetros para editar el poder.");
}

// Obtener los detalles del poder actual
$stmt_power = $pdo->prepare("SELECT giver_property_id, receiver_property_id FROM powers WHERE id = ?");
$stmt_power->execute([$power_id]);
$power = $stmt_power->fetch(PDO::FETCH_ASSOC);

if (!$power) {
    die("El poder especificado no existe.");
}

// Obtener todas las propiedades para los menús desplegables
$properties = $pdo->query("SELECT id, owner_name, house_number FROM properties ORDER BY house_number ASC")->fetchAll(PDO::FETCH_ASSOC);

// Manejar la actualización del poder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_power'])) {
    $giver_id = $_POST['giver_id'];
    $receiver_id = $_POST['receiver_id'];

    if ($giver_id && $receiver_id && $giver_id !== $receiver_id) {
        $stmt_update = $pdo->prepare("UPDATE powers SET giver_property_id = ?, receiver_property_id = ? WHERE id = ?");
        if ($stmt_update->execute([$giver_id, $receiver_id, $power_id])) {
            // Redirigir de vuelta a la página de poderes
            header("Location: powers.php?meeting_id=" . $meeting_id . "&update=success");
            exit;
        }
    } else {
        echo "<div class='message error'>Error: Debe seleccionar dos propiedades diferentes.</div>";
    }
}
?>

<a href="powers.php?meeting_id=<?php echo $meeting_id; ?>">&larr; Volver a Gestión de Poderes</a>
<h2>Editar Poder</h2>

<div class="form-container">
    <form method="post">
        <label for="giver_id">Propiedad que DA el poder:</label>
        <select id="giver_id" name="giver_id" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($properties as $prop): ?>
                <option value="<?php echo $prop['id']; ?>" <?php echo ($prop['id'] == $power['giver_property_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($prop['house_number'] . ' - ' . $prop['owner_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label for="receiver_id">Propiedad que RECIBE el poder (representante):</label>
        <select id="receiver_id" name="receiver_id" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($properties as $prop): ?>
                <option value="<?php echo $prop['id']; ?>" <?php echo ($prop['id'] == $power['receiver_property_id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($prop['house_number'] . ' - ' . $prop['owner_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="update_power">Actualizar Poder</button>
    </form>
</div>

<?php
require_once 'includes/footer.php';
?>