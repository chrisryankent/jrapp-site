<?php
session_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// For demonstration, assume the borrower is logged in and their loan_application_id is known.
// You would normally get this via session or a URL parameter.
$loan_application_id = isset($_GET['loan_id']) ? intval($_GET['loan_id']) : 0;
$current_date = date('Y-m-d');

// Query repayment schedule for the selected loan
$repayment_query = "SELECT rs.*, 
                           CASE 
                             WHEN rs.due_date < '$current_date' AND rs.is_paid = 0 THEN 'Overdue'
                             WHEN rs.is_paid = 1 THEN 'Paid'
                             ELSE 'Upcoming'
                           END AS installment_status
                    FROM tbl_repayment_schedule rs
                    WHERE rs.loan_application_id = '$loan_application_id'
                    ORDER BY rs.installment_no ASC";
$result_schedule = mysqli_query($conn, $repayment_query);

// Optionally, query full payment history
$payment_query = "SELECT * FROM tbl_payment 
                  WHERE loan_application_id = '$loan_application_id'
                  ORDER BY payment_date DESC";
$result_payments = mysqli_query($conn, $payment_query);
?>

<div class="container mt-4">
  <h2>Repayment Schedule</h2>

  <!-- Repayment Schedule Table -->
  <div class="card mb-4">
    <div class="card-header bg-info text-white">Installments for Loan #<?php echo $loan_application_id; ?></div>
    <div class="card-body">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Installment No.</th>
            <th>Due Date</th>
            <th>Due Amount (tk)</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          if ($result_schedule && mysqli_num_rows($result_schedule) > 0) {
              while ($row = mysqli_fetch_assoc($result_schedule)) {
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($row['installment_no']) . "</td>";
                  echo "<td>" . htmlspecialchars($row['due_date']) . "</td>";
                  echo "<td>" . number_format($row['due_amount'], 2) . "</td>";
                  echo "<td>" . htmlspecialchars($row['installment_status']) . "</td>";
                  echo "</tr>";
              }
          } else {
              echo "<tr><td colspan='4' class='text-center'>No schedule found.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Payment History Table -->
  <div class="card mb-4">
    <div class="card-header bg-dark text-white">Payment History</div>
    <div class="card-body">
      <table class="table table-bordered table-striped">
        <thead>
          <tr>
            <th>Payment Date</th>
            <th>Amount (tk)</th>
            <th>Installment No.</th>
            <th>Fine (tk)</th>
          </tr>
        </thead>
        <tbody>
          <?php 
          if ($result_payments && mysqli_num_rows($result_payments) > 0) {
              while ($row = mysqli_fetch_assoc($result_payments)) {
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($row['payment_date']) . "</td>";
                  echo "<td>" . number_format($row['amount'], 2) . "</td>";
                  echo "<td>" . htmlspecialchars($row['installment_no']) . "</td>";
                  echo "<td>" . number_format($row['fine_amount'], 2) . "</td>";
                  echo "</tr>";
              }
          } else {
              echo "<tr><td colspan='4' class='text-center'>No payment history available.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  
  <!-- Optional: Prepayment / Early Repayment Option -->
  <div class="card mb-4">
    <div class="card-header bg-success text-white">Early Repayment</div>
    <div class="card-body">
      <form method="POST" action="prepay.php">
        <!-- In a real system, include loan id, borrower id, and new prepayment amount -->
        <input type="hidden" name="loan_application_id" value="<?php echo $loan_application_id; ?>">
        <div class="form-group">
          <label for="prepay_amount">Prepayment Amount (tk):</label>
          <input type="number" name="prepay_amount" id="prepay_amount" step="0.01" class="form-control" required>
        </div>
        <input type="submit" class="btn btn-primary" value="Prepay Now">
      </form>
    </div>
  </div>
  
</div>
<?php include_once "inc/footer.php"; ?>
