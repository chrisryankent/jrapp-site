<?php
// filepath: /opt/lampp/htdocs/htdocs/terms_conditions.php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";

// Handle messages
$msg = "";

// Handle Create/Update
if (isset($_POST['save'])) {
    $content = $conn->real_escape_string($_POST['content']);
    if (!empty($_POST['id'])) {
        // Update
        $id = (int)$_POST['id'];
        $conn->query("UPDATE tbl_terms_conditions SET content='$content' WHERE id=$id");
        $msg = "<div class='alert alert-success'>Terms updated successfully.</div>";
    } else {
        // Create
        $conn->query("INSERT INTO tbl_terms_conditions (content) VALUES ('$content')");
        $msg = "<div class='alert alert-success'>Terms added successfully.</div>";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $conn->query("DELETE FROM tbl_terms_conditions WHERE id=$id");
    $msg = "<div class='alert alert-danger'>Terms deleted.</div>";
}

// Handle Edit
$editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$editContent = "";
if ($editId) {
    $res = $conn->query("SELECT * FROM tbl_terms_conditions WHERE id=$editId");
    if ($res && $row = $res->fetch_assoc()) {
        $editContent = $row['content'];
    }
}

// Fetch all terms
$terms = [];
$res = $conn->query("SELECT * FROM tbl_terms_conditions ORDER BY updated_at DESC");
while ($row = $res->fetch_assoc()) {
    $terms[] = $row;
}
?>
<div class="content-wrapper">
    <div class="container-fluid">
        <h2 class="mt-3 mb-4 font-weight-bold">
            Terms & Conditions
            <span class="ml-4" style="font-size:1rem;">
                <span class="badge badge-primary">Total Versions: <?php echo count($terms); ?></span>
            </span>
        </h2>
        <?php echo $msg; ?>

        <!-- Add/Edit Form -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <?php echo $editId ? "Edit Terms" : "Add New Terms"; ?>
            </div>
            <div class="card-body">
                <form method="post">
                    <input type="hidden" name="id" value="<?php echo $editId; ?>">
                    <div class="form-group">
                        <textarea name="content" class="form-control" rows="8" required><?php echo htmlspecialchars($editContent); ?></textarea>
                    </div>
                    <button type="submit" name="save" class="btn btn-success"><?php echo $editId ? "Update" : "Add"; ?></button>
                    <?php if ($editId): ?>
                        <a href="terms_conditions.php" class="btn btn-secondary">Cancel</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <!-- List of Terms -->
        <div class="card">
            <div class="card-header bg-secondary text-white">
                All Terms & Conditions
            </div>
            <div class="card-body">
                <?php if (count($terms)): ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Content</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($terms as $i => $row): ?>
                            <tr>
                                <td><?php echo $i+1; ?></td>
                                <td style="white-space:pre-line;"><?php echo htmlspecialchars(mb_strimwidth($row['content'], 0, 200, '...')); ?></td>
                                <td><?php echo htmlspecialchars($row['updated_at']); ?></td>
                                <td>
                                    <a href="terms_conditions.php?edit=<?php echo $row['id']; ?>" class="btn btn-sm btn-info">Edit</a>
                                    <a href="terms_conditions.php?delete=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this terms entry?');">Delete</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="alert alert-warning">No terms and conditions found.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include_once "inc/footer.php"; ?>