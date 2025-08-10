<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php"; // Include database connection

// Optional: Show alert if redirected with a message (e.g., after verification)
$alert = "";
if (isset($_GET['msg']) && $_GET['msg'] != "") {
    $type = isset($_GET['type']) && $_GET['type'] === "success" ? "success" : "danger";
    $alert = "<div class='alert alert-{$type} alert-dismissible fade show mt-3' role='alert'>"
           . htmlspecialchars($_GET['msg']) .
           "<button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
            </div>";
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <h3 class="page-heading mb-4">Unverified Loan Applications</h3>
        <?php if ($alert) echo "<div class='row'><div class='col-md-8 offset-md-2'>{$alert}</div></div>"; ?>

        <div class="card">
            <div class="card-header bg-warning text-dark">
                Unverified Loan Applications
            </div>
            <div class="card-body">
                <h5 class="card-title">Loan Details</h5>
                <div class="table-responsive">
                    <table id="unverifiedLoans" class="table table-striped table-bordered table-hover" cellspacing="0" width="100%">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>NID</th>
                                <th>Mobile</th>
                                <th>Expected Loan</th>
                                <th>Interest Rate (%)</th>
                                <th>Installments</th>
                                <th>Total Loan</th>
                                <th>EMI</th>
                                <th>Documents</th>
                                <th>Verification</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Fetch only loans with status "Pending" (i.e. not yet fully verified by all three)
                            $query = "SELECT la.*, b.name, b.nid, b.mobile, ir.annual_rate_percent 
                                        FROM tbl_loan_application la 
                                        INNER JOIN tbl_borrower b ON la.borrower_id = b.id
                                        INNER JOIN tbl_interest_rate ir ON la.interest_rate_id = ir.id
                                        WHERE la.status = 'Pending'";
                            $result = mysqli_query($conn, $query);

                            if ($result && mysqli_num_rows($result) > 0) {
                                while ($row = mysqli_fetch_assoc($result)) {
                            ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                                        <td><?php echo htmlspecialchars($row['nid']); ?></td>
                                        <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                        <td><?php echo htmlspecialchars($row['expected_amount']); ?> tk</td>
                                        <td><?php echo htmlspecialchars($row['annual_rate_percent']); ?>%</td>
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
                                            <a href="individual_verify.php?loan_id=<?php echo urlencode($row['id']); ?>" class="btn btn-outline-success btn-sm">
                                                Verify Loan
                                            </a>
                                        </td>
                                    </tr>
                            <?php
                                }
                            } else {
                                echo "<tr><td colspan='10' class='text-center'>No unverified loan applications found.</td></tr>";
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
