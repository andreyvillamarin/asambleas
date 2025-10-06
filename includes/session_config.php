<?php
// includes/session_config.php

// --- Configuración del Tiempo de Vida de la Sesión ---
// Se establece un tiempo de vida de 3600 segundos (60 minutos) para la sesión.
// Esto anula la configuración predeterminada del servidor y previene desconexiones prematuras.
ini_set('session.gc_maxlifetime', 3600);

// También se establece el tiempo de vida de la cookie de sesión para que coincida.
ini_set('session.cookie_lifetime', 3600);
?>