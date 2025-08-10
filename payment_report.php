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

if (isset($_GET['loan_id']) && isset($_GET['pay_id']) && isset($_GET['b_id'])) {
    $loan_id = mysqli_real_escape_string($conn, $_GET['loan_id']);
    $pay_id = mysqli_real_escape_string($conn, $_GET['pay_id']);
    $b_id = mysqli_real_escape_string($conn, $_GET['b_id']);
} else {
    echo "Required parameters not provided.";
    exit();
}

// Fetch payment details
$payment_query = "SELECT payments.*, borrowers.name, loans.total_loan 
                  FROM payments 
                  INNER JOIN borrowers ON payments.borrower_id = borrowers.id 
                  INNER JOIN loans ON payments.loan_id = loans.id 
                  WHERE payments.id = '$pay_id' AND payments.loan_id = '$loan_id' AND payments.borrower_id = '$b_id'";
$payment_result = mysqli_query($conn, $payment_query);

if ($payment_result && mysqli_num_rows($payment_result) > 0) {
    $rec = mysqli_fetch_assoc($payment_result);
} else {
    echo "Payment details not found.";
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
$pdf->Write(5, 'Payment Report');
$pdf->Ln();
$pdf->Ln(2);

// Payment Details
$pdf->Cell(5);
$pdf->SetFont('Times', '', 14);
$pdf->Cell(60, 10, 'Name', 1);
$pdf->Cell(80, 10, $rec['name'], 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Pay Date', 1);
$pdf->Cell(80, 10, $rec['pay_date'], 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Payment Amount', 1);
$pdf->Cell(80, 10, $rec['pay_amount'] . " tk", 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Installments', 1);
$pdf->Cell(80, 10, $rec['current_inst'], 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Fine', 1);
$pdf->Cell(80, 10, $rec['fine'] . " tk", 1);
$pdf->Ln();

$pdf->Cell(5);
$pdf->Cell(60, 10, 'Total Loan', 1);
$pdf->Cell(80, 10, $rec['total_loan'] . " tk", 1);
$pdf->Ln();

// Output PDF
$pdf->Ln();
$pdf->Ln();
ob_end_clean();
$pdf->Output();
?>