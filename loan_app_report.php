<?php
include_once "config.php"; // Include database connection
require_once('assets/fpdf/fpdf.php');
require_once('assets/fpdf/rotation.php');

class PDF extends PDF_Rotate
{
    function Header()
    {
        // Optional: Add a watermark or header content
    }

    function RotatedText($x, $y, $txt, $angle)
    {
        // Text rotated around its origin
        $this->Rotate($angle, $x, $y);
        $this->Text($x, $y, $txt);
        $this->Rotate(0);
    }
}

if (isset($_GET['loan_id'])) {
    $loan_id = mysqli_real_escape_string($conn, $_GET['loan_id']);
} else {
    echo "Loan ID not provided.";
    exit();
}

// Fetch loan details
$loan_query = "SELECT loans.*, borrowers.name, borrowers.nid, borrowers.mobile, borrowers.email 
               FROM loans 
               INNER JOIN borrowers ON loans.borrower_id = borrowers.id 
               WHERE loans.id = '$loan_id'";
$loan_result = mysqli_query($conn, $loan_query);

if ($loan_result && mysqli_num_rows($loan_result) > 0) {
    $rec = mysqli_fetch_assoc($loan_result);
} else {
    echo "Loan details not found.";
    exit();
}

// Create PDF
$pdf = new PDF();
$pdf->AddPage();

// Bank Header
$pdf->Image('assets/brac.jpg', 10, 15, 17);
$pdf->Ln();
$pdf->Cell(20);
$pdf->SetFont('Times', '', 12);
$pdf->Write(4, 'BRAC Bank is a private commercial bank');
$pdf->Ln();
$pdf->Cell(20);
$pdf->Write(4, 'Uttara Model Town, Dhaka 1230, Bangladesh');
$pdf->Ln();
$pdf->Cell(20);
$pdf->Write(4, 'Phone: 0175465465, Email: info@bracbank.com');
$pdf->Ln();
$pdf->Cell(20);
$pdf->Write(4, 'Web: www.bracbank.com');
$pdf->Ln();
$pdf->Cell(20);
$pdf->SetFont('Times', '', 8);
$pdf->Write(5, '__________________________________________________________________________________________________________________________________');
$pdf->Ln();
$pdf->Ln();

// Report Title
$pdf->Cell(85);
$pdf->SetFont('Times', 'U', 12);
$pdf->Write(5, 'Loan Application Report');
$pdf->Ln();
$pdf->Ln(2);

// Loan Details
$pdf->Cell(5);
$pdf->SetFont('Times', '', 14);
$pdf->Cell(60, 10, 'Name', 1);
$pdf->Cell(80, 10, $rec['name'], 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'National ID', 1);
$pdf->Cell(80, 10, $rec['nid'], 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Mobile', 1);
$pdf->Cell(80, 10, $rec['mobile'], 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Email', 1);
$pdf->Cell(80, 10, $rec['email'], 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Expected Loan', 1);
$pdf->Cell(80, 10, $rec['expected_loan'] . " tk", 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Loan Percentage', 1);
$pdf->Cell(80, 10, $rec['loan_percentage'] . " %", 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Total Loan', 1);
$pdf->Cell(80, 10, $rec['total_loan'] . " tk", 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Installments', 1);
$pdf->Cell(80, 10, $rec['installments'], 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'EMI', 1);
$pdf->Cell(80, 10, $rec['emi_loan'] . " tk/month", 1);
$pdf->Ln();

// Output PDF
$pdf->Ln();
$pdf->Ln();
ob_end_clean();
$pdf->Output();
?>