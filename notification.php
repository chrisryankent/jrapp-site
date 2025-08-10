<?php
session_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";

error_reporting(E_ALL);
ini_set('display_errors', 1);

// --- Deletion Handling ---
$delete_message = "";
if (isset($_GET['delete_notification'])) {
    $delete_id = intval($_GET['delete_notification']);
    $delete_query = "DELETE FROM tbl_notification WHERE id = '$delete_id'";
    if (mysqli_query($conn, $delete_query)) {
        $delete_message = "<div class='alert alert-success alert-dismissible fade show mt-3' role='alert'>
            Notification deleted successfully.
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    } else {
        $delete_message = "<div class='alert alert-danger alert-dismissible fade show mt-3' role='alert'>
            Error deleting notification: " . mysqli_error($conn) . "
            <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                <span aria-hidden='true'>&times;</span>
            </button>
        </div>";
    }
    // Refresh page after deletion to avoid resubmission.
    echo "<meta http-equiv='refresh' content='1;url=notification.php'>";
}

// --- STEP 1: Overdue Notification Insertion ---
$current_date = date('Y-m-d');
$overdue_query = "SELECT rs.id, rs.loan_application_id, rs.installment_no, rs.due_date
                  FROM tbl_repayment_schedule rs
                  WHERE rs.is_paid = 0
                    AND rs.due_date < '$current_date'";
$result_overdue = mysqli_query($conn, $overdue_query);

if ($result_overdue) {
    while ($overdue = mysqli_fetch_assoc($result_overdue)) {
        $loan_id = $overdue['loan_application_id'];
        $installment_no = $overdue['installment_no'];
        $due_date = $overdue['due_date'];
        $notificationCheckQuery = "SELECT id FROM tbl_notification 
                                   WHERE loan_application_id = '$loan_id' 
                                     AND message LIKE '%Installment $installment_no%' 
                                   LIMIT 1";
        $result_check = mysqli_query($conn, $notificationCheckQuery);
        if (mysqli_num_rows($result_check) == 0) {
            $message = "Overdue: Installment $installment_no for Loan #$loan_id, due on $due_date, is overdue. Please settle ASAP.";
            $insertNotification = "INSERT INTO tbl_notification 
                                   (recipient_user, borrower_id, loan_application_id, type, message) 
                                   VALUES (NULL, NULL, '$loan_id', 'SMS', '" . mysqli_real_escape_string($conn, $message) . "')";
            mysqli_query($conn, $insertNotification);
        }
    }
}

// --- STEP 2: Retrieve All Notifications ---
$notification_query = "SELECT * FROM tbl_notification ORDER BY sent_at DESC";
$result_notifications = mysqli_query($conn, $notification_query);
?>

<div class="content-wrapper">
    <div class="container-fluid mt-4">
        <h2>Notifications</h2>
        <?php
        if (!empty($delete_message)) {
            echo "<div class='row'><div class='col-md-8 offset-md-2'>{$delete_message}</div></div>";
        }
        ?>
        <div class="card">
            <div class="card-header bg-warning text-white">All Notifications</div>
            <div class="card-body">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Type</th>
                            <th>Message</th>
                            <th>Sent At</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result_notifications && mysqli_num_rows($result_notifications) > 0) {
                            while ($row = mysqli_fetch_assoc($result_notifications)) {
                                echo "<tr>";
                                echo "<td>" . htmlspecialchars($row['id']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['type']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['message']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['sent_at']) . "</td>";
                                echo "<td>" . ($row['is_read'] ? "Read" : "Unread") . "</td>";
                                echo "<td>
                                    <a href='notification.php?delete_notification=" . $row['id'] . "' onclick=\"return confirm('Are you sure you want to delete this notification?');\" class='btn btn-danger btn-sm'>Delete</a>
                                </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='6' class='text-center'>No notifications available.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include_once "inc/footer.php"; ?>