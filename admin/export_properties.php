<?php
// admin/export_properties.php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';

$filename = "propiedades_" . date('Y-m-d') . ".csv";

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

$output = fopen('php://output', 'w');
// Escribir la cabecera
fputcsv($output, ['house_number', 'coefficient', 'owner_id_card', 'owner_name', 'owner_email']);

// Obtener los datos y escribirlos en el archivo
$stmt = $pdo->query("SELECT house_number, coefficient, owner_id_card, owner_name, owner_email FROM properties");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    fputcsv($output, $row);
}

fclose($output);
exit;