<?php
require('fpdf/fpdf.php');  // FPDF library file
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch data
$sql = "SELECT date, weight, calories, steps, bp FROM health_records WHERE user_id='$user_id' ORDER BY date DESC";
$result = $conn->query($sql);

$pdf = new FPDF();
$pdf->AddPage();
$pdf->SetFont('Arial','B',16);
$pdf->Cell(0,10,'Health Records Report',0,1,'C');
$pdf->Ln(10);

$pdf->SetFont('Arial','B',12);
$pdf->Cell(35,10,'Date',1);
$pdf->Cell(30,10,'Weight',1);
$pdf->Cell(35,10,'Calories',1);
$pdf->Cell(30,10,'Steps',1);
$pdf->Cell(40,10,'Blood Pressure',1);
$pdf->Ln();

$pdf->SetFont('Arial','',12);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $pdf->Cell(35,10,$row['date'],1);
        $pdf->Cell(30,10,$row['weight'].' kg',1);
        $pdf->Cell(35,10,$row['calories'].' kcal',1);
        $pdf->Cell(30,10,$row['steps'],1);
        $pdf->Cell(40,10,$row['bp'],1);
        $pdf->Ln();
    }
} else {
    $pdf->Cell(0,10,'No records found!',1,1,'C');
}

$pdf->Output('D', 'health_records.pdf');
?>
