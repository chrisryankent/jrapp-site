<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php"; // Include database connection
?>

<h3 class="page-heading mb-4">Manage Liability</h3>
<h5 class="card-title p-3 bg-info text-white rounded">Property Selling Details</h5>
<div class="container">
    <?php
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['property_sell_submit'])) {
        $borrower_id = mysqli_real_escape_string($conn, $_POST['b_id']);
        $loan_id = mysqli_real_escape_string($conn, $_POST['loan_id']);
        $property_name = mysqli_real_escape_string($conn, $_POST['property_name']);
        $property_details = mysqli_real_escape_string($conn, $_POST['property_details']);
        $price = mysqli_real_escape_string($conn, $_POST['price']);
        $pay_remaining_loan = mysqli_real_escape_string($conn, $_POST['pay_remaining_loan']);

        // Validate that the payment does not exceed the remaining loan amount
        $loan_query = "SELECT amount_remain FROM tbl_loan_application WHERE id = '$loan_id'";
        $loan_result = mysqli_query($conn, $loan_query);
        if ($loan_result && mysqli_num_rows($loan_result) > 0) {
            $loan_data = mysqli_fetch_assoc($loan_result);
            $amount_remain = $loan_data['amount_remain'];

            if ($pay_remaining_loan > $amount_remain) {
                $inserted = "Error: Payment exceeds the remaining loan amount.";
            } else {
                // Insert property selling details
                $insert_query = "INSERT INTO property_sales (borrower_id, loan_id, property_name, property_details, price, pay_remaining_loan) 
                                 VALUES ('$borrower_id', '$loan_id', '$property_name', '$property_details', '$price', '$pay_remaining_loan')";
                if (mysqli_query($conn, $insert_query)) {
                    // Update loan details
                    $update_loan_query = "UPDATE loans 
                                          SET amount_paid = amount_paid + '$pay_remaining_loan', 
                                              amount_remain = amount_remain - '$pay_remaining_loan' 
                                          WHERE id = '$loan_id'";
                    if (mysqli_query($conn, $update_loan_query)) {
                        $inserted = "Property selling details submitted successfully!";
                    } else {
                        $inserted = "Error updating loan details: " . mysqli_error($conn);
                    }
                } else {
                    $inserted = "Error submitting property selling details: " . mysqli_error($conn);
                }
            }
        } else {
            $inserted = "Error: Loan details not found.";
        }
    }

    if (isset($inserted)) {
        echo "<div id='successMessage' class='alert alert-" . (strpos($inserted, 'Error') === false ? 'success' : 'danger') . " alert-dismissible'>
                <a href='#' class='close' data-dismiss='alert' aria-label='close'>&times;</a>
                $inserted
              </div>";
    }

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
        $nid = mysqli_real_escape_string($conn, $_POST['key']);
        $borrower_query = "SELECT * FROM tbl_borrower WHERE nid = '$nid'";
        $borrower_result = mysqli_query($conn, $borrower_query);

        if ($borrower_result && mysqli_num_rows($borrower_result) > 0) {
            $row = mysqli_fetch_assoc($borrower_result);
            $name = $row['name'];
            $b_id = $row['id'];

            $loan_query = "SELECT * FROM tbl_loan_application WHERE b_id = '$b_id' AND status = 3";
            $loan_result = mysqli_query($conn, $loan_query);

            if ($loan_result && mysqli_num_rows($loan_result) > 0) {
                $loan = mysqli_fetch_assoc($loan_result);
            } else {
                echo "<span class='text-center' style='color:red'>Loan not approved!</span>";
            }
        } else {
            echo "<span class='text-center' style='color:red'>Borrower NID not matched or not applicable for loan</span>";
        }
    }
    ?>

    <form action="" method="POST">
        <div class="form-group row">
            <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Search Borrower:</label>
            <div class="col-sm-6">
                <input type="text" name="key" class="form-control" id="inputBorrowerFirstName" placeholder="Enter NID number of borrower" required>
            </div>
            <div class="col-sm-3">
                <input type="submit" class="btn btn-info" name="search" value="Search">
            </div>
        </div>
    </form>

    <form action="" method="post" name="myform" id="myform">
        <div class="form-group row">
            <label for="inputBorrowerFirstName" class="text-right col-2 font-weight-bold col-form-label">Full Name</label>
            <div class="col-sm-9">
                <input type="text" class="form-control" id="inputBorrowerFirstName" value="<?php if (isset($name)) echo $name; ?>" readonly>
                <input type="hidden" name="b_id" value="<?php if (isset($b_id)) echo $b_id; ?>">
                <input type="hidden" name="loan_id" value="<?php if (isset($loan['id'])) echo $loan['id']; ?>">
            </div>
        </div>

        <div class="form-group row">
            <label class="text-right col-2 font-weight-bold col-form-label">Total Amount to be Paid:</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" value="<?php if (isset($loan['total_loan'])) echo $loan['total_loan']; ?>" readonly>
            </div>
        </div>

        <div class="form-group row">
            <label class="text-right col-2 font-weight-bold col-form-label">Paid Amount</label>
            <div class="col-sm-9">
                <input type="number" name="amount_paid" class="form-control" value="<?php if (isset($loan['amount_paid'])) echo $loan['amount_paid']; ?>" readonly>
            </div>
        </div>

        <div class="form-group row">
            <label class="text-right col-2 font-weight-bold col-form-label">Amount Remaining</label>
            <div class="col-sm-9">
                <input type="number" class="form-control" value="<?php if (isset($loan['amount_remain'])) echo $loan['amount_remain']; ?>" readonly>
            </div>
        </div>

        <div class="form-group row">
            <label class="text-right col-2 font-weight-bold col-form-label">Property Name</label>
            <div class="col-sm-9">
                <input type="text" name="property_name" class="form-control" placeholder="Property name" required>
            </div>
        </div>

        <div class="form-group row">
            <label class="text-right col-2 font-weight-bold col-form-label">Property Details</label>
            <div class="col-sm-9">
                <textarea name="property_details" class="form-control" cols="30" rows="10" required></textarea>
            </div>
        </div>

        <div class="form-group row">
            <label class="text-right col-2 font-weight-bold col-form-label">Selling Price</label>
            <div class="col-sm-9">
                <input type="text" name="price" class="form-control" placeholder="Property selling price" required>
            </div>
        </div>

        <div class="form-group row">
            <label class="text-right col-2 font-weight-bold col-form-label">Pay Remaining Loan</label>
            <div class="col-sm-9">
                <input type="number" name="pay_remaining_loan" class="form-control" placeholder="Pay remaining loan" required>
            </div>
        </div>

        <hr>
        <div class="form-group row">
            <div class="col-md-6">
                <input type="submit" name="property_sell_submit" class="btn btn-info pull-right" value="Submit Details">
            </div>
        </div>
    </form>
</div>

<?php
include_once "inc/footer.php";
?>