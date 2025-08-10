<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";

// -- SESSION & SEND REMINDER LOGIC --
if (!isset($_SESSION['branch_id'])) {
    echo "<div class='content-wrapper'><div class='container-fluid'><div class='row'><div class='col-md-8 offset-md-2'><div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>Error: Please log in.<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div></div></div></div></div>";
    include_once "inc/footer.php";
    exit;
}

// Handle email reminder for current loan
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_email'])) {
    $loan_id = (int)$_POST['loan_id'];
    $bQ = mysqli_query($conn, "SELECT b.email,b.name,l.amount_remaining,l.next_due_date 
                               FROM tbl_borrower b 
                               JOIN tbl_loan_application l ON b.id=l.borrower_id 
                               WHERE l.id='$loan_id' LIMIT 1");
    if ($bQ && $br = mysqli_fetch_assoc($bQ)) {
        $to      = $br['email'];
        $subject = "Loan Payment Reminder";
        $message = "Dear {$br['name']},\n\n"
                 ."Please settle your outstanding balance of UGX {$br['amount_remaining']}.\n"
                 ."Next due date: {$br['next_due_date']}.\n\nThank you.";
        $headers = "From: no-reply@loanmanagement.com";
        if (mail($to,$subject,$message,$headers)) {
            $email_msg = "<div class='alert alert-success alert-dismissible fade show mt-3' role='alert'>
                            Reminder sent to {$br['email']}
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                          </div>";
        } else {
            $email_msg = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                            Failed to send reminder.
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                          </div>";
        }
    }
}

// -- FETCH BORROWER --
if (!isset($_GET['borrower_id'])) {
    echo "<div class='content-wrapper'><div class='container-fluid'><div class='row'><div class='col-md-8 offset-md-2'><div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>No borrower selected.<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div></div></div></div></div>";
    include_once "inc/footer.php";
    exit;
}
$bid = (int)$_GET['borrower_id'];
$bRes = mysqli_query($conn,"SELECT * FROM tbl_borrower WHERE id='$bid' LIMIT 1");
if (!$bRes || !mysqli_num_rows($bRes)) {
    echo "<div class='content-wrapper'><div class='container-fluid'><div class='row'><div class='col-md-8 offset-md-2'><div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>Borrower not found.<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div></div></div></div></div>";
    include_once "inc/footer.php";
    exit;
}
$borrower = mysqli_fetch_assoc($bRes);

// -- FETCH ALL LOANS --
$loans = [];
$lRes = mysqli_query($conn, "SELECT * FROM tbl_loan_application 
                              WHERE borrower_id='$bid' 
                              ORDER BY created_at DESC, id DESC");
while ($row = mysqli_fetch_assoc($lRes)) {
    $loans[] = $row;
}

// -- FETCH LAST KYC --
$kyc = null;
$kycQ = mysqli_query($conn,"SELECT * FROM tbl_kyc_record WHERE borrower_id='$bid' ORDER BY created_at DESC LIMIT 1");
if ($kycQ && mysqli_num_rows($kycQ)) {
    $kyc = mysqli_fetch_assoc($kycQ);
}
?>

<div class="content-wrapper">
  <div class="container-fluid mt-4">
    <?php if(isset($email_msg)) echo "<div class='row'><div class='col-md-8 offset-md-2'>{$email_msg}</div></div>"; ?>

    <!-- Borrower Info -->
    <div class="card mb-4">
      <div class="card-header bg-primary text-white">Borrower Information</div>
      <div class="card-body row">
        <div class="col-md-4 text-center">
          <img src="<?php echo $borrower['selfie_path'];?>" class="img-thumbnail" style="max-width:250px;">
        </div>
        <div class="col-md-8">
          <ul class="list-group">
            <li class="list-group-item"><strong>Name:</strong> <?php echo htmlspecialchars($borrower['name']);?></li>
            <li class="list-group-item"><strong>NID:</strong> <?php echo htmlspecialchars($borrower['nid']);?></li>
            <li class="list-group-item"><strong>Mobile:</strong> <?php echo htmlspecialchars($borrower['mobile']);?></li>
            <li class="list-group-item"><strong>Email:</strong> <?php echo htmlspecialchars($borrower['email']);?></li>
            <li class="list-group-item"><strong>DOB:</strong> <?php echo htmlspecialchars($borrower['dob']);?></li>
            <li class="list-group-item"><strong>Address:</strong> <?php echo htmlspecialchars($borrower['address']);?></li>
            <li class="list-group-item"><strong>Status:</strong> <?php echo htmlspecialchars($borrower['working_status']);?></li>
          </ul>
        </div>
      </div>
    </div>

    <?php if(count($loans)): 
          $current = $loans[0]; ?>
    <!-- Current Loan Details -->
    <div class="card mb-4">
      <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
        <span>Current Loan (ID #<?php echo $current['id'];?>)</span>
        <a href="download_payments.php?loan_id=<?php echo $current['id'];?>" class="btn btn-light btn-sm">
          <i class="fas fa-download"></i> Download Payment History
        </a>
      </div>
      <div class="card-body">
        <ul class="list-group">
          <li class="list-group-item"><strong>Loan Amount:</strong> UGX <?php echo number_format($current['expected_amount']);?></li>
          <li class="list-group-item"><strong>Total with Interest:</strong> UGX <?php echo number_format($current['total_amount']);?></li>
          <li class="list-group-item"><strong>EMI:</strong> UGX <?php echo number_format($current['emi_amount']);?></li>
          <li class="list-group-item"><strong>Balance:</strong> UGX <?php echo number_format($current['amount_remaining']);?></li>
          <li class="list-group-item"><strong>Installments:</strong> <?php echo $current['installments'];?></li>
          <li class="list-group-item"><strong>Next Due Date:</strong> <?php echo $current['next_due_date'];?></li>
          <li class="list-group-item"><strong>Status:</strong> <?php echo $current['status'];?></li>
        </ul>

        <?php // Payment History Table
          $pRes = mysqli_query($conn,"SELECT * FROM tbl_payment WHERE loan_application_id='{$current['id']}' ORDER BY payment_date DESC");
        ?>
        <h5 class="mt-4">Payment History</h5>
        <table class="table table-sm table-bordered">
          <thead>
            <tr><th>Date</th><th>Amt (UGX)</th><th>Inst #</th><th>Fine</th></tr>
          </thead>
          <tbody>
          <?php while($p = mysqli_fetch_assoc($pRes)): ?>
            <tr>
              <td><?php echo $p['payment_date'];?></td>
              <td><?php echo number_format($p['amount']);?></td>
              <td><?php echo $p['installment_no'];?></td>
              <td><?php echo number_format($p['fine_amount']);?></td>
            </tr>
          <?php endwhile;?>
          <?php if(!mysqli_num_rows($pRes)): ?>
            <tr><td colspan="4" class="text-center">No payments yet.</td></tr>
          <?php endif;?>
          </tbody>
        </table>

        <?php // Guarantors
          $gRes = mysqli_query($conn,"SELECT * FROM tbl_guarantor WHERE loan_application_id='{$current['id']}'");
        ?>
        <h5 class="mt-4">Guarantor(s)</h5>
        <table class="table table-sm table-bordered">
          <thead><tr><th>Name</th><th>Relation</th><th>Contact</th><th>NID</th><th>Address</th></tr></thead>
          <tbody>
          <?php while($g = mysqli_fetch_assoc($gRes)): ?>
            <tr>
              <td><?php echo htmlspecialchars($g['name']);?></td>
              <td><?php echo htmlspecialchars($g['relationship']);?></td>
              <td><?php echo htmlspecialchars($g['contact']);?></td>
              <td><?php echo htmlspecialchars($g['nid']);?></td>
              <td><?php echo htmlspecialchars($g['address']);?></td>
            </tr>
          <?php endwhile;?>
          <?php if(!mysqli_num_rows($gRes)): ?>
            <tr><td colspan="5" class="text-center">No guarantors recorded.</td></tr>
          <?php endif;?>
          </tbody>
        </table>

        <?php // Collateral
          $cRes = mysqli_query($conn,"SELECT * FROM tbl_liability WHERE loan_application_id='{$current['id']}'");
        ?>
        <h5 class="mt-4">Collateral</h5>
        <table class="table table-sm table-bordered">
          <thead><tr><th>Asset</th><th>Details</th><th>Market (UGX)</th><th>Outstanding (UGX)</th><th>Expiry Value (UGX)</th><th>Valuation Date</th></tr></thead>
          <tbody>
          <?php while($c = mysqli_fetch_assoc($cRes)): ?>
            <tr>
              <td><?php echo htmlspecialchars($c['property_name']);?></td>
              <td><?php echo htmlspecialchars($c['property_details']);?></td>
              <td><?php echo number_format($c['market_value']);?></td>
              <td><?php echo number_format($c['outstanding_loan_value']);?></td>
              <td><?php echo number_format($c['expected_return_value']);?></td>
              <td><?php echo $c['valuation_date'];?></td>
            </tr>
          <?php endwhile;?>
          <?php if(!mysqli_num_rows($cRes)): ?>
            <tr><td colspan="6" class="text-center">No collateral recorded.</td></tr>
          <?php endif;?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Previous Loans -->
    <?php if(count($loans)>1): ?>
    <div class="card mb-4">
      <div class="card-header bg-secondary text-white">Previous Loans</div>
      <div class="card-body p-0">
        <table class="table table-striped mb-0">
          <thead>
            <tr>
              <th>ID</th>
              <th>Principal</th>
              <th>Total</th>
              <th>Balance</th>
              <th>Status</th>
              <th>Started</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php for($i=1; $i<count($loans); $i++):
                  $l = $loans[$i]; ?>
            <tr>
              <td><?php echo $l['id'];?></td>
              <td>UGX <?php echo number_format($l['expected_amount']);?></td>
              <td>UGX <?php echo number_format($l['total_amount']);?></td>
              <td>UGX <?php echo number_format($l['amount_remaining']);?></td>
              <td><?php echo $l['status'];?></td>
              <td><?php echo $l['created_at'];?></td>
              <td>
                <a href="download_payments.php?loan_id=<?php echo $l['id'];?>" 
                   class="btn btn-light btn-sm">
                  <i class="fas fa-download"></i> CSV
                </a>
              </td>
            </tr>
          <?php endfor; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Last KYC -->
    <?php if($kyc): ?>
    <div class="card mb-4">
      <div class="card-header bg-info text-white">Latest KYC Record</div>
      <div class="card-body">
        <pre><?php echo json_encode(json_decode($kyc['scanned_data'],true),JSON_PRETTY_PRINT);?></pre>
        <div class="row">
          <div class="col-md-6 text-center">
            <img src="<?php echo $kyc['front_image_path'];?>" class="img-thumbnail" style="max-width:250px;">
          </div>
          <div class="col-md-6 text-center">
            <img src="<?php echo $kyc['back_image_path'];?>" class="img-thumbnail" style="max-width:250px;">
          </div>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <?php else: ?>
      <div class="row"><div class="col-md-8 offset-md-2"><div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
        No loans found for this borrower.
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div></div></div>
    <?php endif; ?>

  </div>
</div>
<?php include_once "inc/footer.php"; ?>