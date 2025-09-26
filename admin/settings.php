<?php
// admin/settings.php
require_once 'includes/auth_check.php';
require_once 'includes/header.php';

$success_msg = '';
$error_msg = '';

// --- LÓGICA PARA GUARDAR AJUSTES GENERALES ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $whatsapp_number = $_POST['whatsapp_number'] ?? '';
    
    // Usamos INSERT ... ON DUPLICATE KEY UPDATE para insertar o actualizar
    $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('admin_whatsapp', ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    if ($stmt->execute([$whatsapp_number, $whatsapp_number])) {
        $success_msg = 'Ajustes guardados correctamente.';
    } else {
        $error_msg = 'Error al guardar los ajustes.';
    }
}

// --- LÓGICA PARA CAMBIAR CONTRASEÑA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $error_msg = 'Las contraseñas nuevas no coinciden.';
    } else {
        // Obtener el hash actual del admin logueado
        $stmt = $pdo->prepare("SELECT password_hash FROM administrators WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $current_hash = $stmt->fetchColumn();

        if (password_verify($old_password, $current_hash)) {
            // La contraseña antigua es correcta, proceder a actualizar
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE administrators SET password_hash = ? WHERE id = ?");
            if ($update_stmt->execute([$new_hash, $_SESSION['admin_id']])) {
                $success_msg = 'Contraseña actualizada con éxito.';
            } else {
                $error_msg = 'Error al actualizar la contraseña.';
            }
        } else {
            $error_msg = 'La contraseña antigua es incorrecta.';
        }
    }
}


// Obtener los valores actuales de la base de datos para mostrarlos
$settings_stmt = $pdo->query("SELECT * FROM settings");
$current_settings = $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
$whatsapp = $current_settings['admin_whatsapp'] ?? '';

?>
<h2>Configuración</h2>

<?php if ($success_msg): ?><div class="message success"><?php echo $success_msg; ?></div><?php endif; ?>
<?php if ($error_msg): ?><div class="message error"><?php echo $error_msg; ?></div><?php endif; ?>

<div class="form-container">
    <h3>Ajustes Generales</h3>
    <form method="post">
        <label for="whatsapp_number">Número de WhatsApp para Soporte:</label>
        <input type="text" id="whatsapp_number" name="whatsapp_number" value="<?php echo htmlspecialchars($whatsapp); ?>" placeholder="+573001234567">
        <button type="submit" name="save_settings">Guardar Ajustes</button>
    </form>
</div>

<div class="form-container">
    <h3>Cambiar Contraseña</h3>
    <form method="post">
        <label for="old_password">Contraseña Antigua:</label>
        <input type="password" id="old_password" name="old_password" required>

        <label for="new_password">Contraseña Nueva:</label>
        <input type="password" id="new_password" name="new_password" required>

        <label for="confirm_password">Confirmar Contraseña Nueva:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        
        <button type="submit" name="change_password">Cambiar Contraseña</button>
    </form>
</div>

<?php
require_once 'includes/footer.php';
?>