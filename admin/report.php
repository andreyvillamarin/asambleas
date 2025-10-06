<?php
// admin/report.php
require_once 'includes/auth_check.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../fpdf/fpdf.php'; // Asegúrate que la ruta sea correcta

$meeting_id = $_GET['meeting_id'] ?? null;
if (!$meeting_id) die("ID de reunión no válido.");

// 1. OBTENER DATOS DE LA REUNIÓN
$stmt = $pdo->prepare("SELECT * FROM meetings WHERE id = ?");
$stmt->execute([$meeting_id]);
$meeting = $stmt->fetch(PDO::FETCH_ASSOC);

// 2. OBTENER LISTA DE ASISTENTES CONECTADOS
$stmt_attendees = $pdo->prepare(
    "SELECT p.house_number, p.owner_name, p.coefficient
     FROM user_sessions us JOIN properties p ON us.property_id = p.id
     WHERE us.meeting_id = ? AND us.status = 'connected' ORDER BY p.owner_name"
);
$stmt_attendees->execute([$meeting_id]);
$attendees = $stmt_attendees->fetchAll(PDO::FETCH_ASSOC);

// 3. CALCULAR QUORUM FINAL
$final_quorum = 0;
foreach ($attendees as $attendee) {
    // Se convierte la coma en punto para el cálculo
    $final_quorum += floatval(str_replace(',', '.', $attendee['coefficient']));
}
$total_coefficient_str = $pdo->query("SELECT SUM(REPLACE(coefficient, ',', '.')) FROM properties")->fetchColumn();
$total_coefficient = floatval($total_coefficient_str);
$quorum_percentage = ($total_coefficient > 0) ? ($final_quorum / $total_coefficient) * 100 : 0;

// 4. GENERAR EL PDF
class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(80);
        $this->Cell(30, 10, 'Acta de Asamblea', 0, 0, 'C');
        $this->Ln(20);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo(), 0, 0, 'C');
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Info General
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, utf8_decode($meeting['name']), 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 10, 'Fecha: ' . date('d/m/Y H:i', strtotime($meeting['meeting_date'])), 0, 1);
$pdf->Ln(5);

// Quorum
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Resumen del Quorum', 0, 1);
$pdf->SetFont('Arial', '', 12);
$pdf->Cell(0, 8, 'Quorum final alcanzado: ' . number_format($quorum_percentage, 2) . '%', 0, 1);
$pdf->Cell(0, 8, 'Coeficiente total de asistentes: ' . number_format($final_quorum, 4), 0, 1);
$pdf->Cell(0, 8, 'Total de asistentes: ' . count($attendees), 0, 1);
$pdf->Ln(10);

// Lista de Asistentes
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Lista de Asistentes Conectados', 0, 1);
$pdf->Ln(2);

// Cabecera de la tabla
$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(90, 7, 'Propietario', 1);
$pdf->Cell(40, 7, 'Propiedad', 1);
$pdf->Cell(40, 7, 'Coeficiente', 1);
$pdf->Ln();

// Datos de la tabla
$pdf->SetFont('Arial', '', 10);
if (empty($attendees)) {
    $pdf->Cell(170, 10, 'No habia asistentes conectados.', 1, 0, 'C');
} else {
    foreach ($attendees as $attendee) {
        $pdf->Cell(90, 7, utf8_decode($attendee['owner_name']), 1);
        $pdf->Cell(40, 7, utf8_decode($attendee['house_number']), 1);
        $pdf->Cell(40, 7, $attendee['coefficient'], 1);
        $pdf->Ln();
    }
}

$pdf->Output(); // Muestra el PDF en el navegador
?>