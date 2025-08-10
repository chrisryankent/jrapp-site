<?php
// expense.php
session_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 0) Branch scoping
if (empty($_SESSION['branch_id'])) {
    echo "<div class='content-wrapper'><div class='container-fluid'><div class='row'><div class='col-md-8 offset-md-2'><div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>Access denied: no branch assigned.<button type='button' class='close' data-dismiss='alert' aria-label='Close'><span aria-hidden='true'>&times;</span></button></div></div></div></div></div>";
    include_once "inc/footer.php";
    exit;
}
$branch_id = (int)$_SESSION['branch_id'];

// --- STEP 1: Handle New Expense Submission ---
$expense_message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['expense_submit'])) {
    $description    = mysqli_real_escape_string($conn, $_POST['description']);
    $expense_amount = floatval($_POST['expense_amount']);

    // Insert into tbl_expenses for this branch
    $insert_sql = "
        INSERT INTO tbl_expenses 
            (branch_id, description, expense_amount) 
        VALUES 
            ($branch_id, '$description', $expense_amount)
    ";
    if (mysqli_query($conn, $insert_sql)) {
        $expense_message = "<div class='alert alert-success alert-dismissible fade show mt-3' role='alert'>
                                Expense recorded successfully.
                                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                  <span aria-hidden='true'>&times;</span>
                                </button>
                            </div>";
    } else {
        $expense_message = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                                Error recording expense: " . mysqli_error($conn) . "
                                <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                  <span aria-hidden='true'>&times;</span>
                                </button>
                            </div>";
    }

    // --- STEP 2: Recalculate money_used for this branch and update tbl_capital ---
    // Sum approved loans for this branch
    $loan_q = "
        SELECT IFNULL(SUM(total_amount),0) AS loan_sum
          FROM tbl_loan_application
         WHERE status='Approved'
           AND branch_id = $branch_id
    ";
    $r1 = mysqli_query($conn, $loan_q);
    $loan_sum = ($r1 && $row = mysqli_fetch_assoc($r1)) ? $row['loan_sum'] : 0;

    // Sum expenses for this branch
    $exp_q = "
        SELECT IFNULL(SUM(expense_amount),0) AS exp_sum
          FROM tbl_expenses
         WHERE branch_id = $branch_id
    ";
    $r2 = mysqli_query($conn, $exp_q);
    $exp_sum = ($r2 && $row2 = mysqli_fetch_assoc($r2)) ? $row2['exp_sum'] : 0;

    $money_used = $loan_sum + $exp_sum;

    // Update the branch's capital record
    mysqli_query($conn, "
        UPDATE tbl_capital
           SET amount_used = $money_used
         WHERE branch_id = $branch_id
    ");
}

// --- STEP 3: Fetch Expense History for this Branch ---
$history_sql = "
    SELECT id, description, expense_amount, created_at
      FROM tbl_expenses
     WHERE branch_id = $branch_id
     ORDER BY created_at DESC
";
$result_expenses = mysqli_query($conn, $history_sql);
?>

<div class="content-wrapper">
    <div class="container-fluid mt-4">
        <h2>Expense Management (Branch #<?php echo $branch_id; ?>)</h2>
        
        <?php 
        if (!empty($expense_message)) {
            echo "<div class='row'><div class='col-md-8 offset-md-2'>{$expense_message}</div></div>";
        }
        ?>

        <!-- New Expense Entry Form -->
        <div class="card mb-4">
            <div class="card-header bg-secondary text-white">Add New Expense</div>
            <div class="card-body">
                <form method="POST">
                    <div class="form-group">
                        <label for="description">Description:</label>
                        <input type="text"
                               name="description"
                               id="description"
                               class="form-control"
                               placeholder="Enter expense description"
                               required>
                    </div>
                    <div class="form-group">
                        <label for="expense_amount">Expense Amount (UGX):</label>
                        <input type="number"
                               name="expense_amount"
                               id="expense_amount"
                               step="0.01"
                               class="form-control"
                               required>
                    </div>
                    <button type="submit"
                            name="expense_submit"
                            class="btn btn-primary">
                        Record Expense
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Expense History Table -->
        <div class="card">
            <div class="card-header bg-dark text-white">Expense History</div>
            <div class="card-body">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Description</th>
                            <th>Amount (UGX)</th>
                            <th>Recorded At</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result_expenses && mysqli_num_rows($result_expenses) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($result_expenses)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo number_format($row['expense_amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($row['created_at']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="text-center">
                                No expense records found for this branch.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once "inc/footer.php"; ?>
