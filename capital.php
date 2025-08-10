<?php
session_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 0) Branch scoping
if (empty($_SESSION['branch_id'])) {
    die("<div class='alert alert-danger'>Access denied: no branch assigned.</div>");
}
$branch_id = (int)$_SESSION['branch_id'];

// --- STEP 1: Calculate Money Used for this Branch ---
// Sum total_amount from approved loans for this branch
$loan_query = "
    SELECT IFNULL(SUM(expected_amount),0) AS loan_sum
      FROM tbl_loan_application
     WHERE status = 'Approved'
       AND branch_id = $branch_id
";
$result_loans = mysqli_query($conn, $loan_query);
$loan_sum = ($result_loans && $r = mysqli_fetch_assoc($result_loans))
          ? $r['loan_sum'] : 0;

// Sum expense_amount from expenses for this branch
$expense_query = "
    SELECT IFNULL(SUM(expense_amount),0) AS expense_sum
      FROM tbl_expenses
     WHERE branch_id = $branch_id
";
$result_expenses = mysqli_query($conn, $expense_query);
$expense_sum = ($result_expenses && $r2 = mysqli_fetch_assoc($result_expenses))
             ? $r2['expense_sum'] : 0;

// Total used
$money_used = $loan_sum + $expense_sum;

// --- STEP 2: Fetch (or Create) Capital Record for this Branch ---
$cap_res = mysqli_query($conn,
    "SELECT * 
       FROM tbl_capital 
      WHERE branch_id = $branch_id 
      LIMIT 1"
);
if ($cap_res && mysqli_num_rows($cap_res) > 0) {
    $capital = mysqli_fetch_assoc($cap_res);
} else {
    // no record yet → insert initial zero record
    mysqli_query($conn,
      "INSERT INTO tbl_capital (branch_id, amount, amount_used, entry_type)
       VALUES ($branch_id, 0, 0, 'initial')"
    );
    $capital = mysqli_fetch_assoc(
      mysqli_query($conn,
        "SELECT * FROM tbl_capital 
           WHERE branch_id = $branch_id 
           LIMIT 1"
      )
    );
}

// --- STEP 3: Update amount_used in the DB ---
mysqli_query($conn,
    "UPDATE tbl_capital
        SET amount_used = $money_used
      WHERE branch_id = $branch_id"
);
$capital['amount_used'] = $money_used;
$capital['amount_remaining'] = $capital['amount'] - $money_used;

// --- STEP 4: Handle Your Form Submission ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_capital_submit'])) {
    $type = $_POST['entry_type'];           // initial|topup|withdrawal
    $val  = floatval($_POST['new_investment']);
    
    if ($val > 0) {
        $current = floatval($capital['amount']);
        switch ($type) {
            case 'initial':
                $new_amount = $val;
                break;
            case 'topup':
                $new_amount = $current + $val;
                break;
            case 'withdrawal':
                $new_amount = max(0, $current - $val);
                break;
            default:
                $new_amount = $current;
        }
        // Update the single capital row
        mysqli_query($conn,
          "UPDATE tbl_capital
              SET amount = $new_amount,
                  entry_type = '$type'
            WHERE branch_id = $branch_id"
        );
        // refresh values in $capital
        $capital['amount'] = $new_amount;
        $capital['amount_remaining'] = $new_amount - $capital['amount_used'];
        echo "<div class='alert alert-success'>Capital updated.</div>";
    } else {
        echo "<div class='alert alert-danger'>Enter a positive amount.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Capital Management</title>
</head>
<body>
<div class="container mt-4">
  <h2>Capital Management (Branch #<?php echo $branch_id;?>)</h2>
  <p>
    Money Used = Sum of this branch’s approved loans + expenses.<br>
    Remaining = Total Investment − Money Used.
  </p>

  <!-- Form to Initial / Top-up / Withdraw -->
  <div class="card mb-4">
    <div class="card-header bg-primary text-white">Adjust Capital</div>
    <div class="card-body">
      <form method="POST">
        <div class="form-group">
          <label>Action</label>
          <select name="entry_type" class="form-control" required>
            <option value="initial">Initial (Set Total)</option>
            <option value="topup">Top-Up (Add)</option>
            <option value="withdrawal">Withdrawal (Subtract)</option>
          </select>
        </div>
        <div class="form-group">
          <label>Amount (UGX)</label>
          <input 
            type="number" 
            name="new_investment" 
            step="0.01" 
            class="form-control" 
            placeholder="Enter amount" 
            required>
        </div>
        <button type="submit" name="update_capital_submit" class="btn btn-success">
          Apply
        </button>
      </form>
    </div>
  </div>

  <!-- Display the Branch's Capital Record -->
  <div class="card">
    <div class="card-header bg-secondary text-white">Current Capital Status</div>
    <div class="card-body">
      <table class="table table-bordered">
        <tr>
          <th>Total Investment (UGX)</th>
          <td><?php echo number_format($capital['amount'],2);?></td>
        </tr>
        <tr>
          <th>Money Used (UGX)</th>
          <td><?php echo number_format($capital['amount_used'],2);?></td>
        </tr>
        <tr>
          <th>Remaining Capital (UGX)</th>
          <td><?php echo number_format($capital['amount_remaining'],2);?></td>
        </tr>
        <tr>
          <th>Last Entry Type</th>
          <td><?php echo ucfirst($capital['entry_type']);?></td>
        </tr>
        <tr>
          <th>Record Created</th>
          <td><?php echo htmlspecialchars($capital['created_at']);?></td>
        </tr>
      </table>
    </div>
  </div>
</div>
<?php include_once "inc/footer.php";?>
</body>
</html>
