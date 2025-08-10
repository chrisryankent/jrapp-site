<?php
include 'inc/header.php';
include 'inc/sidebar.php';
include 'config/config.php';

// --- Penalty Logic: Apply 20% penalty ONCE for overdue loans ---
$overdueLoans = $conn->query("
    SELECT l.id, l.total_amount, l.installments, l.current_installment
    FROM tbl_loan_application l
    WHERE l.updated_at <= DATE_SUB(NOW(), INTERVAL 3 MONTH)
      AND l.status = 'Disbursed'
      AND (l.penalty_applied IS NULL OR l.penalty_applied = 0)
");
while ($loan = $overdueLoans->fetch_assoc()) {
    $loanId = (int)$loan['id'];
    $oldTotal = (float)$loan['total_amount'];
    $oldInstallments = (int)$loan['installments'];
    $currentInstallment = (int)$loan['current_installment'];
    $penalty = $oldTotal * 0.20;
    $newTotal = $oldTotal + $penalty;

    // Calculate remaining installments (if any paid)
    $remainingInstallments = $oldInstallments - $currentInstallment;
    $newInstallments = $remainingInstallments > 0 ? ceil($newTotal / ($oldTotal / $oldInstallments)) : $oldInstallments;

    // Update loan: new total, new installments, mark penalty applied
    $conn->query("
        UPDATE tbl_loan_application
        SET total_amount = $newTotal,
            installments = $newInstallments,
            penalty_applied = 1
        WHERE id = $loanId
    ");
}

// Dashboard stats
$sql = "SELECT COUNT(*) AS count FROM tbl_loan_application WHERE status = 'Pending'";
$result = $conn->query($sql);
$pendingApplications = ($row = $result->fetch_assoc()) ? $row['count'] : 0;

$sql = "SELECT COUNT(*) AS count FROM tbl_borrower";
$result = $conn->query($sql);
$totalBorrowers = ($row = $result->fetch_assoc()) ? $row['count'] : 0;

$sql = "SELECT COUNT(*) AS count FROM tbl_loan_application WHERE status = 'Approved'";
$result = $conn->query($sql);
$activeLoans = ($row = $result->fetch_assoc()) ? $row['count'] : 0;

$sql = "SELECT COALESCE(SUM(amount), 0) AS total_paid FROM tbl_payment";
$result = $conn->query($sql);
$totalPaidMoney = ($row = $result->fetch_assoc()) ? $row['total_paid'] : 0;

$sql = "SELECT COUNT(DISTINCT borrower_id) AS count FROM tbl_kyc_record WHERE status = 'Verified'";
$result = $conn->query($sql);
$kycCount = ($row = $result->fetch_assoc()) ? $row['count'] : 0;

$sql = "SELECT COUNT(*) AS count FROM tbl_borrower_location WHERE location_time >= NOW() - INTERVAL 1 DAY";
$result = $conn->query($sql);
$locationReports = ($row = $result->fetch_assoc()) ? $row['count'] : 0;

$sql = "SELECT COUNT(*) AS count FROM tbl_geofence_alert WHERE alert_time >= NOW() - INTERVAL 7 DAY";
$result = $conn->query($sql);
$geofenceAlerts = ($row = $result->fetch_assoc()) ? $row['count'] : 0;

$sql = "SELECT AVG(rating_score) AS avg_risk FROM tbl_risk_rating";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$avgRisk = (isset($row['avg_risk']) && $row['avg_risk'] !== null) ? number_format($row['avg_risk'], 2) : '0.00';

// Recent borrowers (last 5)
$recentBorrowers = $conn->query("SELECT name, nid, created_at FROM tbl_borrower ORDER BY created_at DESC LIMIT 5");
// Recent loans (last 5)
$recentLoans = $conn->query("SELECT l.id, b.name, l.total_amount, l.status, l.created_at FROM tbl_loan_application l JOIN tbl_borrower b ON l.borrower_id = b.id ORDER BY l.created_at DESC LIMIT 5");

// --- Capital & Expense Stats ---
$sql = "SELECT COALESCE(SUM(amount),0) AS total_capital FROM tbl_capital";
$result = $conn->query($sql);
$totalCapital = ($row = $result->fetch_assoc()) ? $row['total_capital'] : 0;

$sql = "SELECT COALESCE(SUM(total_amount),0) AS total_loans FROM tbl_loan_application WHERE status IN ('Approved','Disbursed','Closed')";
$result = $conn->query($sql);
$totalLoans = ($row = $result->fetch_assoc()) ? $row['total_loans'] : 0;

$sql = "SELECT COALESCE(SUM(expense_amount),0) AS total_expenses FROM tbl_expenses";
$result = $conn->query($sql);
$totalExpenses = ($row = $result->fetch_assoc()) ? $row['total_expenses'] : 0;

$amountRemaining = $totalCapital - ($totalLoans + $totalExpenses);
?>
<div class="content-wrapper">
    <div class="container-fluid">
        <h2 class="mt-3 mb-4 font-weight-bold">
            Dashboard
            <span class="ml-4" style="font-size:1rem;">
                <span class="badge badge-primary">Total Capital: <?php echo number_format($totalCapital,2); ?></span>
                <span class="badge badge-success">Spent on Loans: <?php echo number_format($totalLoans,2); ?></span>
                <span class="badge badge-warning">Expenses: <?php echo number_format($totalExpenses,2); ?></span>
                <span class="badge badge-info">Amount Remaining: <?php echo number_format($amountRemaining,2); ?></span>
            </span>
        </h2>
        <div class="row">
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-primary h-100 dashboard-card" onclick="location.href='loanverify.php'" style="cursor:pointer;">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-file-alt fa-2x mr-3"></i>
                        <div>
                            <h6 class="card-title mb-1">Pending Loan Applications</h6>
                            <h3><?php echo $pendingApplications; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-success h-100 dashboard-card" onclick="location.href='viewborrower.php'" style="cursor:pointer;">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-users fa-2x mr-3"></i>
                        <div>
                            <h6 class="card-title mb-1">Total Borrowers</h6>
                            <h3><?php echo $totalBorrowers; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-info h-100 dashboard-card" onclick="location.href='loan_application.php'" style="cursor:pointer;">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-hand-holding-usd fa-2x mr-3"></i>
                        <div>
                            <h6 class="card-title mb-1">Active Loans</h6>
                            <h3><?php echo $activeLoans; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-warning h-100 dashboard-card" onclick="location.href='payloan.php'" style="cursor:pointer;">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-dollar-sign fa-2x mr-3"></i>
                        <div>
                            <h6 class="card-title mb-1">Total Paid Money</h6>
                            <h3><?php echo number_format($totalPaidMoney, 2); ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-danger h-100 dashboard-card" onclick="location.href='viewborrower.php'" style="cursor:pointer;">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-id-card fa-2x mr-3"></i>
                        <div>
                            <h6 class="card-title mb-1">KYC Verified Borrowers</h6>
                            <h3><?php echo $kycCount; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-secondary h-100 dashboard-card" onclick="location.href='locate_borrower.php'" style="cursor:pointer;">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-map-marker-alt fa-2x mr-3"></i>
                        <div>
                            <h6 class="card-title mb-1">Location Reports (24h)</h6>
                            <h3><?php echo $locationReports; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-dark h-100 dashboard-card" onclick="location.href='reports.php'" style="cursor:pointer;">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-exclamation-triangle fa-2x mr-3"></i>
                        <div>
                            <h6 class="card-title mb-1">Geofence Alerts (7d)</h6>
                            <h3><?php echo $geofenceAlerts; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-4">
                <div class="card text-white bg-primary h-100 dashboard-card" onclick="location.href='risk_ratings.php'" style="cursor:pointer;">
                    <div class="card-body d-flex align-items-center">
                        <i class="fas fa-chart-line fa-2x mr-3"></i>
                        <div>
                            <h6 class="card-title mb-1">Avg. Risk Rating</h6>
                            <h3><?php echo $avgRisk; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Features -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <i class="fas fa-user-clock"></i> Recent Borrowers
                    </div>
                    <div class="card-body">
                        <?php if ($recentBorrowers && $recentBorrowers->num_rows > 0): ?>
                            <ul class="list-group">
                                <?php while($b = $recentBorrowers->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-user mr-2"></i>
                                            <?php echo htmlspecialchars($b['name']); ?> (<?php echo htmlspecialchars($b['nid']); ?>)
                                        </span>
                                        <span class="badge badge-primary badge-pill"><?php echo date("M d, H:i", strtotime($b['created_at'])); ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <span class="text-muted">No recent borrowers.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <i class="fas fa-file-invoice-dollar"></i> Recent Loans
                    </div>
                    <div class="card-body">
                        <?php if ($recentLoans && $recentLoans->num_rows > 0): ?>
                            <ul class="list-group">
                                <?php while($l = $recentLoans->fetch_assoc()): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-money-check-alt mr-2"></i>
                                            <?php echo htmlspecialchars($l['name']); ?> - <?php echo number_format($l['total_amount'],2); ?> (<?php echo htmlspecialchars($l['status']); ?>)
                                        </span>
                                        <span class="badge badge-success badge-pill"><?php echo date("M d, H:i", strtotime($l['created_at'])); ?></span>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        <?php else: ?>
                            <span class="text-muted">No recent loans.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Loans with Penalty Section -->
        <div class="card my-4">
            <div class="card-header bg-danger text-white d-flex justify-content-between align-items-center">
                <span><i class="fas fa-exclamation-circle"></i> Overdue Loans (Penalty Applied)</span>
                <a href="overdue_loans.php" class="btn btn-light btn-sm">View All Overdue Loans</a>
            </div>
            <div class="card-body">
                <?php
                $penalizedLoans = $conn->query("
                    SELECT l.id, b.name, b.nid, b.mobile, l.total_amount, l.installments, l.current_installment
                    FROM tbl_loan_application l
                    JOIN tbl_borrower b ON l.borrower_id = b.id
                    WHERE l.penalty_applied = 1
                    ORDER BY l.updated_at DESC
                    LIMIT 5
                ");
                if ($penalizedLoans && $penalizedLoans->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead>
                                <tr>
                                    <th>Borrower</th>
                                    <th>NID</th>
                                    <th>Mobile</th>
                                    <th>Loan ID</th>
                                    <th>Current Balance</th>
                                    <th>Installments</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $penalizedLoans->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['nid']); ?></td>
                                    <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo number_format($row['total_amount'],2); ?></td>
                                    <td><?php echo $row['installments']; ?> (Paid: <?php echo $row['current_installment']; ?>)</td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <span class="text-muted">No overdue loans with penalty applied.</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
include 'inc/footer.php';
?>