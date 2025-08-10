<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Process Payment Submission ---
$alert = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payment_submit'])) {
    $borrower_id   = mysqli_real_escape_string($conn, $_POST['borrower_id']);
    $loan_id       = mysqli_real_escape_string($conn, $_POST['loan_id']);
    $payment       = mysqli_real_escape_string($conn, $_POST['payment']);
    $pay_date      = mysqli_real_escape_string($conn, $_POST['pay_date']);
    $fine_amount   = isset($_POST['fine_amount']) ? mysqli_real_escape_string($conn, $_POST['fine_amount']) : 0;
    $current_inst  = mysqli_real_escape_string($conn, $_POST['current_inst']);
    $remain_inst   = mysqli_real_escape_string($conn, $_POST['remain_inst']);
    $total_amount  = mysqli_real_escape_string($conn, $_POST['total_amount']);
    $paid_amount   = mysqli_real_escape_string($conn, $_POST['paid_amount']);
    $remain_amount = mysqli_real_escape_string($conn, $_POST['remain_amount']);
    $next_due_date = (isset($_POST['next_date']) && trim($_POST['next_date']) !== "") 
        ? mysqli_real_escape_string($conn, $_POST['next_date']) 
        : date('Y-m-d', strtotime('+30 days'));

    // Insert the payment record.
    $payment_query = "INSERT INTO tbl_payment (borrower_id, loan_application_id, amount, payment_date, installment_no, remaining_installments, fine_amount) 
                      VALUES ('$borrower_id', '$loan_id', '$payment', '$pay_date', '$current_inst', '$remain_inst', '$fine_amount')";
    if (mysqli_query($conn, $payment_query)) {
        // Determine new loan status based on updated paid amount.
        $loan_status = ($paid_amount >= $total_amount) ? 'Closed' : 'Approved';
        // Update the loan record with new payment details.
        $update_loan_query = "UPDATE tbl_loan_application 
                                  SET amount_paid = '$paid_amount', 
                                      current_installment = '$current_inst', 
                                      next_due_date = '$next_due_date',
                                      status = '$loan_status'
                                  WHERE id = '$loan_id'";
        if (mysqli_query($conn, $update_loan_query)) {
            $alert = "<div class='alert alert-success alert-dismissible fade show mt-3' role='alert'>
                        Payment submitted successfully!
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                          <span aria-hidden='true'>&times;</span>
                        </button>
                      </div>";
            // Insert a notification indicating that a payment has been received.
            $notification_message = "Payment of " . number_format($payment, 2) . " tk received for installment #" . $current_inst . " of Loan #$loan_id.";
            $notification_query = "INSERT INTO tbl_notification (recipient_user, borrower_id, loan_application_id, type, message) 
                                   VALUES (NULL, '$borrower_id', '$loan_id', 'SMS', '" . mysqli_real_escape_string($conn, $notification_message) . "')";
            mysqli_query($conn, $notification_query);
        } else {
            $alert = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                        Error updating loan details: " . mysqli_error($conn) . "
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                          <span aria-hidden='true'>&times;</span>
                        </button>
                      </div>";
        }
    } else {
        $alert = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                    Error submitting payment: " . mysqli_error($conn) . "
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                      <span aria-hidden='true'>&times;</span>
                    </button>
                  </div>";
    }
}

// --- Borrower Search & Loan Fetch ---
$name = "";
$borrower_id = "";
$loan = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $nid = mysqli_real_escape_string($conn, $_POST['key']);
    $borrower_query = "SELECT * FROM tbl_borrower WHERE nid = '$nid'";
    $borrower_result = mysqli_query($conn, $borrower_query);
    if ($borrower_result && mysqli_num_rows($borrower_result) > 0) {
        $row = mysqli_fetch_assoc($borrower_result);
        $name = $row['name'];
        $borrower_id = $row['id'];
        // Fetch loan details for the borrower where status is 'Approved' and amount_remaining > 0.
        $loan_query = "SELECT *, (total_amount - amount_paid) AS amount_remaining FROM tbl_loan_application 
                       WHERE borrower_id = '$borrower_id' 
                         AND status = 'Approved' 
                         AND (total_amount - amount_paid) > 0";
        $loan_result = mysqli_query($conn, $loan_query);
        if ($loan_result && mysqli_num_rows($loan_result) > 0) {
            $loan = mysqli_fetch_assoc($loan_result);
        } else {
            $alert = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                        Loan not approved or already fully paid!
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                          <span aria-hidden='true'>&times;</span>
                        </button>
                      </div>";
        }
    } else {
        $alert = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                    Borrower NID not matched or not applicable for loan
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                      <span aria-hidden='true'>&times;</span>
                    </button>
                  </div>";
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <h3 class="page-heading mb-4">Loan Payment</h3>
        <h5 class="card-title p-3 bg-info text-white rounded">Payment</h5>
        <?php
        // Show alert aligned with layout
        if (!empty($alert)) {
            echo "<div class='row'><div class='col-md-8 offset-md-2'>{$alert}</div></div>";
        }
        ?>

        <!-- Borrower Search Form -->
        <form action="" method="POST" class="mb-4">
            <div class="form-group row">
                <label for="inputBorrowerSearch" class="text-right col-2 font-weight-bold col-form-label">Search Borrower:</label>
                <div class="col-sm-6">
                    <input type="text" name="key" class="form-control" id="inputBorrowerSearch" placeholder="Enter NID number" required>
                </div>
                <div class="col-sm-3">
                    <input type="submit" class="btn btn-info" name="search" value="Search">
                </div>
            </div>
        </form>

        <?php if ($loan): ?>
        <!-- Payment Form -->
        <form action="" method="post" name="myform" id="myform">
            <div class="form-group row">
                <label for="borrower_name" class="text-right col-2 font-weight-bold col-form-label">Full Name</label>
                <div class="col-sm-9">
                    <input type="text" name="borrower_name" class="form-control" value="<?php echo htmlspecialchars($name); ?>" readonly>
                    <input type="hidden" name="borrower_id" value="<?php echo htmlspecialchars($borrower_id); ?>">
                    <input type="hidden" name="loan_id" value="<?php echo htmlspecialchars($loan['id']); ?>">
                </div>
            </div>

            <!-- Payment Amount (EMI) -->
            <div class="form-group row">
                <label for="payment" class="text-right col-2 font-weight-bold col-form-label">Payment Amount (EMI)</label>
                <div class="col-sm-9">
                    <input type="number" step="0.01" name="payment" class="form-control" id="payment" value="<?php echo $loan['emi_amount']; ?>" readonly>
                </div>
            </div>

            <?php if (isset($loan['next_due_date'])): ?>
            <div class="form-group row">
                <label for="next_date" class="text-right col-2 font-weight-bold col-form-label">Next Payment Date</label>
                <div class="col-sm-9">
                    <?php
                    $new_next_date = date('Y-m-d', strtotime('+30 days', strtotime($loan['next_due_date'])));
                    ?>
                    <input type="date" name="next_date" class="form-control" id="next_date" value="<?php echo $new_next_date; ?>" readonly>
                </div>
            </div>

            <?php
            if (strtotime(date('Y-m-d')) > strtotime($loan['next_due_date'])):
                $fine = $loan['emi_amount'] * 0.02;
            ?>
            <div class="form-group row">
                <label class="text-right col-2 font-weight-bold col-form-label">Fine (2% of EMI):</label>
                <div class="col-sm-9">
                    <input type="number" step="0.01" name="fine_amount" class="form-control" value="<?php echo $fine; ?>" readonly>
                </div>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="form-group row">
                <label for="pay_date" class="text-right col-2 font-weight-bold col-form-label">Payment Date</label>
                <div class="col-sm-9">
                    <input type="date" name="pay_date" class="form-control" id="pay_date" required>
                </div>
            </div>

            <!-- Current Installment -->
            <div class="form-group row">
                <label class="text-right col-2 font-weight-bold col-form-label">Current Installment</label>
                <div class="col-sm-9">
                    <input type="number" name="current_inst" class="form-control" value="<?php echo $loan['current_installment'] + 1; ?>" readonly>
                </div>
            </div>

            <!-- Remaining Installments -->
            <div class="form-group row">
                <label class="text-right col-2 font-weight-bold col-form-label">Remaining Installments</label>
                <div class="col-sm-9">
                    <input type="number" name="remain_inst" class="form-control" value="<?php echo $loan['installments'] - ($loan['current_installment'] + 1); ?>" readonly>
                </div>
            </div>

            <!-- Total Loan Amount -->
            <div class="form-group row">
                <label class="text-right col-2 font-weight-bold col-form-label">Total Loan Amount</label>
                <div class="col-sm-9">
                    <input type="number" step="0.01" name="total_amount" class="form-control" value="<?php echo $loan['total_amount']; ?>" readonly>
                </div>
            </div>

            <!-- Paid Amount -->
            <div class="form-group row">
                <label class="text-right col-2 font-weight-bold col-form-label">Paid Amount</label>
                <div class="col-sm-9">
                    <input type="number" step="0.01" name="paid_amount" class="form-control" value="<?php echo $loan['amount_paid'] + $loan['emi_amount']; ?>" readonly>
                </div>
            </div>

            <!-- Remaining Amount -->
            <div class="form-group row">
                <label class="text-right col-2 font-weight-bold col-form-label">Remaining Amount</label>
                <div class="col-sm-9">
                    <input type="number" step="0.01" name="remain_amount" class="form-control" value="<?php echo $loan['total_amount'] - ($loan['amount_paid'] + $loan['emi_amount']); ?>" readonly>
                </div>
            </div>
            <hr>
            <div class="form-group row">
                <div class="col-md-6">
                    <input type="submit" name="payment_submit" class="btn btn-info pull-right" value="Submit Payment">
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<?php
include_once "inc/footer.php";
?>
