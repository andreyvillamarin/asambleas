<?php
// api/real_time_data.php
// Este script es ultra-rápido y ahora también es "autocorrector".

header('Content-Type: application/json');

$cache_file_path = __DIR__ . '/../cache/meeting_data.json';

if (!file_exists($cache_file_path)) {
    // Fallback de autocorrección: si la caché no existe, se genera ahora.
    // Esto previene condiciones de carrera donde un usuario solicita datos
    // antes de que el primer evento (abrir reunión, unirse) haya creado la caché.
    require_once __DIR__ . '/../includes/cache_updater.php';
    update_meeting_cache();
}

// Servir el archivo de caché (ya sea que existiera o que se acabara de crear).
readfile($cache_file_path);

exit;
?>