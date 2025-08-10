<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Add interest rate
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_rate'])) {
    $annual_rate_percent = floatval($_POST['annual_rate_percent']);
    $effective_date = mysqli_real_escape_string($conn, $_POST['effective_date']);
    $sql = "INSERT INTO tbl_interest_rate (annual_rate_percent, effective_date, created_at) VALUES ($annual_rate_percent, '$effective_date', NOW())";
    mysqli_query($conn, $sql);
    header("Location: interest_rates.php");
    exit;
}

// Delete interest rate
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM tbl_interest_rate WHERE id = $id");
    header("Location: interest_rates.php");
    exit;
}

// Fetch all interest rates
$result = mysqli_query($conn, "SELECT * FROM tbl_interest_rate ORDER BY effective_date DESC, id DESC");
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card shadow">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Interest Rates</h4>
                    <button class="btn btn-light btn-sm" data-toggle="collapse" data-target="#addRateForm">
                        <i class="fas fa-plus"></i> Add Rate
                    </button>
                </div>
                <div class="collapse" id="addRateForm">
                    <div class="card-body border-bottom">
                        <form method="POST" class="row align-items-end">
                            <div class="form-group col-md-5">
                                <label>Annual Rate (%)</label>
                                <input type="number" step="0.01" name="annual_rate_percent" class="form-control" required>
                            </div>
                            <div class="form-group col-md-5">
                                <label>Effective Date</label>
                                <input type="date" name="effective_date" class="form-control" required>
                            </div>
                            <div class="form-group col-md-2">
                                <button type="submit" name="add_rate" class="btn btn-success btn-block">Add</button>
                            </div>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>#</th>
                                    <th>Annual Rate (%)</th>
                                    <th>Effective Date</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                                    <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['annual_rate_percent']); ?></td>
                                        <td><?php echo htmlspecialchars($row['effective_date']); ?></td>
                                        <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                                        <td>
                                            <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
                                               onclick="return confirm('Delete this rate?');">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">No interest rates found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include_once "inc/footer.php";
?>