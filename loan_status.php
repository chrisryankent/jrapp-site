<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php"; // Include database connection
?>

<div class="card">
  <div class="card-header">
    All Loan Details
  </div>
  <div class="card-body">
    <h5 class="card-title">Loan Details</h5>
    <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th>Name</th>
          <th>NID</th>
          <th>Total Loan</th>
          <th>Installments</th>
          <th>EMI</th>
          <th>Amount Paid</th>
          <th>Remaining Amount</th>
          <th>Total Fine</th>
          <th>Current Installment</th>
          <th>Remaining Installments</th>
          <th>Next Pay Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Fetch all loan details
        $query = "SELECT tbl_loan_application.*, tbl_borrower.name, tbl_borrower.nid 
                  FROM tbl_loan_application 
                  INNER JOIN tbl_borrower ON tbl_loan_application.b_id = tbl_borrower.id";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
          while ($row = mysqli_fetch_assoc($result)) {
        ?>
            <tr>
              <td><?php echo $row['name']; ?></td>
              <td><?php echo $row['nid']; ?></td>
              <td><?php echo $row['total_loan']; ?> tk</td>
              <td><?php echo $row['installments']; ?></td>
              <td><?php echo $row['emi_loan']; ?> tk/month</td>
              <td><?php echo $row['amount_paid']; ?> tk</td>
              <td><?php echo $row['amount_remain']; ?> tk</td>
              <td><?php echo isset($row['fine']) ? $row['fine'] . " tk" : "N/A"; ?></td>
              <td><?php echo $row['current_inst']; ?></td>
              <td><?php echo $row['remain_inst']; ?></td>
              <td><?php echo isset($row['next_date']) ? $row['next_date'] : "N/A"; ?></td>
              <td>
                <div>
                  <a class="btn btn-info" href="individual_loan.php?loan_id=<?php echo $row['id']; ?>&b_id=<?php echo $row['b_id']; ?>">View</a>
                </div>
              </td>
            </tr>
        <?php
          }
        } else {
          echo "<tr><td colspan='12' class='text-center'>No loan details found.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include_once "inc/footer.php";
?>