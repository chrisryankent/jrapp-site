<?php
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php"; 
include_once "helpers/send_email.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);// Database connection

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Begin a transaction so both borrower and KYC inserts occur together
    mysqli_begin_transaction($conn);

    // Borrower Personal Details
    $borrower_name = mysqli_real_escape_string($conn, $_POST['borrower_name']);
    $borrower_nid = mysqli_real_escape_string($conn, $_POST['borrower_nid']);
    $borrower_gender = mysqli_real_escape_string($conn, $_POST['borrower_gender']);
    $borrower_mobile = mysqli_real_escape_string($conn, $_POST['borrower_mobile']);
    $borrower_email = mysqli_real_escape_string($conn, $_POST['borrower_email']);
    $borrower_dob = mysqli_real_escape_string($conn, $_POST['borrower_dob']);
    $borrower_address = mysqli_real_escape_string($conn, $_POST['borrower_address']);
    $borrower_working_status = mysqli_real_escape_string($conn, $_POST['borrower_working_status']);
    $borrower_password = mysqli_real_escape_string($conn, $_POST['borrower_password']);
    $hash_password = password_hash($borrower_password, PASSWORD_DEFAULT);

    // Check for duplicate NID, mobile, or email
    $check_query = "SELECT * FROM tbl_borrower WHERE nid = '$borrower_nid' OR mobile = '$borrower_mobile' OR email = '$borrower_email'";
    $check_result = mysqli_query($conn, $check_query);
    
    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $msg = "Error: A borrower with the same NID, mobile number, or email already exists.";
        mysqli_rollback($conn);
    } else {
        // ---------------------------
        // Upload Borrower Photo
        // ---------------------------
        $photo_dir = "admin/uploads/";
        if (!file_exists($photo_dir)) {
            mkdir($photo_dir, 0777, true);
        }
        $photo = $_FILES['photo']['name'];
        $photo_target = $photo_dir . basename($photo);
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_target)) {
            $msg = "Error: Failed to upload borrower photo.";
            mysqli_rollback($conn);
            exit;
        }
      
        // Insert borrower record into tbl_borrower
        $insert_borrower = "INSERT INTO tbl_borrower 
            (name, nid, gender, mobile, email, dob, address, working_status, selfie_path, password_hash) 
            VALUES 
            ('$borrower_name', '$borrower_nid', '$borrower_gender', '$borrower_mobile', '$borrower_email', 
            '$borrower_dob', '$borrower_address', '$borrower_working_status', '$photo_target', '$hash_password')";
      
        if (mysqli_query($conn, $insert_borrower)) {
            $borrower_id = mysqli_insert_id($conn);
            
            // ---------------------------
            // Upload KYC Documents
            // ---------------------------
            // Front Image Upload
            $front_dir = "admin/uploads/kyc/front/";
            if (!file_exists($front_dir)) {
                mkdir($front_dir, 0777, true);
            }
            $front_image = $_FILES['front_image']['name'];
            $front_target = $front_dir . basename($front_image);
            if (!move_uploaded_file($_FILES['front_image']['tmp_name'], $front_target)) {
                $msg = "Error: Failed to upload front image.";
                mysqli_rollback($conn);
                exit;
            }

            // Back Image Upload
            $back_dir = "admin/uploads/kyc/back/";
            if (!file_exists($back_dir)) {
                mkdir($back_dir, 0777, true);
            }
            $back_image = $_FILES['back_image']['name'];
            $back_target = $back_dir . basename($back_image);
            if (!move_uploaded_file($_FILES['back_image']['tmp_name'], $back_target)) {
                $msg = "Error: Failed to upload back image.";
                mysqli_rollback($conn);
                exit;
            }

            // Barcode/Scanned Data from the National ID (expect JSON format)
            $scanned_data = mysqli_real_escape_string($conn, $_POST['scanned_data']);
            
            // Insert KYC record into tbl_kyc_record
            $insert_kyc = "INSERT INTO tbl_kyc_record 
                (borrower_id, scanned_data, front_image_path, back_image_path, selfie_path) 
                VALUES 
                ('$borrower_id', '$scanned_data', '$front_target', '$back_target', '$photo_target')";
            
            if (mysqli_query($conn, $insert_kyc)) {
                mysqli_commit($conn);
                $msg = "Borrower and KYC documents added successfully!";
                // Send account creation email
                sendAccountCreatedEmail($borrower_email, $borrower_name, $borrower_password);
            } else {
                $msg = "Error inserting KYC record: " . mysqli_error($conn);
                mysqli_rollback($conn);
            }
        } else {
            $msg = "Error inserting borrower: " . mysqli_error($conn);
            mysqli_rollback($conn);
        }
    }
}
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <h3 class="mt-3 mb-4 font-weight-bold">Add Borrower &amp; KYC Details</h3>
        <?php if (isset($msg)) { ?>
        <div class="alert alert-<?php echo (strpos($msg, 'Error') === false ? 'success' : 'danger'); ?> alert-dismissible">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <?php echo $msg; ?>
        </div>
        <?php } ?>
        <div class="row mb-3">
            <div class="col-md-6">
                <a href="viewborrower.php" class="btn btn-info mb-2">
                    <i class="fas fa-users"></i> All Borrowers
                </a>
                <a href="today_borrowers.php" class="btn btn-warning mb-2">
                    <i class="fas fa-calendar-day"></i> Borrowers Joined Today
                </a>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <form action="" method="post" enctype="multipart/form-data" id="add_borrower_kyc_form">
                    <!-- Borrower Personal Details Section -->
                    <h5 class="card-title p-3 bg-info text-white rounded">Borrower Personal Details</h5>
                    <div class="form-group row">
                        <label for="inputBorrowerFirstName" class="col-md-3 col-form-label font-weight-bold text-right">Full Name</label>
                        <div class="col-md-9">
                            <input type="text" name="borrower_name" class="form-control" id="inputBorrowerFirstName" placeholder="Enter Full Name" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="inputBorrowerUniqueNumber" class="col-md-3 col-form-label font-weight-bold text-right">National ID number</label>
                        <div class="col-md-9">
                            <input type="text" name="borrower_nid" class="form-control" id="inputBorrowerUniqueNumber" placeholder="Unique Number" required>
                            <p class="text-muted">This ID number must be unique.</p>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="inputBorrowerGender" class="col-md-3 col-form-label font-weight-bold text-right">Gender</label>
                        <div class="col-md-6">
                            <select class="form-control" name="borrower_gender" id="inputBorrowerGender" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <!-- Optionally add an "Other" if needed -->
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="inputBorrowerMobile" class="col-md-3 col-form-label font-weight-bold text-right">Mobile</label>
                        <div class="col-md-9">
                            <input type="text" name="borrower_mobile" class="form-control" id="inputBorrowerMobile" placeholder="Numbers Only" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="inputBorrowerEmail" class="col-md-3 col-form-label font-weight-bold text-right">Email</label>
                        <div class="col-md-9">
                            <input type="email" name="borrower_email" class="form-control" id="inputBorrowerEmail" placeholder="Email" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="inputBorrowerDob" class="col-md-3 col-form-label font-weight-bold text-right">Date of Birth</label>
                        <div class="col-md-6">
                            <input type="date" name="borrower_dob" class="form-control" id="inputBorrowerDob" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="inputBorrowerAddress" class="col-md-3 col-form-label font-weight-bold text-right">Address</label>
                        <div class="col-md-9">
                            <input type="text" name="borrower_address" class="form-control" id="inputBorrowerAddress" placeholder="Address" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="inputBorrowerStatus" class="col-md-3 col-form-label font-weight-bold text-right">Working Status</label>
                        <div class="col-md-9">
                            <select name="borrower_working_status" class="form-control" id="inputBorrowerStatus" required>
                                <option value="Employed">Employed</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="borrower_password" class="col-md-3 col-form-label font-weight-bold text-right">Password</label>
                        <div class="col-md-9">
                            <input type="text" name="borrower_password" class="form-control" id="borrower_password" placeholder="Password" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="photo_file" class="col-md-3 col-form-label font-weight-bold text-right">Borrower Photo</label>
                        <div class="col-md-9">
                            <input type="file" id="photo_file" name="photo" required>
                        </div>
                    </div>

                    <!-- KYC Documents Section -->
                    <h5 class="card-title p-3 bg-secondary text-white rounded mt-4">KYC Document Details</h5>
                    <div class="form-group row">
                        <label for="scanned_data" class="col-md-3 col-form-label font-weight-bold text-right">
                            Scanned Data<br><small class="text-muted">(JSON format)</small>
                        </label>
                        <div class="col-md-9">
                            <textarea name="scanned_data" id="scanned_data" class="form-control" placeholder='{"name": "John Doe", "dob": "1990-01-01", "doc_no": "123456789"}' rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="front_image" class="col-md-3 col-form-label font-weight-bold text-right">Front Image (ID)</label>
                        <div class="col-md-9">
                            <input type="file" name="front_image" id="front_image" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-group row">
                        <label for="back_image" class="col-md-3 col-form-label font-weight-bold text-right">Back Image (ID)</label>
                        <div class="col-md-9">
                            <input type="file" name="back_image" id="back_image" class="form-control" required>
                        </div>
                    </div>
                    <div class="box-footer mt-3 text-right">
                        <button type="submit" name="submit" class="btn btn-primary">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include_once "inc/footer.php"; ?>
