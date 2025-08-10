<?php
session_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

/*
This page serves as a skeleton for manually updating a borrower’s loan balance.
Workflow:
1. An admin enters a Borrower NID into the search form.
2. If a matching borrower is found and they have an active loan (status not "Closed"),
   the system displays the following loan details:
   - Total Loan Amount,
   - Amount Paid (so far),
   - System-computed Outstanding Balance (Total Loan - Amount Paid).
3. The admin may then enter a "Corrected Outstanding Balance" (the value the borrower claims is correct).
4. The system then calculates a new "Amount Paid" as:
       new amount_paid = total_amount - corrected_outstanding.
   The loan record is updated accordingly.
5. If the corrected outstanding balance is 0, the loan status is also set to "Closed".
*/

$msg = "";
$borrower = [];
$loan = null;

// --- Process Borrower Search ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_borrower'])) {
    $nid = mysqli_real_escape_string($conn, $_POST['nid']);
    $queryBorrower = "SELECT * FROM tbl_borrower WHERE nid = '$nid' LIMIT 1";
    $resultBorrower = mysqli_query($conn, $queryBorrower);
    if ($resultBorrower && mysqli_num_rows($resultBorrower) > 0) {
        $borrower = mysqli_fetch_assoc($resultBorrower);
        $loanQuery = "SELECT * FROM tbl_loan_application 
                      WHERE borrower_id = '".$borrower['id']."' 
                        AND status <> 'Closed'
                      LIMIT 1";
        $resultLoan = mysqli_query($conn, $loanQuery);
        if ($resultLoan && mysqli_num_rows($resultLoan) > 0) {
            $loan = mysqli_fetch_assoc($resultLoan);
        } else {
            $msg = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                        No active loan found for this borrower.
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                          <span aria-hidden='true'>&times;</span>
                        </button>
                    </div>";
        }
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                    Borrower not found.
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                      <span aria-hidden='true'>&times;</span>
                    </button>
                </div>";
    }
}

// --- Process Balance Update ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_balance'])) {
    $loan_id = mysqli_real_escape_string($conn, $_POST['loan_id']);
    $corrected_outstanding = mysqli_real_escape_string($conn, $_POST['corrected_outstanding']);
    $loanQuery = "SELECT * FROM tbl_loan_application WHERE id = '$loan_id' LIMIT 1";
    $resultLoan = mysqli_query($conn, $loanQuery);
    if ($resultLoan && mysqli_num_rows($resultLoan) > 0) {
        $loanRec = mysqli_fetch_assoc($resultLoan);
        $total_amount = $loanRec['total_amount'];
        $new_amount_paid = $total_amount - $corrected_outstanding;
        if ($new_amount_paid < 0) {
            $msg = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                        Corrected outstanding balance cannot exceed total loan amount.
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                          <span aria-hidden='true'>&times;</span>
                        </button>
                    </div>";
        } else {
            $new_status = ($corrected_outstanding == 0) ? 'Closed' : $loanRec['status'];
            $updateQuery = "UPDATE tbl_loan_application SET amount_paid = '$new_amount_paid', status = '$new_status'
                            WHERE id = '$loan_id'";
            if (mysqli_query($conn, $updateQuery)) {
                $msg = "<div class='alert alert-success alert-dismissible fade show mt-3' role='alert'>
                            Loan balance updated successfully. New outstanding balance: " . number_format($corrected_outstanding, 2) . " tk.
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
                $loanQuery2 = "SELECT * FROM tbl_loan_application WHERE id = '$loan_id' LIMIT 1";
                $resultLoan2 = mysqli_query($conn, $loanQuery2);
                if ($resultLoan2 && mysqli_num_rows($resultLoan2) > 0) {
                    $loan = mysqli_fetch_assoc($resultLoan2);
                }
            } else {
                $msg = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                            Error updating loan balance: " . mysqli_error($conn) . "
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
            }
        }
    } else {
        $msg = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                    Loan record not found.
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                      <span aria-hidden='true'>&times;</span>
                    </button>
                </div>";
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <h2 class="mt-3 mb-4 font-weight-bold">Update Borrower Loan Balance</h2>
        <?php  
        if (!empty($msg)) {
            echo "<div class='row'><div class='col-md-8 offset-md-2'>{$msg}</div></div>";
        }
        ?>

        <!-- Borrower Search Form -->
        <form method="POST" action="">
            <div class="form-group row">
                <label for="nid" class="col-sm-3 col-form-label">Borrower NID:</label>
                <div class="col-sm-6">
                    <input type="text" name="nid" id="nid" class="form-control" placeholder="Enter Borrower NID" required>
                </div>
                <div class="col-sm-3">
                    <input type="submit" name="search_borrower" class="btn btn-primary" value="Search">
                </div>
            </div>
        </form>
        
        <?php if (!empty($borrower) && !empty($loan)) : ?>
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">Borrower &amp; Loan Details</div>
            <div class="card-body">
                <p><strong>Borrower Name:</strong> <?php echo htmlspecialchars($borrower['name']); ?></p>
                <p><strong>Loan ID:</strong> <?php echo $loan['id']; ?></p>
                <p><strong>Total Loan Amount:</strong> <?php echo number_format($loan['total_amount'],2); ?> tk</p>
                <p><strong>Amount Paid:</strong> <?php echo number_format($loan['amount_paid'],2); ?> tk</p>
                <p>
                    <strong>System Computed Outstanding Balance:</strong> 
                    <?php 
                        $outstanding = $loan['total_amount'] - $loan['amount_paid'];
                        echo number_format($outstanding,2);
                    ?> tk
                </p>
            </div>
        </div>
        
        <!-- Balance Adjustment Form -->
        <div class="card mb-4">
            <div class="card-header bg-dark text-white">Manual Balance Correction</div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                    <div class="form-group row">
                        <label for="corrected_outstanding" class="col-sm-4 col-form-label">Corrected Outstanding Balance (tk):</label>
                        <div class="col-sm-8">
                            <input type="number" step="0.01" name="corrected_outstanding" id="corrected_outstanding" class="form-control" placeholder="Enter correct outstanding balance" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-sm-8 offset-sm-4">
                            <input type="submit" name="update_balance" class="btn btn-success" value="Update Balance">
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include_once "inc/footer.php"; ?>
