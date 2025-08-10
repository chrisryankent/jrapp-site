<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php"; // Include database connection
?>

<div class="card">
  <div class="card-header">
    Liability Management
  </div>
  <div class="card-body">
    <h5 class="card-title">Property Sell and Due Loan Management</h5>
    <table id="example" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
      <thead>
        <tr>
          <th>Name</th>
          <th>NID</th>
          <th>Property Name</th>
          <th>Property Details</th>
          <th>Sell Price</th>
          <th>Paid Due</th>
          <th>Return Money</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Fetch all property selling and loan management details
        $query = "SELECT tbl_liability.*, tbl_borrower.name, tbl_borrower.nid 
                  FROM tbl_liability
                  INNER JOIN tbl_borrower ON tbl_liability.b_id = tbl_borrower.id";
        $result = mysqli_query($conn, $query);

        if ($result && mysqli_num_rows($result) > 0) {
          while ($row = mysqli_fetch_assoc($result)) {
        ?>
            <tr>
              <td><?php echo $row['name']; ?></td>
              <td><?php echo $row['nid']; ?></td>
              <td><?php echo $row['property_name']; ?></td>
              <td><?php echo $row['property_details']; ?></td>
              <td><?php echo $row['price']; ?> tk</td>
              <td><?php echo $row['pay_remaining_loan']; ?> tk</td>
              <td>
                <?php
                // Calculate return money if the selling price exceeds the remaining loan payment
                $return_money = $row['price'] - $row['pay_remaining_loan'];
                echo $return_money > 0 ? $return_money . " tk" : "0 tk";
                ?>
              </td>
            </tr>
        <?php
          }
        } else {
          echo "<tr><td colspan='7' class='text-center'>No property selling details found.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>

<?php
include_once "inc/footer.php";
?>