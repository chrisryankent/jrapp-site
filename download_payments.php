<?php
// download_payments.php

include_once "config/config.php";

// Validate loan_id
if (!isset($_GET['loan_id']) || !ctype_digit($_GET['loan_id'])) {
    http_response_code(400);
    exit("Invalid loan_id");
}
$loan_id = (int)$_GET['loan_id'];

// Fetch payments
$sql = "SELECT payment_date, amount, installment_no, fine_amount 
        FROM tbl_payment 
        WHERE loan_application_id = '$loan_id' 
        ORDER BY payment_date ASC";
$res = mysqli_query($conn, $sql);

// Send CSV headers
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=payments_loan_' . $loan_id . '.csv');

// Open output stream
$out = fopen('php://output', 'w');

// Column headers
fputcsv($out, ['Payment Date','Amount (UGX)','Installment No','Fine (UGX)']);

// Output rows
if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        fputcsv($out, [
            $row['payment_date'],
            $row['amount'],
            $row['installment_no'],
            $row['fine_amount'],
        ]);
    }
} else {
    // optional: write a “no data” row
    // fputcsv($out, ['No payments found']);
}

fclose($out);
exit;
