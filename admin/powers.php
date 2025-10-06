<?php
// admin/powers.php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$meeting_id = $_GET['meeting_id'] ?? null;
if (!$meeting_id) {
    die("Debe seleccionar una reunión desde la <a href='meetings.php'>página de reuniones</a> para gestionar los poderes.");
}

// Obtener todas las propiedades para los menús desplegables
$properties = $pdo->query("SELECT id, owner_name, house_number FROM properties ORDER BY house_number ASC")->fetchAll(PDO::FETCH_ASSOC);
$meeting_name = $pdo->prepare("SELECT name FROM meetings WHERE id = ?");
$meeting_name->execute([$meeting_id]);
$meeting_name = $meeting_name->fetchColumn();

// Manejar la asignación de un nuevo poder
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_power'])) {
    $giver_id = $_POST['giver_id'];
    $receiver_id = $_POST['receiver_id'];

    if ($giver_id && $receiver_id && $giver_id !== $receiver_id) {
        $stmt = $pdo->prepare("INSERT INTO powers (meeting_id, giver_property_id, receiver_property_id) VALUES (?, ?, ?)");
        if ($stmt->execute([$meeting_id, $giver_id, $receiver_id])) {
            echo "<div class='message success'>Poder asignado correctamente.</div>";
        }
    } else {
        echo "<div class='message error'>Error: Debe seleccionar dos propiedades diferentes.</div>";
    }
}

// Obtener los poderes ya asignados para esta reunión
$stmt_powers = $pdo->prepare(
    "SELECT 
        p.id,
        giver.owner_name as giver_name, giver.house_number as giver_house,
        receiver.owner_name as receiver_name, receiver.house_number as receiver_house
     FROM powers p
     JOIN properties giver ON p.giver_property_id = giver.id
     JOIN properties receiver ON p.receiver_property_id = receiver.id
     WHERE p.meeting_id = ?"
);
$stmt_powers->execute([$meeting_id]);
$assigned_powers = $stmt_powers->fetchAll(PDO::FETCH_ASSOC);

?>

<a href="meetings.php">&larr; Volver a Reuniones</a>
<h2>Gestión de Poderes para: "<?php echo htmlspecialchars($meeting_name); ?>"</h2>

<div class="form-container">
    <h3>Asignar Poder</h3>
    <form method="post">
        <label for="giver_id">Propiedad que DA el poder:</label>
        <select id="giver_id" name="giver_id" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($properties as $prop): ?>
                <option value="<?php echo $prop['id']; ?>"><?php echo htmlspecialchars($prop['house_number'] . ' - ' . $prop['owner_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <label for="receiver_id">Propiedad que RECIBE el poder (representante):</label>
        <select id="receiver_id" name="receiver_id" required>
            <option value="">-- Seleccione --</option>
            <?php foreach ($properties as $prop): ?>
                <option value="<?php echo $prop['id']; ?>"><?php echo htmlspecialchars($prop['house_number'] . ' - ' . $prop['owner_name']); ?></option>
            <?php endforeach; ?>
        </select>

        <button type="submit" name="assign_power">Asignar Poder</button>
    </form>
</div>

<div class="data-table">
    <h3>Poderes Asignados</h3>
    <table>
        <thead>
            <tr>
                <th>Quien da el poder (Poderdante)</th>
                <th>Quien recibe el poder (Apoderado)</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assigned_powers as $power): ?>
            <tr data-power-row-id="<?php echo $power['id']; ?>">
                <td><?php echo htmlspecialchars($power['giver_house'] . ' - ' . $power['giver_name']); ?></td>
                <td><?php echo htmlspecialchars($power['receiver_house'] . ' - ' . $power['receiver_name']); ?></td>
                <td>
                    <a href="edit_power.php?power_id=<?php echo $power['id']; ?>&meeting_id=<?php echo $meeting_id; ?>" class="button-edit">Editar</a>
                    <button class="button-delete" data-power-id="<?php echo $power['id']; ?>">Eliminar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php
require_once 'includes/footer.php';
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const deleteButtons = document.querySelectorAll('.button-delete');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function() {
            const powerId = this.dataset.powerId;
            if (confirm('¿Está seguro de que desea eliminar este poder?')) {
                fetch('../api/admin_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=delete_power&power_id=${powerId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Eliminar la fila de la tabla
                        const row = document.querySelector(`tr[data-power-row-id="${powerId}"]`);
                        if (row) {
                            row.remove();
                        }
                        alert(data.message); // O mostrar un mensaje más sutil
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Ocurrió un error al procesar la solicitud.');
                });
            }
        });
    });
});
</script>