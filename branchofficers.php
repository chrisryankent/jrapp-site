<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php"; // Include database connection

// Handle Create, Update, and Delete operations
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_officer'])) {
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
        $branch = mysqli_real_escape_string($conn, $_POST['branch']);
        $role = 2; // Role 2 for Branch Officer

        $query = "INSERT INTO tbl_user (name, email, mobile, branch, role) VALUES ('$name', '$email', '$mobile', '$branch', '$role')";
        if (mysqli_query($conn, $query)) {
            $message = "<div class='alert alert-success alert-dismissible fade show mt-3' role='alert'>
                            Branch officer added successfully!
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                            Error: " . mysqli_error($conn) . "
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
        }
    }

    if (isset($_POST['update_officer'])) {
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        $name = mysqli_real_escape_string($conn, $_POST['name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $mobile = mysqli_real_escape_string($conn, $_POST['mobile']);
        $branch = mysqli_real_escape_string($conn, $_POST['branch']);

        $query = "UPDATE tbl_user SET name = '$name', email = '$email', mobile = '$mobile', branch = '$branch' WHERE id = '$id' AND role = 2";
        if (mysqli_query($conn, $query)) {
            $message = "<div class='alert alert-success alert-dismissible fade show mt-3' role='alert'>
                            Branch officer updated successfully!
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                            Error: " . mysqli_error($conn) . "
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
        }
    }

    if (isset($_POST['delete_officer'])) {
        $id = mysqli_real_escape_string($conn, $_POST['id']);
        $query = "DELETE FROM tbl_user WHERE id = '$id' AND role = 2";
        if (mysqli_query($conn, $query)) {
            $message = "<div class='alert alert-success alert-dismissible fade show mt-3' role='alert'>
                            Branch officer deleted successfully!
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
        } else {
            $message = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
                            Error: " . mysqli_error($conn) . "
                            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                              <span aria-hidden='true'>&times;</span>
                            </button>
                        </div>";
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <h3 class="page-heading mb-4">Manage Branch Officers</h3>

        <?php if (!empty($message)) { ?>
            <div class="row"><div class="col-md-8 offset-md-2"><?php echo $message; ?></div></div>
        <?php } ?>

        <!-- Add Branch Officer Form -->
        <form method="POST" class="mb-4">
            <h5>Add Branch Officer</h5>
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="mobile">Mobile</label>
                <input type="text" name="mobile" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="branch">Branch</label>
                <input type="text" name="branch" class="form-control" required>
            </div>
            <button type="submit" name="add_officer" class="btn btn-primary">Add Officer</button>
        </form>

        <!-- Branch Officers List -->
        <h5>Branch Officers List</h5>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Branch</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $query = "SELECT * FROM tbl_user WHERE role = 2"; // Fetch only branch officers
                $result = mysqli_query($conn, $query);

                if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) {
                        echo "<tr>";
                        echo "<td>" . $row['id'] . "</td>";
                        echo "<td>" . $row['name'] . "</td>";
                        echo "<td>" . $row['email'] . "</td>";
                        echo "<td>" . $row['mobile'] . "</td>";
                        echo "<td>" . $row['branch'] . "</td>";
                        echo "<td>
                                <form method='POST' style='display:inline-block;'>
                                    <input type='hidden' name='id' value='" . $row['id'] . "'>
                                    <button type='submit' name='delete_officer' class='btn btn-danger btn-sm'>Delete</button>
                                </form>
                                <button class='btn btn-info btn-sm' type='button' onclick='editOfficer(" . json_encode($row) . ")'>Edit</button>
                              </td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='6'>No branch officers found.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Branch Officer Modal -->
<div id="editModal" class="modal" tabindex="-1" role="dialog" style="display:none;">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Branch Officer</h5>
                    <button type="button" class="close" onclick="closeModal()">&times;</button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="form-group">
                        <label for="edit_name">Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_email">Email</label>
                        <input type="email" name="email" id="edit_email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_mobile">Mobile</label>
                        <input type="text" name="mobile" id="edit_mobile" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_branch">Branch</label>
                        <input type="text" name="branch" id="edit_branch" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="update_officer" class="btn btn-primary">Save Changes</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editOfficer(officer) {
        document.getElementById('edit_id').value = officer.id;
        document.getElementById('edit_name').value = officer.name;
        document.getElementById('edit_email').value = officer.email;
        document.getElementById('edit_mobile').value = officer.mobile;
        document.getElementById('edit_branch').value = officer.branch;
        document.getElementById('editModal').style.display = 'block';
    }

    function closeModal() {
        document.getElementById('editModal').style.display = 'none';
    }
    // Enable Bootstrap modal close with fade
    document.addEventListener('DOMContentLoaded', function() {
        var modals = document.querySelectorAll('.modal');
        modals.forEach(function(modal) {
            modal.addEventListener('click', function(e) {
                if (e.target === modal) closeModal();
            });
        });
    });
</script>

<?php
include_once "inc/footer.php";
?>