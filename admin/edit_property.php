<?php
// admin/edit_property.php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$property_id = $_GET['id'] ?? null;
if (!$property_id) {
    die("ID de propiedad no especificado.");
}

// --- LÓGICA PARA GUARDAR LOS CAMBIOS (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare(
        "UPDATE properties SET 
            house_number = ?, 
            coefficient = ?, 
            owner_id_card = ?, 
            owner_name = ?, 
            owner_email = ? 
         WHERE id = ?"
    );
    
    try {
        $stmt->execute([
            $_POST['house_number'],
            $_POST['coefficient'],
            $_POST['owner_id_card'],
            $_POST['owner_name'],
            $_POST['owner_email'],
            $property_id
        ]);
        // Redirigir a la lista de propiedades con un mensaje de éxito
        header("Location: properties.php?status=success");
        exit;
    } catch (PDOException $e) {
        if ($e->errorInfo[1] == 1062) {
            $error_msg = "Error: La cédula ya se encuentra registrada en otra propiedad.";
        } else {
            $error_msg = "Error al actualizar la propiedad.";
        }
    }
}

// --- LÓGICA PARA OBTENER LOS DATOS (GET) ---
$stmt_get = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
$stmt_get->execute([$property_id]);
$property = $stmt_get->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    die("Propiedad no encontrada.");
}
?>

<a href="properties.php">&larr; Volver al listado</a>
<h2>Editar Propiedad</h2>

<?php if (isset($error_msg)): ?><div class="message error"><?php echo $error_msg; ?></div><?php endif; ?>

<div class="form-container">
    <form method="post">
        <label for="house_number">Número de Casa/Apto:</label>
        <input type="text" id="house_number" name="house_number" value="<?php echo htmlspecialchars($property['house_number']); ?>" required>

        <label for="coefficient">Coeficiente:</label>
        <input type="text" id="coefficient" name="coefficient" value="<?php echo htmlspecialchars($property['coefficient']); ?>" required>

        <label for="owner_id_card">Cédula Propietario:</label>
        <input type="text" id="owner_id_card" name="owner_id_card" value="<?php echo htmlspecialchars($property['owner_id_card']); ?>" required>
        
        <label for="owner_name">Nombre Propietario:</label>
        <input type="text" id="owner_name" name="owner_name" value="<?php echo htmlspecialchars($property['owner_name']); ?>" required>

        <label for="owner_email">Correo Propietario:</label>
        <input type="email" id="owner_email" name="owner_email" value="<?php echo htmlspecialchars($property['owner_email']); ?>" required>

        <button type="submit" name="update_property">Guardar Cambios</button>
    </form>
</div>

<?php
require_once 'includes/footer.php';
?>