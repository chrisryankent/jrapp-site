<?php
// filepath: /opt/lampp/htdocs/htdocs/overdue_loans.php
include 'inc/header.php';
include 'inc/sidebar.php';
include 'config/config.php';

// Fetch overdue loans with penalty
$res = $conn->query("
    SELECT l.id, b.id AS borrower_id, b.name, b.nid, b.mobile, b.email, l.total_amount, l.installments, l.current_installment, l.updated_at
    FROM tbl_loan_application l
    JOIN tbl_borrower b ON l.borrower_id = b.id
    WHERE l.penalty_applied = 1
    ORDER BY l.updated_at DESC
");

// Handle send warning actions (simulate sending)
$sendMsg = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_warning_one'])) {
        // Send to one
        $name = htmlspecialchars($_POST['name']);
        $mobile = htmlspecialchars($_POST['mobile']);
        $email = htmlspecialchars($_POST['email']);
        // Here you would integrate your SMS/Email sending logic
        $sendMsg = "<div class='alert alert-info'>Warning sent to $name ($mobile, $email).</div>";
    }
    if (isset($_POST['send_warning_all'])) {
        // Send to all
        // Here you would loop through all overdue and send
        $sendMsg = "<div class='alert alert-info'>Warning sent to all overdue borrowers.</div>";
    }
}
?>
<div class="content-wrapper">
    <div class="container-fluid mt-4">
        <h2 class="mb-4">All Overdue Loans (Penalty Applied)</h2>
        <?php echo $sendMsg; ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>Overdue Loans List</span>
                <form method="post" class="mb-0">
                    <button type="submit" name="send_warning_all" class="btn btn-danger btn-sm"
                        onclick="return confirm('Send warning to ALL overdue borrowers?');">
                        <i class="fas fa-bullhorn"></i> Send Warning to All
                    </button>
                </form>
            </div>
            <div class="card-body">
                <?php if ($res && $res->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>Borrower</th>
                                <th>NID</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Loan ID</th>
                                <th>Current Balance</th>
                                <th>Installments</th>
                                <th>Last Updated</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $res->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['name']); ?></td>
                                <td><?php echo htmlspecialchars($row['nid']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><?php echo $row['id']; ?></td>
                                <td><?php echo number_format($row['total_amount'],2); ?></td>
                                <td><?php echo $row['installments']; ?> (Paid: <?php echo $row['current_installment']; ?>)</td>
                                <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                                <td>
                                    <form method="post" class="mb-0">
                                        <input type="hidden" name="name" value="<?php echo htmlspecialchars($row['name']); ?>">
                                        <input type="hidden" name="mobile" value="<?php echo htmlspecialchars($row['mobile']); ?>">
                                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($row['email']); ?>">
                                        <button type="submit" name="send_warning_one" class="btn btn-warning btn-sm"
                                            onclick="return confirm('Send warning to <?php echo htmlspecialchars($row['name']); ?>?');">
                                            <i class="fas fa-exclamation-triangle"></i> Send Warning
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning">No overdue loans with penalty applied.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include 'inc/footer.php'; ?>