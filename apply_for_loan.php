<?php
session_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure branch_id is set in session
if (!isset($_SESSION['branch_id']) || empty($_SESSION['branch_id'])) {
    die("Error: Branch ID is not set. Please log in with a valid branch account.");
}
$branch_id = $_SESSION['branch_id'];

// Prepare interest rate options from the database.
$interestRates = [];
$queryRates = "SELECT id, annual_rate_percent, effective_date FROM tbl_interest_rate ORDER BY effective_date DESC";
$resultRates = mysqli_query($conn, $queryRates);
if ($resultRates) {
    while ($row = mysqli_fetch_assoc($resultRates)) {
        $interestRates[] = $row;
    }
}

$msg = "";
$borrower = [];
$loanApplicationInserted = false;
$loanEligible = false;
$loan_application_id = 0;

// Borrower Search Section
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search_borrower'])) {
    $nid = mysqli_real_escape_string($conn, $_POST['nid']);
    $queryBorrower = "SELECT * FROM tbl_borrower WHERE nid = '$nid' LIMIT 1";
    $resultBorrower = mysqli_query($conn, $queryBorrower);
    if ($resultBorrower && mysqli_num_rows($resultBorrower) > 0) {
        $borrower = mysqli_fetch_assoc($resultBorrower);
        // Check if borrower already has an active loan (status <> 'Closed')
        $activeLoanQuery = "SELECT * FROM tbl_loan_application 
                            WHERE borrower_id = '".$borrower['id']."'
                              AND status <> 'Closed'
                            LIMIT 1";
        $activeLoanResult = mysqli_query($conn, $activeLoanQuery);
        if ($activeLoanResult && mysqli_num_rows($activeLoanResult) > 0) {
            $msg = "This borrower already has an active loan. They cannot submit a new loan application until the current one is closed.";
            $loanEligible = false;
        } else {
            $loanEligible = true;
        }
    } else {
        $msg = "Borrower not found.";
    }
}

// Process Loan Application Submission with Collateral
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_loan_application'])) {
    $borrower_id        = mysqli_real_escape_string($conn, $_POST['borrower_id']);
    $loan_amount        = mysqli_real_escape_string($conn, $_POST['loan_amount']);
    $interest_rate_id   = mysqli_real_escape_string($conn, $_POST['interest_rate_id']);
    $processing_fee_pct = mysqli_real_escape_string($conn, $_POST['processing_fee_pct']);
    $installments       = mysqli_real_escape_string($conn, $_POST['installments']);
    $total_amount       = mysqli_real_escape_string($conn, $_POST['total_amount']);
    $emi_amount         = mysqli_real_escape_string($conn, $_POST['borrower_emi']);

    // Process file upload for loan document.
    $loan_doc = "";
    if (isset($_FILES['loan_file']) && $_FILES['loan_file']['name'] != "") {
        $file_name = $_FILES['loan_file']['name'];
        $file_tmp  = $_FILES['loan_file']['tmp_name'];
        $file_ext  = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $allowed_ext = array('doc', 'docx', 'pdf');
        $upload_dir = "admin/uploads/documents/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $loan_doc = $upload_dir . time() . "_" . basename($file_name);
        if (!in_array($file_ext, $allowed_ext) || !move_uploaded_file($file_tmp, $loan_doc)) {
            $msg = "Loan document upload failed. Allowed extensions: doc, docx, pdf.";
        }
    } else {
        $msg = "Loan document is required.";
    }

    // If no error so far, insert the loan application.
    if (empty($msg)) {
        $loan_query = "INSERT INTO tbl_loan_application 
            (borrower_id, branch_id, expected_amount, interest_rate_id, processing_fee_pct, installments, total_amount, emi_amount, document_path, status, amount_paid, current_installment)
            VALUES
            ('$borrower_id', '$branch_id', '$loan_amount', '$interest_rate_id', '$processing_fee_pct', '$installments', '$total_amount', '$emi_amount', '$loan_doc', 'Pending', 0, 0)";
        if (mysqli_query($conn, $loan_query)) {
            $loan_application_id = mysqli_insert_id($conn);

            // Collateral Details
            $property_name          = mysqli_real_escape_string($conn, $_POST['property_name']);
            $property_details       = mysqli_real_escape_string($conn, $_POST['property_details']);
            $market_value           = mysqli_real_escape_string($conn, $_POST['market_value']);
            $outstanding_loan_value = mysqli_real_escape_string($conn, $_POST['outstanding_loan_value']);
            $expected_return_value  = mysqli_real_escape_string($conn, $_POST['expected_return_value']);
            $valuation_date         = mysqli_real_escape_string($conn, $_POST['valuation_date']);

            $collateral_doc = "";
            if (isset($_FILES['collateral_doc']) && $_FILES['collateral_doc']['name'] != "") {
                $coll_file_name = $_FILES['collateral_doc']['name'];
                $coll_file_tmp  = $_FILES['collateral_doc']['tmp_name'];
                $coll_file_ext  = strtolower(pathinfo($coll_file_name, PATHINFO_EXTENSION));
                $allowed_coll_ext = array('doc', 'docx', 'pdf', 'jpg', 'png');
                $coll_upload_dir = "admin/uploads/collateral/";
                if (!file_exists($coll_upload_dir)) {
                    mkdir($coll_upload_dir, 0777, true);
                }
                $collateral_doc = $coll_upload_dir . time() . "_" . basename($coll_file_name);
                if (!in_array($coll_file_ext, $allowed_coll_ext) || !move_uploaded_file($coll_file_tmp, $collateral_doc)) {
                    $msg = "Collateral document upload failed. Allowed extensions: doc, docx, pdf, jpg, png.";
                }
            }

            if (empty($msg)) {
                $insert_collateral = "INSERT INTO tbl_liability 
                    (borrower_id, loan_application_id, property_name, property_details, market_value, outstanding_loan_value, expected_return_value, valuation_date)
                    VALUES
                    ('$borrower_id', '$loan_application_id', '$property_name', '$property_details', '$market_value', '$outstanding_loan_value', '$expected_return_value', '$valuation_date')";
                if (mysqli_query($conn, $insert_collateral)) {

                    // --- INSERT GUARANTOR INFO ---
                    $guarantor_name        = mysqli_real_escape_string($conn, $_POST['guarantor_name']);
                    $guarantor_relationship= mysqli_real_escape_string($conn, $_POST['guarantor_relationship']);
                    $guarantor_contact     = mysqli_real_escape_string($conn, $_POST['guarantor_contact']);
                    $guarantor_nid         = mysqli_real_escape_string($conn, $_POST['guarantor_nid']);
                    $guarantor_address     = mysqli_real_escape_string($conn, $_POST['guarantor_address']);

                    $insert_guarantor = "INSERT INTO tbl_guarantor
                        (loan_application_id, name, relationship, contact, nid, address)
                        VALUES
                        ('$loan_application_id', '$guarantor_name', '$guarantor_relationship', '$guarantor_contact', '$guarantor_nid', '$guarantor_address')";
                    if (mysqli_query($conn, $insert_guarantor)) {
                        $msg = "Loan application, collateral, and guarantor details submitted successfully!";
                        $loanApplicationInserted = true;
                    } else {
                        $msg = "Error inserting guarantor details: " . mysqli_error($conn);
                    }

                } else {
                    $msg = "Error inserting collateral details: " . mysqli_error($conn);
                }
            } // end if (empty($msg)) for collateral
        } // end if (mysqli_query($conn, $loan_query))
        else {
            $msg = "Error submitting loan application: " . mysqli_error($conn);
        }
    } // end if (empty($msg)) for loan
} // end if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_loan_application']))
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <h2 class="mt-3 mb-4 font-weight-bold">Loan Application with Collateral Details (UGX)</h2>
        <?php if (!empty($msg)): ?>
            <div class='alert alert-info'><?php echo $msg; ?></div>
        <?php endif; ?>

        <!-- Borrower Search Form -->
        <form method="POST" action="">
            <div class="form-group row">
                <label for="nid" class="col-sm-3 col-form-label">Borrower NID:</label>
                <div class="col-sm-6">
                    <input type="text" name="nid" id="nid" class="form-control" placeholder="Enter Borrower NID" required>
                </div>
                <div class="col-sm-3">
                    <input type="submit" name="search_borrower" class="btn btn-primary" value="Search Borrower">
                </div>
            </div>
        </form>

        <?php if (!empty($borrower)): ?>
            <div class='card mb-4'>
                <div class='card-header bg-secondary text-white'>Borrower Details</div>
                <div class='card-body'>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($borrower['name']); ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($borrower) && !$loanEligible): ?>
            <div class='alert alert-danger'>This borrower already has an active loan. They cannot apply for a new loan until the current one is closed.</div>
        <?php endif; ?>

        <!-- Loan Application Form -->
        <?php if (!empty($borrower) && $loanEligible && !$loanApplicationInserted): ?>
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="borrower_id" value="<?php echo htmlspecialchars($borrower['id']); ?>">

            <!-- Loan Details Section -->
            <div class="card mb-3">
                <div class="card-header bg-primary text-white">Loan Details</div>
                <div class="card-body">
                    <div class="form-group row">
                        <label for="loan_amount" class="col-sm-3 col-form-label">Expected Loan Amount (UGX)</label>
                        <div class="col-sm-9">
                            <input type="number" step="1" name="loan_amount" id="loan_amount" class="form-control" placeholder="Enter loan amount" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="interest_rate_id" class="col-sm-3 col-form-label">Interest Rate</label>
                        <div class="col-sm-9">
                            <select name="interest_rate_id" id="interest_rate_id" class="form-control" required>
                                <option value="">Select Interest Rate</option>
                                <?php foreach ($interestRates as $rate) { ?>
                                    <option value="<?php echo $rate['id']; ?>" data-rate="<?php echo $rate['annual_rate_percent']; ?>">
                                        <?php echo $rate['annual_rate_percent']; ?>% (Effective: <?php echo $rate['effective_date']; ?>)
                                    </option>
                                <?php } ?>
                            </select>
                            <input type="hidden" id="interest_rate_value" name="interest_rate_value">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="processing_fee_pct" class="col-sm-3 col-form-label">Service Fee (%)</label>
                        <div class="col-sm-9">
                            <input type="number" step="0.01" name="processing_fee_pct" id="processing_fee_pct" class="form-control" placeholder="Enter service fee percentage" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="installments" class="col-sm-3 col-form-label">Number of Installments</label>
                        <div class="col-sm-9">
                            <input type="number" name="installments" id="installments" class="form-control" placeholder="Enter number of installments" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="total_amount" class="col-sm-3 col-form-label">Total Amount (with interest &amp; fee) (UGX)</label>
                        <div class="col-sm-9">
                            <input type="text" name="total_amount" id="total_amount" class="form-control" readonly required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="borrower_emi" class="col-sm-3 col-form-label">Calculated EMI (UGX)</label>
                        <div class="col-sm-9">
                            <input type="text" name="borrower_emi" id="borrower_emi" class="form-control" readonly required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="loan_file" class="col-sm-3 col-form-label">Attach Loan Document<br>(doc, docx, pdf only)</label>
                        <div class="col-sm-9">
                            <input type="file" name="loan_file" id="loan_file" class="form-control" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Collateral Details Section -->
            <div class="card mb-3">
                <div class="card-header bg-info text-white">Collateral Details</div>
                <div class="card-body">
                    <div class="form-group row">
                        <label for="property_name" class="col-sm-3 col-form-label">Property Name</label>
                        <div class="col-sm-9">
                            <input type="text" name="property_name" id="property_name" class="form-control" placeholder="Enter asset name" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="property_details" class="col-sm-3 col-form-label">Property Details</label>
                        <div class="col-sm-9">
                            <textarea name="property_details" id="property_details" class="form-control" rows="3" placeholder="Enter asset details" required></textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="market_value" class="col-sm-3 col-form-label">Market Value (UGX)</label>
                        <div class="col-sm-9">
                            <input type="number" step="1" name="market_value" id="market_value" class="form-control" placeholder="Enter market value" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="outstanding_loan_value" class="col-sm-3 col-form-label">Outstanding Loan Value (UGX)</label>
                        <div class="col-sm-9">
                            <input type="number" step="1" name="outstanding_loan_value" id="outstanding_loan_value" class="form-control" placeholder="Auto-filled from total amount" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="expected_return_value" class="col-sm-3 col-form-label">Expected Return Value (UGX)</label>
                        <div class="col-sm-9">
                            <input type="number" step="1" name="expected_return_value" id="expected_return_value" class="form-control" placeholder="Auto-filled from total amount" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="valuation_date" class="col-sm-3 col-form-label">Valuation Date</label>
                        <div class="col-sm-9">
                            <input type="date" name="valuation_date" id="valuation_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="collateral_doc" class="col-sm-3 col-form-label">Collateral Document<br>(doc, docx, pdf, jpg, png)</label>
                        <div class="col-sm-9">
                            <input type="file" name="collateral_doc" id="collateral_doc" class="form-control" accept=".doc,.docx,.pdf,.jpg,.png">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Guarantor Details Section -->
            <div class="card mb-3">
                <div class="card-header bg-dark text-white">Guarantor Details</div>
                <div class="card-body">
                    <div class="form-group row">
                        <label for="guarantor_name" class="col-sm-3 col-form-label">Name</label>
                        <div class="col-sm-9">
                            <input type="text" name="guarantor_name" id="guarantor_name" class="form-control" placeholder="Enter guarantor name" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="guarantor_relationship" class="col-sm-3 col-form-label">Relationship</label>
                        <div class="col-sm-9">
                            <input type="text" name="guarantor_relationship" id="guarantor_relationship" class="form-control" placeholder="e.g., Parent, Sibling, Friend" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="guarantor_contact" class="col-sm-3 col-form-label">Contact</label>
                        <div class="col-sm-9">
                            <input type="text" name="guarantor_contact" id="guarantor_contact" class="form-control" placeholder="Enter contact number" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="guarantor_nid" class="col-sm-3 col-form-label">National ID</label>
                        <div class="col-sm-9">
                            <input type="text" name="guarantor_nid" id="guarantor_nid" class="form-control" placeholder="Enter National ID (if available)">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="guarantor_address" class="col-sm-3 col-form-label">Address</label>
                        <div class="col-sm-9">
                            <textarea name="guarantor_address" id="guarantor_address" class="form-control" placeholder="Enter guarantor address" rows="3" required></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="form-group row">
                <div class="col-sm-6">
                    <input type="submit" name="submit_loan_application" class="btn btn-primary" value="Submit Loan Application">
                </div>
            </div>
        </form>
        <?php endif; ?>
    </div>
</div>

<script>
   document.getElementById("interest_rate_id").addEventListener("change", function() {
       var selectedOption = this.options[this.selectedIndex];
       var rate = selectedOption.getAttribute("data-rate") || 0;
       document.getElementById("interest_rate_value").value = rate;
       calculateEMI();
   });

   function calculateEMI() {
       var loanAmount = parseFloat(document.getElementById("loan_amount").value) || 0;
       var interestRate = parseFloat(document.getElementById("interest_rate_value").value) || 0;
       var feePct = parseFloat(document.getElementById("processing_fee_pct").value) || 0;
       var installments = parseFloat(document.getElementById("installments").value) || 0;

       var total = loanAmount + (loanAmount * (interestRate / 100)) + (loanAmount * (feePct / 100));
       var emi = installments > 0 ? (total / installments) : 0;

       document.getElementById("total_amount").value = total;
       document.getElementById("borrower_emi").value = emi;

       updateCollateralDefaults();
   }

   function updateCollateralDefaults() {
       var totalAmount = document.getElementById("total_amount").value;
       var outstandingLoanField = document.getElementById("outstanding_loan_value");
       var expectedReturnField = document.getElementById("expected_return_value");

       if (outstandingLoanField && outstandingLoanField.value.trim() === "") {
           outstandingLoanField.value = totalAmount;
       }
       if (expectedReturnField && expectedReturnField.value.trim() === "") {
           expectedReturnField.value = totalAmount;
       }
   }

   document.getElementById("processing_fee_pct").addEventListener("keyup", calculateEMI);
   document.getElementById("installments").addEventListener("keyup", calculateEMI);
   document.getElementById("loan_amount").addEventListener("keyup", calculateEMI);
</script>

<?php
include_once "inc/footer.php";
?>
