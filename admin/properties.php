<?php
// admin/properties.php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$success_msg = '';
$error_msg = '';

// Mensaje de éxito al ser redirigido desde la página de edición
if (isset($_GET['status']) && $_GET['status'] === 'success') {
    $success_msg = 'Propiedad actualizada exitosamente.';
}

// --- LÓGICA PARA BORRAR PROPIEDAD ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_property'])) {
    $property_id_to_delete = $_POST['property_id'];
    $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
    if ($stmt->execute([$property_id_to_delete])) {
        $success_msg = 'Propiedad borrada exitosamente.';
    } else {
        $error_msg = 'Error al borrar la propiedad.';
    }
}

// --- LÓGICA PARA AGREGAR PROPIEDAD MANUALMENTE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_property'])) {
    $stmt = $pdo->prepare(
        "INSERT INTO properties (house_number, coefficient, owner_id_card, owner_name, owner_email) 
         VALUES (?, ?, ?, ?, ?)"
    );
    try {
        $stmt->execute([
            $_POST['house_number'],
            $_POST['coefficient'],
            $_POST['owner_id_card'],
            $_POST['owner_name'],
            $_POST['owner_email']
        ]);
        $success_msg = 'Propiedad agregada exitosamente.';
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $error_msg = "Error: La cédula ya se encuentra registrada.";
        } else {
            $error_msg = "Error al agregar la propiedad.";
        }
    }
}

// Lógica para manejar la subida del archivo CSV... (puedes expandir esto más adelante)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    // ... Tu lógica de importación aquí ...
    $success_msg = 'Importación desde CSV completada (lógica de ejemplo).';
}

// Obtener todas las propiedades para mostrarlas en la tabla
$properties = $pdo->query("SELECT * FROM properties ORDER BY house_number ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<h2>Gestión de Propiedades</h2>

<?php if ($success_msg): ?><div class="message success"><?php echo $success_msg; ?></div><?php endif; ?>
<?php if ($error_msg): ?><div class="message error"><?php echo $error_msg; ?></div><?php endif; ?>


<div class="form-container">
    <h3>Añadir Nueva Propiedad</h3>
    <form method="post">
        <input type="text" name="house_number" placeholder="Número de Casa/Apto" required>
        <input type="text" name="coefficient" placeholder="Coeficiente (ej: 0.12345)" required>
        <input type="text" name="owner_id_card" placeholder="Cédula Propietario" required>
        <input type="text" name="owner_name" placeholder="Nombre Propietario" required>
        <input type="email" name="owner_email" placeholder="Correo Propietario" required>
        <button type="submit" name="add_property">Añadir Propiedad</button>
    </form>
</div>


<div class="data-actions">
    <div class="import-csv">
        <h3>Importar desde CSV</h3>
        <p>El archivo debe tener las columnas: house_number, coefficient, owner_id_card, owner_name, owner_email</p>
        <form method="post" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit">Importar</button>
        </form>
    </div>
    <div class="export-csv">
        <h3>Exportar a CSV</h3>
        <a href="export_properties.php" class="button">Descargar CSV</a>
    </div>
</div>


<div class="data-table">
    <h3>Listado de Propiedades</h3>
    <table>
        <thead>
            <tr>
                <th>Casa/Apto</th>
                <th>Coeficiente</th>
                <th>Cédula</th>
                <th>Propietario</th>
                <th>Email</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($properties)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;">No hay propiedades registradas.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($properties as $prop): ?>
                <tr>
                    <td><?php echo htmlspecialchars($prop['house_number']); ?></td>
                    <td><?php echo htmlspecialchars($prop['coefficient']); ?></td>
                    <td><?php echo htmlspecialchars($prop['owner_id_card']); ?></td>
                    <td><?php echo htmlspecialchars($prop['owner_name']); ?></td>
                    <td><?php echo htmlspecialchars($prop['owner_email']); ?></td>
                    <td class="actions">
                        <a href="edit_property.php?id=<?php echo $prop['id']; ?>" class="button">Editar</a>
                        <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres borrar esta propiedad?');">
                            <input type="hidden" name="property_id" value="<?php echo $prop['id']; ?>">
                            <button type="submit" name="delete_property" class="danger">Borrar</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>


<?php
require_once 'includes/footer.php';
?>