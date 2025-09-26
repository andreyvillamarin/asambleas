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

// 2. OBTENER LISTA DE ASISTENTES
$stmt_attendees = $pdo->prepare(
    "SELECT p.house_number, p.owner_name, p.coefficient 
     FROM user_sessions us JOIN properties p ON us.property_id = p.id 
     WHERE us.meeting_id = ? ORDER BY p.owner_name"
);
$stmt_attendees->execute([$meeting_id]);
$attendees = $stmt_attendees->fetchAll(PDO::FETCH_ASSOC);

// 3. CALCULAR QUORUM FINAL
$final_quorum = 0;
foreach ($attendees as $attendee) {
    $final_quorum += $attendee['coefficient'];
}
$total_coefficient = $pdo->query("SELECT SUM(coefficient) FROM properties")->fetchColumn();
$quorum_percentage = ($total_coefficient > 0) ? ($final_quorum / $total_coefficient) * 100 : 0;

// 4. OBTENER VOTACIONES Y RESULTADOS
$stmt_polls = $pdo->prepare("SELECT * FROM polls WHERE meeting_id = ? AND status = 'closed'");
$stmt_polls->execute([$meeting_id]);
$polls = $stmt_polls->fetchAll(PDO::FETCH_ASSOC);

// 5. GENERAR EL PDF
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
$pdf->Cell(0, 8, 'Coeficiente total de asistentes: ' . $final_quorum, 0, 1);
$pdf->Cell(0, 8, 'Total de asistentes: ' . count($attendees), 0, 1);
$pdf->Ln(10);

// Votaciones
$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell(0, 10, 'Resultados de las Votaciones', 0, 1);
$pdf->SetFont('Arial', '', 12);
foreach ($polls as $index => $poll) {
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->MultiCell(0, 8, ($index + 1) . '. ' . utf8_decode($poll['question']), 0, 'L');
    $pdf->SetFont('Arial', '', 11);
    $results = json_decode($poll['results'], true);
    if ($results) {
        foreach ($results as $option => $coefficient) {
            $pdf->Cell(15);
            $pdf->MultiCell(0, 7, utf8_decode($option) . ': ' . $coefficient . ' (coef.)', 0, 'L');
        }
    } else {
        $pdf->Cell(15);
        $pdf->MultiCell(0, 7, 'Sin resultados registrados.', 0, 'L');
    }
    $pdf->Ln(5);
}

$pdf->Output(); // Muestra el PDF en el navegador
?>