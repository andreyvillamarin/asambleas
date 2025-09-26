<?php
// Cambia 'TuContraseñaSuperSegura' por la contraseña real que quieras usar.
$password = 'TuContraseñaSuperSegura'; 

$hash = password_hash($password, PASSWORD_DEFAULT);

echo "Tu hash es: <br>";
echo "<strong>" . $hash . "</strong>";
?>