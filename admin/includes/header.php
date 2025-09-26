<?php
// admin/includes/header.php
require_once __DIR__ . '/../../includes/db.php'; // Conexión a la BD
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración de Asambleas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <header class="admin-header">
        <h1>Admin Asambleas</h1>
<nav>
    <a href="index.php">Dashboard</a>
    <a href="meetings.php">Reuniones</a>
    <a href="properties.php">Propiedades</a>
    <a href="settings.php">Configuración</a> <a href="logout.php">Salir</a>
</nav>
    </header>
    <main class="admin-main">