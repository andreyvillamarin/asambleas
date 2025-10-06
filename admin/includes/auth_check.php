<?php
// admin/includes/auth_check.php

// Cargar la configuración de la sesión ANTES de iniciarla.
// La ruta es relativa al directorio 'admin/includes/'.
require_once __DIR__ . '/../../includes/session_config.php';

session_start();
if (!isset($_SESSION['is_admin']) || $_SESSION['is_admin'] !== true) {
    header('Location: login.php');
    exit;
}