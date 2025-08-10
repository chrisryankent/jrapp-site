<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php"; // Include database connection

if (isset($_GET['loan_id']) && isset($_GET['b_id'])) {
    $loan_id = mysqli_real_escape_string($conn, $_GET['loan_id']);
    $b_id = mysqli_real_escape_string($conn, $_GET['b_id']);
} else {
    echo "<div class='alert alert-danger'>Invalid loan or borrower ID.</div>";
    exit();
}

// Fetch borrower details
$borrower_query = "SELECT * FROM tbl_borrower WHERE id = '$b_id'";
$borrower_result = mysqli_query($conn, $borrower_query);

// Fetch loan details
$loan_query = "SELECT * FROM tbl_loan_application WHERE id = '$loan_id' AND b_id = '$b_id'";
$loan_result = mysqli_query($conn, $loan_query);
?>

<div class="card">
    <div class="card-header">
        Borrower and Loan Details
    </div>
    <div class="card-body">
        <?php if ($borrower_result && mysqli_num_rows($borrower_result) > 0): ?>
            <?php $borrower = mysqli_fetch_assoc($borrower_result); ?>
            <div class="list-group mb-4">
                <a class="list-group-item"><strong>Name:</strong> <?php echo $borrower['name']; ?></a>
                <a class="list-group-item"><strong>NID:</strong> <?php echo $borrower['nid']; ?></a>
                <a class="list-group-item"><strong>Date of Birth:</strong> <?php echo $borrower['dob']; ?></a>
                <a class="list-group-item"><strong>Phone:</strong> <?php echo $borrower['mobile']; ?></a>
                <a class="list-group-item"><strong>Address:</strong> <?php echo $borrower['address']; ?></a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Borrower details not found.</div>
        <?php endif; ?>

        <?php if ($loan_result && mysqli_num_rows($loan_result) > 0): ?>
            <?php $loan = mysqli_fetch_assoc($loan_result); ?>
            <div class="list-group mb-4">
                <a class="list-group-item"><strong>Loan Amount:</strong> <?php echo $loan['expected_loan']; ?> tk</a>
                <a class="list-group-item"><strong>Total Amount (with Interest):</strong> <?php echo $loan['total_loan']; ?> tk</a>
                <a class="list-group-item"><strong>EMI:</strong> <?php echo $loan['emi_loan']; ?> tk/month</a>
                <a class="list-group-item"><strong>Amount Paid:</strong> <?php echo $loan['amount_paid']; ?> tk</a>
                <a class="list-group-item"><strong>Remaining Amount:</strong> <?php echo $loan['amount_remain']; ?> tk</a>
                <a class="list-group-item"><strong>Current Installment:</strong> <?php echo $loan['current_inst']; ?></a>
                <a class="list-group-item"><strong>Remaining Installments:</strong> <?php echo $loan['remain_inst']; ?></a>
                <a class="list-group-item"><strong>Next Payment Date:</strong> <?php echo $loan['next_date']; ?></a>
            </div>
        <?php else: ?>
            <div class="alert alert-danger">Loan details not found.</div>
        <?php endif; ?>
    </div>
</div>

<hr>
<div class="row">
    <a href="loan_status.php" class="btn btn-primary ml-4">Back to Loan Status</a>
</div>

<div class="card mt-4">
    <div class="card-header">
        Loan Payment History
    </div>
    <div class="card-body">
        <table class="table table-hover table-striped">
            <thead>
                <tr>
                    <th>SL</th>
                    <th>Pay Date</th>
                    <th>Amount Paid</th>
                    <th>Installment</th>
                    <th>Fine</th>
                    <th>Payment Report</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Fetch payment history
                $payment_query = "SELECT * FROM tbl_payment WHERE b_id = '$b_id' AND loan_id = '$loan_id'";
                $payment_result = mysqli_query($conn, $payment_query);

                $i = 0;
                $sum = 0;
                $inst = 0;

                if ($payment_result && mysqli_num_rows($payment_result) > 0) {
                    while ($pay = mysqli_fetch_assoc($payment_result)) {
                        $i++;
                        $sum += $pay['pay_amount'];
                        $inst += $pay['current_inst'];
                        ?>
                        <tr>
                            <td><?php echo $i; ?></td>
                            <td><?php echo $pay['pay_date']; ?></td>
                            <td><?php echo $pay['pay_amount']; ?> tk</td>
                            <td><?php echo $pay['current_inst']; ?></td>
                            <td><?php echo isset($pay['fine']) ? $pay['fine'] . " tk" : "N/A"; ?></td>
                            <td>
                                <a target="_blank" href="payment_report.php?loan_id=<?php echo $pay['loan_id']; ?>&pay_id=<?php echo $pay['id']; ?>&b_id=<?php echo $pay['b_id']; ?>">Report</a>
                            </td>
                        </tr>
                        <?php
                    }
                } else {
                    echo "<tr><td colspan='6' class='text-center'>No payment history found.</td></tr>";
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total: <?php echo $i; ?></th>
                    <th></th>
                    <th>Total: <?php echo $sum; ?> tk</th>
                    <th>Total Completed Installments: <?php echo $inst; ?></th>
                    <th></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php
include_once "inc/footer.php";
?>