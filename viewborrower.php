<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php"; // DB connection

// 1) Handle Edit form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_borrower_submit'])) {
    $id     = (int)$_POST['borrower_id'];
    $name   = mysqli_real_escape_string($conn, trim($_POST['name']));
    $nid    = mysqli_real_escape_string($conn, trim($_POST['nid']));
    $mobile = mysqli_real_escape_string($conn, trim($_POST['mobile']));
    $email  = mysqli_real_escape_string($conn, trim($_POST['email']));
    $password = isset($_POST['password']) ? trim($_POST['password']) : '';

    $upd = "
      UPDATE tbl_borrower
         SET name   = '$name',
             nid    = '$nid',
             mobile = '$mobile',
             email  = '$email'
    ";

    // Only update password if a new one is entered
    if ($password !== '') {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $upd .= ", password = '$hashed'";
    }

    $upd .= " WHERE id = $id";
    mysqli_query($conn, $upd);
    // redirect to avoid resubmit & clear edit mode
    header("Location: viewborrower.php");
    exit;
}

// 2) If “?edit_id=...” present, fetch that borrower
$editData = null;
if (isset($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    $r = mysqli_query($conn, "SELECT * FROM tbl_borrower WHERE id = $eid LIMIT 1");
    if ($r && mysqli_num_rows($r)) {
        $editData = mysqli_fetch_assoc($r);
    }
}

// 3) Fetch all borrowers for listing
$listQ = "SELECT * FROM tbl_borrower ORDER BY id DESC";
$result = mysqli_query($conn, $listQ);
$totalBorrowers = $result ? mysqli_num_rows($result) : 0;
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <h2 class="mt-3 mb-4 font-weight-bold">Borrowers List (Total: <?php echo $totalBorrowers; ?>)</h2>
        <div class="row mb-3">
            <div class="col-md-6">
                <a href="addborrower.php" class="btn btn-success mb-2">
                    <i class="fas fa-plus"></i> Add Borrower
                </a>
                <a href="today_borrowers.php" class="btn btn-info mb-2">
                    <i class="fas fa-calendar-day"></i> Borrowers Joined Today
                </a>
            </div>
            <div class="col-md-6">
                <input type="text" id="customFilter" class="form-control" placeholder="Filter borrowers...">
            </div>
        </div>

        <!-- EDIT FORM -->
        <?php if ($editData): ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card mb-4 border-warning">
                    <div class="card-header bg-warning text-white">Edit Borrower #<?php echo $editData['id']; ?></div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="borrower_id" value="<?php echo $editData['id']; ?>">
                            <div class="form-row">
                                <div class="form-group col-md-3">
                                    <label>Name</label>
                                    <input type="text" name="name" class="form-control" 
                                           value="<?php echo htmlspecialchars($editData['name']); ?>" required>
                                </div>
                                <div class="form-group col-md-2">
                                    <label>National ID</label>
                                    <input type="text" name="nid" class="form-control" 
                                           value="<?php echo htmlspecialchars($editData['nid']); ?>" required>
                                </div>
                                <div class="form-group col-md-2">
                                    <label>Mobile</label>
                                    <input type="text" name="mobile" class="form-control" 
                                           value="<?php echo htmlspecialchars($editData['mobile']); ?>" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label>Email</label>
                                    <input type="email" name="email" class="form-control" 
                                           value="<?php echo htmlspecialchars($editData['email']); ?>" required>
                                </div>
                                <div class="form-group col-md-2">
                                    <label>New Password</label>
                                    <input type="password" name="password" class="form-control" placeholder="Leave blank to keep unchanged">
                                </div>
                                <div class="form-group col-md-2 align-self-end">
                                    <button type="submit" name="update_borrower_submit" 
                                            class="btn btn-success btn-block">Update</button>
                                    <a href="viewborrower.php" class="btn btn-secondary btn-block mt-2">
                                        Cancel
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- TABLE -->
        <div class="row">
            <div class="col-12">
                <?php if ($result && mysqli_num_rows($result) > 0): ?>
                <div class="table-responsive">
                    <table id="borrowersTable" class="table table-bordered table-striped">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>National ID</th>
                                <th>Mobile</th>
                                <th>Email</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php while ($row = mysqli_fetch_assoc($result)): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td>
                                    <a href="blank_page.php?borrower_id=<?php echo $row['id']; ?>">
                                        <?php echo htmlspecialchars($row['name']); ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($row['nid']); ?></td>
                                <td><?php echo htmlspecialchars($row['mobile']); ?></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td>
                                    <a href="?edit_id=<?php echo $row['id']; ?>" 
                                       class="btn btn-warning btn-sm" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="blank_page.php?borrower_id=<?php echo $row['id']; ?>" 
                                       class="btn btn-primary btn-sm" title="View">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="alert alert-danger">No borrowers found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function() {
    var filterInput = document.getElementById("customFilter");
    filterInput.addEventListener("input", function(e) {
        var filter = e.target.value.toLowerCase();
        var rows   = document.querySelectorAll("#borrowersTable tbody tr");

        rows.forEach(function(row) {
            row.style.display = row.textContent.toLowerCase().includes(filter) 
                                ? "" 
                                : "none";
        });
    });
});
</script>

<?php
include_once "inc/footer.php";
?>