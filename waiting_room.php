<?php
// waiting_room.php
// Este archivo actúa como un intermediario para solucionar una redirección inesperada.
// Redirige inmediatamente al usuario a la página principal de la reunión.

header("Location: meeting.php");
exit;
?>