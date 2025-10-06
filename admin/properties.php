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

// --- LÓGICA PARA IMPORTAR DESDE CSV ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    if ($_FILES['csv_file']['error'] == UPLOAD_ERR_OK && is_uploaded_file($_FILES['csv_file']['tmp_name'])) {
        $csv_file = $_FILES['csv_file']['tmp_name'];
        
        $pdo->beginTransaction();
        
        try {
            $handle = fopen($csv_file, "r");
            if ($handle === FALSE) {
                throw new Exception("No se pudo abrir el archivo CSV.");
            }

            // Omitir la cabecera
            fgetcsv($handle, 1000, ",");

            $stmt = $pdo->prepare(
                "INSERT INTO properties (house_number, coefficient, owner_id_card, owner_name, owner_email) 
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE 
                 house_number=VALUES(house_number), 
                 coefficient=VALUES(coefficient), 
                 owner_name=VALUES(owner_name), 
                 owner_email=VALUES(owner_email)"
            );
            
            $imported_count = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (count($data) == 5) { // Asegurarse de que hay 5 columnas
                    $stmt->execute($data);
                    $imported_count++;
                }
            }
            fclose($handle);

            $pdo->commit();
            $success_msg = "Importación completada. Se procesaron " . $imported_count . " registros.";

        } catch (Exception $e) {
            $pdo->rollBack();
            $error_msg = "Error durante la importación: " . $e->getMessage();
        }
    } else {
        $error_msg = 'Error al subir el archivo CSV.';
    }
}

// Obtener todas las propiedades para mostrarlas en la tabla
$properties = $pdo->query("SELECT * FROM properties ORDER BY house_number ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<?php if ($success_msg): ?><div class="alert alert-success"><?php echo $success_msg; ?></div><?php endif; ?>
<?php if ($error_msg): ?><div class="alert alert-danger"><?php echo $error_msg; ?></div><?php endif; ?>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-plus-circle"></i> Añadir Nueva Propiedad</h2>
    </div>
    <div class="card-body">
        <form method="post" class="form-container">
            <label for="house_number">Número de Casa/Apto:</label>
            <input type="text" id="house_number" name="house_number" required>
            <label for="coefficient">Coeficiente (ej: 0.12345):</label>
            <input type="text" id="coefficient" name="coefficient" required>
            <label for="owner_id_card">Cédula Propietario:</label>
            <input type="text" id="owner_id_card" name="owner_id_card" required>
            <label for="owner_name">Nombre Propietario:</label>
            <input type="text" id="owner_name" name="owner_name" required>
            <label for="owner_email">Correo Propietario:</label>
            <input type="email" id="owner_email" name="owner_email" required>
            <button type="submit" name="add_property" class="btn btn-success"><i class="fas fa-plus"></i> Añadir Propiedad</button>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-file-import"></i> Importar / <i class="fas fa-file-export"></i> Exportar</h2>
    </div>
    <div class="card-body data-actions">
        <div class="import-csv">
            <h4>Importar desde CSV</h4>
            <p>El archivo debe tener las columnas: house_number, coefficient, owner_id_card, owner_name, owner_email</p>
            <form method="post" enctype="multipart/form-data">
                <input type="file" name="csv_file" accept=".csv" required>
                <button type="submit" class="btn"><i class="fas fa-upload"></i> Importar</button>
            </form>
        </div>
        <div class="export-csv">
            <h4>Exportar a CSV</h4>
            <a href="export_properties.php" class="btn btn-secondary"><i class="fas fa-download"></i> Descargar CSV</a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2><i class="fas fa-list"></i> Listado de Propiedades</h2>
    </div>
    <div class="card-body">
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
                        <td class="action-buttons">
                            <a href="edit_property.php?id=<?php echo $prop['id']; ?>" class="btn"><i class="fas fa-edit"></i> Editar</a>
                            <form method="post" style="display:inline;" onsubmit="return confirm('¿Estás seguro de que quieres borrar esta propiedad?');">
                                <input type="hidden" name="property_id" value="<?php echo $prop['id']; ?>">
                                <button type="submit" name="delete_property" class="btn btn-danger"><i class="fas fa-trash"></i> Borrar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>


<?php
require_once 'includes/footer.php';
?>