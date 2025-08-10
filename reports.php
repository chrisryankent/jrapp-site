<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ====================================================================
// Download Weekly Report as CSV
// ====================================================================
if(isset($_GET['download']) && $_GET['download'] === 'weekly') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=weekly_report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Week Number', 'Year', 'Start Date', 'End Date', 'Total Payments Received', 'Total Loan Amount', 'Total Profit', 'Number of Loans', 'Average Payment Rate (%)'));
    
    $weekly_query = "
        SELECT 
            WEEK(payment_date,1) AS week_num,
            YEAR(payment_date) AS year,
            MIN(payment_date) AS start_date,
            MAX(payment_date) AS end_date,
            SUM(amount) AS total_payments
        FROM tbl_payment
        GROUP BY YEAR(payment_date), WEEK(payment_date,1)
        ORDER BY YEAR(payment_date) DESC, WEEK(payment_date,1) DESC
    ";
    $result = mysqli_query($conn, $weekly_query);
    
    if($result && mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            $year = $row['year'];
            $week = $row['week_num'];
            $startDate = $row['start_date'];
            $endDate = $row['end_date'];
            $totalPayments = $row['total_payments'];
            
            // Optional: Get extra data for loans in that week.
            $loan_query = "
                SELECT 
                    COUNT(*) AS loan_count,
                    IFNULL(SUM(total_amount), 0) AS total_loan_amount,
                    IFNULL(SUM(total_amount - expected_amount), 0) AS total_profit,
                    IFNULL(AVG((amount_paid/total_amount)*100), 0) AS avg_payment_rate
                FROM tbl_loan_application
                WHERE YEAR(created_at) = '$year' AND WEEK(created_at,1) = '$week'
            ";
            $loan_result = mysqli_query($conn, $loan_query);
            $loan_data = mysqli_fetch_assoc($loan_result);
            
            fputcsv($output, array(
                $week,
                $year,
                $startDate,
                $endDate,
                $totalPayments,
                $loan_data['total_loan_amount'],
                $loan_data['total_profit'],
                $loan_data['loan_count'],
                number_format($loan_data['avg_payment_rate'],2)
            ));
        }
    } else {
        fputcsv($output, array('No data available'));
    }
    
    fclose($output);
    exit();
}

// ====================================================================
// Date Range Filtering for On-Screen Reports
// ====================================================================
// Get date filters from GET parameters.
$start_date_filter = isset($_GET['start_date']) ? mysqli_real_escape_string($conn, $_GET['start_date']) : '';
$end_date_filter   = isset($_GET['end_date'])   ? mysqli_real_escape_string($conn, $_GET['end_date'])   : '';

// For payment queries, no alias needed.
$where_payment = "";
if($start_date_filter && $end_date_filter) {
    $where_payment = "payment_date BETWEEN '$start_date_filter' AND '$end_date_filter'";
}

// For loan queries, we need two versions:
// 1. For queries that use an alias in tbl_loan_application (alias: la).
// 2. For queries that do not use an alias.
$where_loans_alias = "";
$where_loans_non_alias = "";
if($start_date_filter && $end_date_filter) {
    $where_loans_alias   = "la.created_at BETWEEN '$start_date_filter' AND '$end_date_filter'";
    $where_loans_non_alias = "created_at BETWEEN '$start_date_filter' AND '$end_date_filter'";
}

// ====================================================================
// Payment Summary
// ====================================================================
$query_total_payments = "SELECT IFNULL(SUM(amount), 0) AS total_payments FROM tbl_payment" . 
                        ($where_payment ? " WHERE $where_payment" : "");
$result_total_payments = mysqli_query($conn, $query_total_payments);
$total_payments = ($result_total_payments && $row = mysqli_fetch_assoc($result_total_payments)) ? $row['total_payments'] : 0;

$query_avg_payment_rate = "SELECT AVG((amount_paid/total_amount)*100) AS avg_payment_rate 
                          FROM tbl_loan_application 
                          WHERE total_amount > 0" . 
                          ($where_loans_non_alias ? " AND $where_loans_non_alias" : "");
$result_avg_payment_rate = mysqli_query($conn, $query_avg_payment_rate);
$avg_payment_rate = ($result_avg_payment_rate && $row = mysqli_fetch_assoc($result_avg_payment_rate)) ? $row['avg_payment_rate'] : 0;

if (is_null($avg_payment_rate)) {
    $avg_payment_rate = 0;
}
// ====================================================================
// Loan Summary
// ====================================================================
$query_loan_summary = "SELECT 
                          COUNT(*) AS total_loans,
                          IFNULL(SUM(expected_amount), 0) AS total_expected,
                          IFNULL(SUM(total_amount), 0) AS total_loan_amount,
                          IFNULL(SUM(total_amount - expected_amount), 0) AS total_profit
                       FROM tbl_loan_application" . 
                       ($where_loans_non_alias ? " WHERE status IN ('Approved','Disbursed','Closed') AND $where_loans_non_alias" : " WHERE status IN ('Approved','Disbursed','Closed')");
$result_loan_summary = mysqli_query($conn, $query_loan_summary);
$loan_summary = mysqli_fetch_assoc($result_loan_summary);

// ====================================================================
// Payment Rate by Loan/Borrower (using alias)
$query_payment_rates = "SELECT la.id AS loan_id, b.name, la.total_amount, la.amount_paid, la.status,
                              (la.amount_paid/la.total_amount*100) AS payment_rate
                        FROM tbl_loan_application la
                        INNER JOIN tbl_borrower b ON la.borrower_id = b.id" . 
                        ($where_loans_alias ? " WHERE la.total_amount > 0 AND $where_loans_alias" : " WHERE la.total_amount > 0") .
                        " ORDER BY la.created_at DESC";
$result_payment_rates = mysqli_query($conn, $query_payment_rates);
?>

<div class="content-wrapper">
  <div class="container-fluid mt-5">
    <h2>Advanced Reports</h2>
    
    <!-- Date Range Filter Form -->
    <form method="GET" action="reports.php" class="form-inline mb-4">
        <div class="form-group mr-2">
            <label for="start_date" class="mr-2">Start Date:</label>
            <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo htmlspecialchars($start_date_filter); ?>">
        </div>
        <div class="form-group mr-2">
            <label for="end_date" class="mr-2">End Date:</label>
            <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo htmlspecialchars($end_date_filter); ?>">
        </div>
        <input type="submit" class="btn btn-primary mr-2" value="Generate Report">
        <a href="reports.php?download=weekly" class="btn btn-success">Download Weekly Report</a>
    </form>
    
    <!-- Payment Summary Card -->
    <div class="card mb-3">
      <div class="card-header bg-success text-white">
        Payment Summary
      </div>
      <div class="card-body">
        <p><strong>Total Payments Received:</strong> <?php echo number_format($total_payments, 2); ?> tk</p>
        <p><strong>Average Payment Rate:</strong> <?php echo number_format($avg_payment_rate, 2); ?>%</p>
      </div>
    </div>
    
    <!-- Loan Summary Card -->
    <div class="card mb-3">
      <div class="card-header bg-info text-white">
        Loan Summary
      </div>
      <div class="card-body">
        <p><strong>Total Loans:</strong> <?php echo $loan_summary['total_loans']; ?></p>
        <p><strong>Total Expected Amount:</strong> <?php echo number_format($loan_summary['total_expected'], 2); ?> tk</p>
        <p><strong>Total Loan Amount:</strong> <?php echo number_format($loan_summary['total_loan_amount'], 2); ?> tk</p>
        <p><strong>Total Profit:</strong> <?php echo number_format($loan_summary['total_profit'], 2); ?> tk</p>
      </div>
    </div>
    
    <!-- Payment Rate by Loan/Borrower -->
    <div class="card mb-3">
      <div class="card-header bg-warning">
        Payment Rate by Loan/Borrower
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered table-hover">
            <thead>
              <tr>
                <th>Loan ID</th>
                <th>Borrower</th>
                <th>Total Amount (tk)</th>
                <th>Paid (tk)</th>
                <th>Payment Rate (%)</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if ($result_payment_rates && mysqli_num_rows($result_payment_rates) > 0) {
                  while ($row = mysqli_fetch_assoc($result_payment_rates)) {
                      echo "<tr>";
                      echo "<td>" . htmlspecialchars($row['loan_id']) . "</td>";
                      echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                      echo "<td>" . number_format($row['total_amount'], 2) . "</td>";
                      echo "<td>" . number_format($row['amount_paid'], 2) . "</td>";
                      echo "<td>" . number_format($row['payment_rate'], 2) . "%</td>";
                      echo "<td>" . htmlspecialchars($row['status']) . "</td>";
                      echo "</tr>";
                  }
              } else {
                  echo "<tr><td colspan='6' class='text-center'>No data available</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Weekly Payment Trend Chart -->
    <div class="card mb-3">
      <div class="card-header bg-primary text-white">
        Weekly Payment Trend
      </div>
      <div class="card-body">
        <canvas id="weeklyChart" width="400" height="150"></canvas>
      </div>
    </div>
    
  </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<?php
// Prepare data for weekly chart: Payment totals aggregated by week.
$chart_query = "
    SELECT 
        CONCAT('Week ', WEEK(payment_date,1)) AS week_label,
        SUM(amount) AS total_payments
    FROM tbl_payment
    GROUP BY WEEK(payment_date,1)
    ORDER BY WEEK(payment_date,1)
";
$chart_result = mysqli_query($conn, $chart_query);
$chart_labels = array();
$chart_data = array();
if ($chart_result && mysqli_num_rows($chart_result) > 0) {
    while ($row = mysqli_fetch_assoc($chart_result)) {
        $chart_labels[] = $row['week_label'];
        $chart_data[] = $row['total_payments'];
    }
}
?>
<script>
    var ctx = document.getElementById('weeklyChart').getContext('2d');
    var weeklyChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                label: 'Total Payments Received (tk)',
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: 'rgba(54, 162, 235, 0.2)',
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
</script>

<?php
include_once "inc/footer.php";
?>
