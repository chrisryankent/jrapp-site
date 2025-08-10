<?php
session_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1); // Database connection

// Alert message variable
$alert = "";

// Verify that a loan_id is provided.
if (isset($_GET['loan_id'])) {
    $loan_id = mysqli_real_escape_string($conn, $_GET['loan_id']);
} else {
    $alert = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                Invalid loan ID.
                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                  <span aria-hidden='true'>&times;</span>
                </button>
              </div>";
}

// Fetch loan and borrower details.
if (empty($alert)) {
    $loan_query = "SELECT la.*, b.name, b.nid, b.mobile, b.email, b.selfie_path AS photo, b.address, b.id AS borrower_id 
                   FROM tbl_loan_application la
                   INNER JOIN tbl_borrower b ON la.borrower_id = b.id
                   WHERE la.id = '$loan_id'";
    $loan_result = mysqli_query($conn, $loan_query);
    if ($loan_result && mysqli_num_rows($loan_result) > 0) {
        $loanRow = mysqli_fetch_assoc($loan_result);
    } else {
        $alert = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                    Loan or borrower details not found.
                    <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                      <span aria-hidden='true'>&times;</span>
                    </button>
                  </div>";
    }
}

// Get role and user id from session using correct keys.
$role_id = isset($_SESSION['role_id']) ? $_SESSION['role_id'] : 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

// Loan verification and rejection logic
if ($_SERVER['REQUEST_METHOD'] == 'POST' && empty($alert)) {
    // Approve
    if (isset($_POST['verify_submit'])) {
        if ($role_id == 3 && $loanRow['status'] == 'Pending') {
            $update_query = "UPDATE tbl_loan_application 
                             SET approved_by = '$user_id', status = 'Approved'
                             WHERE id = '$loan_id'";
            if (mysqli_query($conn, $update_query)) {
                $alert = "<div class='alert alert-success alert-dismissible fade show mt-3' role='alert'>
                            Loan verified successfully and approved!
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                          </div>";
                $loan_result = mysqli_query($conn, $loan_query);
                $loanRow = mysqli_fetch_assoc($loan_result);
            } else {
                $alert = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                            Error updating verification: " . mysqli_error($conn) . "
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                          </div>";
            }
        } else {
            $alert = "<div class='alert alert-warning alert-dismissible fade show mt-3' role='alert'>
                        You are not authorized to verify this loan at this stage.
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                          <span aria-hidden='true'>&times;</span>
                        </button>
                      </div>";
        }
    }
    // Reject
    if (isset($_POST['reject_submit'])) {
        if ($role_id == 3 && $loanRow['status'] == 'Pending') {
            $reason = mysqli_real_escape_string($conn, $_POST['reject_reason']);
            $update_query = "UPDATE tbl_loan_application 
                             SET status = 'Rejected', rejection_reason = '$reason'
                             WHERE id = '$loan_id'";
            if (mysqli_query($conn, $update_query)) {
                $alert = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                            Loan application rejected.
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                          </div>";
                $loan_result = mysqli_query($conn, $loan_query);
                $loanRow = mysqli_fetch_assoc($loan_result);
            } else {
                $alert = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                            Error rejecting loan: " . mysqli_error($conn) . "
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                          </div>";
            }
        } else {
            $alert = "<div class='alert alert-warning alert-dismissible fade show mt-3' role='alert'>
                        You are not authorized to reject this loan at this stage.
                        <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                          <span aria-hidden='true'>&times;</span>
                        </button>
                      </div>";
        }
    }
}

// Fetch guarantor details.
$guarantor = null;
if (empty($alert)) {
    $gua_query = "SELECT * FROM tbl_guarantor WHERE loan_application_id = '$loan_id'";
    $gua_result = mysqli_query($conn, $gua_query);
    $guarantor = ($gua_result && mysqli_num_rows($gua_result) > 0) ? mysqli_fetch_assoc($gua_result) : null;
}

// Fetch the latest KYC record.
$kyc = null;
if (empty($alert)) {
    $borrower_id = $loanRow['borrower_id'];
    $kyc_query = "SELECT * FROM tbl_kyc_record WHERE borrower_id = '$borrower_id' ORDER BY created_at DESC LIMIT 1";
    $kyc_result = mysqli_query($conn, $kyc_query);
    $kyc = ($kyc_result && mysqli_num_rows($kyc_result) > 0) ? mysqli_fetch_assoc($kyc_result) : null;
}

// Fetch loan history (previous loans).
$history_result = null;
if (empty($alert)) {
    $history_query = "SELECT * FROM tbl_loan_application 
                      WHERE borrower_id = '$borrower_id' AND id <> '$loan_id' 
                      ORDER BY created_at DESC";
    $history_result = mysqli_query($conn, $history_query);
}

// Fetch latest credit score.
$creditScore = null;
if (empty($alert)) {
    $cs_query = "SELECT * FROM tbl_credit_score WHERE borrower_id = '$borrower_id' ORDER BY report_date DESC LIMIT 1";
    $cs_result = mysqli_query($conn, $cs_query);
    $creditScore = ($cs_result && mysqli_num_rows($cs_result) > 0) ? mysqli_fetch_assoc($cs_result) : null;
}
?>

<div class="content-wrapper">
  <div class="container-fluid mt-4">
    <?php if (!empty($alert)) echo "<div class='row'><div class='col-md-8 offset-md-2'>{$alert}</div></div>"; ?>

    <?php if (empty($alert)): ?>
    <!-- Borrower Details Card -->
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">
        Borrower Details
      </div>
      <div class="card-body">
        <div class="row">
          <div class="col-md-4 text-center">
            <img src="<?php echo htmlspecialchars($loanRow['photo']); ?>" alt="Borrower Photo" class="img-thumbnail" style="max-width:150px;">
          </div>
          <div class="col-md-8">
            <table class="table table-borderless">
              <tbody>
                <tr>
                  <th>Name:</th>
                  <td><?php echo htmlspecialchars($loanRow['name']); ?></td>
                </tr>
                <tr>
                  <th>National ID:</th>
                  <td><?php echo htmlspecialchars($loanRow['nid']); ?></td>
                </tr>
                <tr>
                  <th>Phone:</th>
                  <td><?php echo htmlspecialchars($loanRow['mobile']); ?></td>
                </tr>
                <tr>
                  <th>Email:</th>
                  <td>
                    <?php echo htmlspecialchars($loanRow['email']); ?>
                    <?php if (filter_var($loanRow['email'], FILTER_VALIDATE_EMAIL)): ?>
                      <span class="badge badge-success">Valid</span>
                    <?php else: ?>
                      <span class="badge badge-danger">Invalid</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <tr>
                  <th>Address:</th>
                  <td><?php echo htmlspecialchars($loanRow['address']); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <!-- Loan Details Card -->
    <div class="card mb-3">
      <div class="card-header bg-secondary text-white">
        Loan Details
      </div>
      <div class="card-body">
        <table class="table table-borderless">
          <tbody>
            <tr>
              <th>Expected Loan:</th>
              <td><?php echo htmlspecialchars($loanRow['expected_amount']); ?> tk</td>
            </tr>
            <tr>
              <th>Total Loan (with interest):</th>
              <td><?php echo htmlspecialchars($loanRow['total_amount']); ?> tk</td>
            </tr>
            <tr>
              <th>EMI:</th>
              <td><?php echo htmlspecialchars($loanRow['emi_amount']); ?> tk/month</td>
            </tr>
            <tr>
              <th>Loan Status:</th>
              <td>
                <?php echo htmlspecialchars($loanRow['status']); ?>
                <?php if ($loanRow['status'] == 'Rejected' && !empty($loanRow['rejection_reason'])): ?>
                  <br><span class="text-danger"><strong>Reason:</strong> <?php echo htmlspecialchars($loanRow['rejection_reason']); ?></span>
                <?php endif; ?>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Guarantor Details Card -->
    <div class="card mb-3">
      <div class="card-header bg-dark text-white">
        Guarantor Details
      </div>
      <div class="card-body">
        <?php if ($guarantor): ?>
          <table class="table table-borderless">
            <tbody>
              <tr>
                <th>Name:</th>
                <td><?php echo htmlspecialchars($guarantor['name']); ?></td>
              </tr>
              <tr>
                <th>Relationship:</th>
                <td><?php echo htmlspecialchars($guarantor['relationship']); ?></td>
              </tr>
              <tr>
                <th>Contact:</th>
                <td><?php echo htmlspecialchars($guarantor['contact']); ?></td>
              </tr>
              <tr>
                <th>National ID:</th>
                <td><?php echo htmlspecialchars($guarantor['nid']); ?></td>
              </tr>
              <tr>
                <th>Address:</th>
                <td><?php echo htmlspecialchars($guarantor['address']); ?></td>
              </tr>
            </tbody>
          </table>
        <?php else: ?>
          <p>No guarantor information available.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- KYC Documents Card -->
    <div class="card mb-3">
      <div class="card-header bg-info text-white">
        KYC Documents
      </div>
      <div class="card-body">
        <?php if ($kyc): ?>
          <p><strong>Scanned Data:</strong></p>
          <pre style="background-color:#f8f9fa; border:1px solid #dee2e6; padding:10px;"><?php 
              $scanned = json_decode($kyc['scanned_data'], true);
              echo $scanned ? json_encode($scanned, JSON_PRETTY_PRINT) : htmlspecialchars($kyc['scanned_data']);
            ?></pre>
          <div class="row mt-3">
            <div class="col-md-4 text-center">
              <p><strong>Front ID</strong></p>
              <?php if (!empty($kyc['front_image_path'])): ?>
                <a href="<?php echo htmlspecialchars($kyc['front_image_path']); ?>" target="_blank">
                  <img src="<?php echo htmlspecialchars($kyc['front_image_path']); ?>" alt="Front ID" class="img-thumbnail" style="max-width:150px;">
                </a>
              <?php else: ?>
                N/A
              <?php endif; ?>
            </div>
            <div class="col-md-4 text-center">
              <p><strong>Back ID</strong></p>
              <?php if (!empty($kyc['back_image_path'])): ?>
                <a href="<?php echo htmlspecialchars($kyc['back_image_path']); ?>" target="_blank">
                  <img src="<?php echo htmlspecialchars($kyc['back_image_path']); ?>" alt="Back ID" class="img-thumbnail" style="max-width:150px;">
                </a>
              <?php else: ?>
                N/A
              <?php endif; ?>
            </div>
            <div class="col-md-4 text-center">
              <p><strong>Selfie</strong></p>
              <?php if (!empty($kyc['selfie_path'])): ?>
                <a href="<?php echo htmlspecialchars($kyc['selfie_path']); ?>" target="_blank">
                  <img src="<?php echo htmlspecialchars($kyc['selfie_path']); ?>" alt="Selfie" class="img-thumbnail" style="max-width:150px;">
                </a>
              <?php else: ?>
                N/A
              <?php endif; ?>
            </div>
          </div>
        <?php else: ?>
          <p>No KYC information available.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Loan History Card -->
    <div class="card mb-3">
      <div class="card-header bg-secondary text-white">
        Loan History
      </div>
      <div class="card-body">
        <?php if ($history_result && mysqli_num_rows($history_result) > 0): ?>
          <div class="table-responsive">
            <table class="table table-sm table-bordered">
              <thead>
                <tr>
                  <th>Loan ID</th>
                  <th>Expected</th>
                  <th>Total</th>
                  <th>EMI</th>
                  <th>Status</th>
                  <th>Applied On</th>
                </tr>
              </thead>
              <tbody>
                <?php while ($hRow = mysqli_fetch_assoc($history_result)): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($hRow['id']); ?></td>
                    <td><?php echo htmlspecialchars($hRow['expected_amount']); ?> tk</td>
                    <td><?php echo htmlspecialchars($hRow['total_amount']); ?> tk</td>
                    <td><?php echo htmlspecialchars($hRow['emi_amount']); ?> tk/month</td>
                    <td><?php echo htmlspecialchars($hRow['status']); ?></td>
                    <td><?php echo date("d M Y", strtotime($hRow['created_at'])); ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <p>No previous loan history available.</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Credit Score Card -->
    <div class="card mb-3">
      <div class="card-header bg-dark text-white">
        Credit Score
      </div>
      <div class="card-body">
        <?php if ($creditScore): ?>
          <p><strong>Score:</strong> <?php echo htmlspecialchars($creditScore['score']); ?></p>
          <p><strong>Report Date:</strong> <?php echo date("d M Y", strtotime($creditScore['report_date'])); ?></p>
          <p><strong>Remarks:</strong> <?php echo htmlspecialchars($creditScore['remarks']); ?></p>
        <?php else: ?>
          <p>New Borrower</p>
        <?php endif; ?>
      </div>
    </div>

    <!-- Loan Verification & Rejection Form (Only for Role ID 3) -->
    <?php if ($loanRow['status'] == 'Pending'): ?>
      <div class="row">
        <div class="col-md-6 mb-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Verify or Reject Loan</h5>
              <?php if ($role_id == 3): ?>
                <form action="" method="POST" style="display:inline-block;">
                  <input type="submit" name="verify_submit" class="btn btn-primary btn-sm" value="Verify Loan">
                </form>
                <form action="" method="POST" style="display:inline-block; margin-left:10px;">
                  <div class="form-group">
                    <input type="text" name="reject_reason" class="form-control form-control-sm" placeholder="Reason for rejection" required>
                  </div>
                  <input type="submit" name="reject_submit" class="btn btn-danger btn-sm" value="Reject Loan">
                </form>
              <?php else: ?>
                <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
                  Only a verifier (Role ID 3) can verify or reject this loan.
                  <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                  </button>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Deposit Money Button (Appears Once Loan is Approved) -->
    <?php if ($loanRow['status'] == 'Approved'): ?>
      <div class="row">
        <div class="col-md-6 mb-4">
          <div class="card">
            <div class="card-body">
              <h5 class="card-title">Deposit Requested Money</h5>
              <form action="deposit_money.php" method="GET">
                <input type="hidden" name="loan_id" value="<?php echo htmlspecialchars($loan_id); ?>">
                <input type="submit" class="btn btn-success btn-sm" value="Deposit Money">
              </form>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <?php endif; // end if no alert ?>
  </div>
</div>

<?php
include_once "inc/footer.php";
?>
