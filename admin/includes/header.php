<?php
// admin/includes/header.php
require_once __DIR__ . '/../../includes/db.php'; // Conexión a la BD
require_once __DIR__ . '/auth_check.php'; // Verificar si el usuario está logueado
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/style.css?v=<?php echo time(); ?>">
    <link rel="icon" href="../assets/favicon.png" type="image/png">
</head>
<body>
<div class="admin-wrapper">
    <aside class="sidebar">
        <div class="sidebar-header">
            <img src="https://qdos.network/demos/asambleas/assets/logo.png" alt="Logo" class="logo">
            <h2>Admin</h2>
        </div>
        <nav class="sidebar-nav">
            <a href="index.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="meetings.php"><i class="fas fa-users"></i> Reuniones</a>
            <a href="properties.php"><i class="fas fa-building"></i> Propiedades</a>
            <a href="polls.php"><i class="fas fa-poll"></i> Votaciones</a>
            <a href="report.php"><i class="fas fa-chart-bar"></i> Reportes</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Configuración</a>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Cerrar Sesión</a>
        </div>
    </aside>
    <div class="main-content">
        <header class="main-header">
            <h1>Bienvenido al Panel</h1>
            <div class="header-user-info">
                <span><i class="fas fa-user-circle"></i> Admin</span>
            </div>
        </header>
        <main class="content-body">