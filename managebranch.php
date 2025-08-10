<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

//-------------------------
// Process Branch Deletion
//-------------------------
if (isset($_GET['delete_branch'])) {
    $branch_id = (int) $_GET['delete_branch'];
    $delete_query = "DELETE FROM tbl_branch WHERE id = '$branch_id'";
    if (mysqli_query($conn, $delete_query)) {
        $message = "Branch deleted successfully.";
    } else {
        $message = "Error deleting branch: " . mysqli_error($conn);
    }
    echo "<meta http-equiv='refresh' content='0;url=manage_branches.php'>";
}

//-------------------------
// Process Branch Update
//-------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_branch_submit'])) {
    $branch_id = mysqli_real_escape_string($conn, $_POST['branch_id']);
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $manager_user_id = mysqli_real_escape_string($conn, $_POST['manager_user_id']);
    
    $update_query = "UPDATE tbl_branch 
                     SET name = '$name', address = '$address', manager_user_id = ";
    if(empty($manager_user_id)) {
        $update_query .= "NULL";
    } else {
        $update_query .= "'$manager_user_id'";
    }
    $update_query .= " WHERE id = '$branch_id'";
    
    if (mysqli_query($conn, $update_query)) {
        $message = "Branch updated successfully.";
    } else {
        $message = "Error updating branch: " . mysqli_error($conn);
    }
    echo "<meta http-equiv='refresh' content='0;url=manage_branches.php'>";
}

//-------------------------
// Process New Branch Addition
//-------------------------
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_add_branch'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $manager_user_id = mysqli_real_escape_string($conn, $_POST['manager_user_id']);
    
    $insert_query = "INSERT INTO tbl_branch (name, address, manager_user_id) VALUES ('$name', '$address', ";
    if (empty($manager_user_id)) {
        $insert_query .= "NULL";
    } else {
        $insert_query .= "'$manager_user_id'";
    }
    $insert_query .= ")";
    
    if (mysqli_query($conn, $insert_query)) {
        $message = "Branch added successfully.";
    } else {
        $message = "Error adding branch: " . mysqli_error($conn);
    }
    echo "<meta http-equiv='refresh' content='0;url=manage_branches.php'>";
}

//-------------------------
// If Editing a Branch, Fetch Its Details
//-------------------------
$editBranch = null;
if (isset($_GET['edit_branch'])) {
    $edit_branch_id = (int) $_GET['edit_branch'];
    $queryEdit = "SELECT * FROM tbl_branch WHERE id = '$edit_branch_id' LIMIT 1";
    $resultEdit = mysqli_query($conn, $queryEdit);
    if ($resultEdit && mysqli_num_rows($resultEdit) > 0) {
        $editBranch = mysqli_fetch_assoc($resultEdit);
    }
}

//-------------------------
// Retrieve List of Branches
//-------------------------
$branches = [];
$branch_query = "SELECT b.*, u.name AS manager_name 
                 FROM tbl_branch b
                 LEFT JOIN tbl_user u ON b.manager_user_id = u.id
                 ORDER BY b.created_at DESC";
$resultBranches = mysqli_query($conn, $branch_query);
if ($resultBranches) {
    while ($row = mysqli_fetch_assoc($resultBranches)) {
        $branches[] = $row;
    }
}

//-------------------------
// Retrieve Users for Manager Selection
//-------------------------
$managers = [];
$manager_query = "SELECT id, name FROM tbl_user ORDER BY name ASC";
$resultManagers = mysqli_query($conn, $manager_query);
if ($resultManagers) {
    while ($row = mysqli_fetch_assoc($resultManagers)) {
        $managers[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Branches</title>
    <!-- Ensure you include your CSS and Bootstrap files -->
</head>
<body>
<div class="container mt-4">
    <h2>Manage Branches</h2>
    
    <?php 
    if (isset($message)) { 
        echo "<div class='alert alert-info'>$message</div>"; 
    } 
    ?>
    
    <!-- Edit Branch Form (if applicable) -->
    <?php if ($editBranch): ?>
    <div class="card mb-4">
        <div class="card-header bg-warning text-white">Edit Branch</div>
        <div class="card-body">
            <form action="" method="post">
                <input type="hidden" name="branch_id" value="<?php echo $editBranch['id']; ?>">
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Branch Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editBranch['name']); ?>" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Address</label>
                    <div class="col-sm-9">
                        <input type="text" name="address" class="form-control" value="<?php echo htmlspecialchars($editBranch['address']); ?>" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Manager</label>
                    <div class="col-sm-9">
                        <select name="manager_user_id" class="form-control">
                            <option value="">-- Select Manager --</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo $manager['id']; ?>" <?php if ($manager['id'] == $editBranch['manager_user_id']) echo "selected"; ?>>
                                    <?php echo htmlspecialchars($manager['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-9 offset-sm-3">
                        <input type="submit" name="update_branch_submit" class="btn btn-primary" value="Update Branch">
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Add New Branch Form -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">Add New Branch</div>
        <div class="card-body">
            <form action="" method="post">
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Branch Name</label>
                    <div class="col-sm-9">
                        <input type="text" name="name" class="form-control" placeholder="Enter branch name" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Address</label>
                    <div class="col-sm-9">
                        <input type="text" name="address" class="form-control" placeholder="Enter branch address" required>
                    </div>
                </div>
                <div class="form-group row">
                    <label class="col-sm-3 col-form-label">Manager</label>
                    <div class="col-sm-9">
                        <select name="manager_user_id" class="form-control">
                            <option value="">-- Select Manager --</option>
                            <?php foreach ($managers as $manager): ?>
                                <option value="<?php echo $manager['id']; ?>"><?php echo htmlspecialchars($manager['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-sm-9 offset-sm-3">
                        <input type="submit" name="submit_add_branch" class="btn btn-success" value="Add Branch">
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- List of Branches -->
    <div class="card">
        <div class="card-header bg-secondary text-white">Branches List</div>
        <div class="card-body">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Manager</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($branches) > 0): ?>
                        <?php foreach ($branches as $branch): ?>
                            <tr>
                                <td><?php echo $branch['id']; ?></td>
                                <td><?php echo htmlspecialchars($branch['name']); ?></td>
                                <td><?php echo htmlspecialchars($branch['address']); ?></td>
                                <td><?php echo $branch['manager_name'] ? htmlspecialchars($branch['manager_name']) : 'None'; ?></td>
                                <td><?php echo htmlspecialchars($branch['created_at']); ?></td>
                                <td>
                                    <a href="managebranch.php?edit_branch=<?php echo $branch['id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                                    <a href="managebranch.php?delete_branch=<?php echo $branch['id']; ?>" onclick="return confirm('Are you sure you want to delete this branch?');" class="btn btn-danger btn-sm">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center">No branches found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
</div>

<?php
include_once "inc/footer.php";
?>
