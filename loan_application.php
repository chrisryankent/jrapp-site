<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php"; // Include database connection
?>

<div class="content-wrapper">
  <div class="container-fluid mt-4">
    <div class="card">
      <div class="card-header bg-primary text-white">
        All Loan Applications
      </div>
      <div class="card-body">
        <h5 class="card-title">Loan Details</h5>
        <div class="table-responsive">
          <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
            <thead>
              <tr>
                <th>Name</th>
                <th>NID</th>
                <th>DOB</th>
                <th>Expected Loan</th>
                <th>Percentage</th>
                <th>Installments</th>
                <th>Total Loan</th>
                <th>EMI</th>
                <th>Documents</th>
                <th>Report</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php
              // Fetch all loan applications
              $query = "SELECT la.*, b.name, b.nid, b.dob 
                        FROM tbl_loan_application la
                        INNER JOIN tbl_borrower b ON la.borrower_id = b.id";
              $result = mysqli_query($conn, $query);

              if ($result && mysqli_num_rows($result) > 0) {
                while ($row = mysqli_fetch_assoc($result)) {
              ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                    <td><?php echo htmlspecialchars($row['nid']); ?></td>
                    <td><?php echo htmlspecialchars($row['dob']); ?></td>
                    <td><?php echo htmlspecialchars($row['expected_amount']); ?> tk</td>
                    <td>
                      <?php
                        // If you have interest rate percent, show it; else show N/A
                        echo isset($row['interest_rate_percent']) ? htmlspecialchars($row['interest_rate_percent']) . '%' : 'N/A';
                      ?>
                    </td>
                    <td><?php echo htmlspecialchars($row['installments']); ?></td>
                    <td><?php echo htmlspecialchars($row['total_amount']); ?> tk</td>
                    <td><?php echo htmlspecialchars($row['emi_amount']); ?> tk/month</td>
                    <td>
                      <?php if (!empty($row['document_path'])): ?>
                        <a href="<?php echo htmlspecialchars($row['document_path']); ?>" target="_blank">Download</a>
                      <?php else: ?>
                        N/A
                      <?php endif; ?>
                    </td>
                    <td>
                      <a target="_blank" href="loan_app_report.php?loan_id=<?php echo $row['id']; ?>">Report</a>
                    </td>
                    <td>
                      <?php
                        // Adjust status display according to your status values
                        if ($row['status'] == 'Approved' || $row['status'] == 3) {
                          echo "<label class='badge badge-success'>Approved</label>";
                        } elseif ($row['status'] == 2 || $row['status'] == 'Verified by Branch Officer') {
                          echo "<label class='badge badge-info'>Verified by Branch Officer</label>";
                        } elseif ($row['status'] == 1 || $row['status'] == 'Verified by Verifier') {
                          echo "<label class='badge badge-primary'>Verified by Verifier</label>";
                        } elseif ($row['status'] == 'Rejected') {
                          echo "<label class='badge badge-danger'>Rejected</label>";
                        } else {
                          echo "<label class='badge badge-warning'>Pending</label>";
                        }
                      ?>
                    </td>
                  </tr>
              <?php
                }
              } else {
                echo "<tr><td colspan='11' class='text-center'>No loan applications found.</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<?php
include_once "inc/footer.php";
?>