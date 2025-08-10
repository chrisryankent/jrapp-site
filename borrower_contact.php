<?php
session_start();
include_once "inc/header.php";
include_once "inc/sidebar.php";
include_once "config/config.php";
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<div class='content-wrapper'><div class='container-fluid'><div class='alert alert-danger mt-3'>Please log in to access this page.</div></div></div>";
    include_once "inc/footer.php";
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Handle AJAX POST to save contacts
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['contacts_json'])) {
    $contacts = json_decode($_POST['contacts_json'], true);
    if (is_array($contacts)) {
        foreach ($contacts as $contact) {
            $name = mysqli_real_escape_string($conn, $contact['name']);
            $phone = mysqli_real_escape_string($conn, $contact['phone']);
            // Prevent duplicates for this user
            $exists = mysqli_query($conn, "SELECT id FROM tbl_user_contacts WHERE user_id='$user_id' AND phone='$phone' LIMIT 1");
            if (!mysqli_num_rows($exists)) {
                mysqli_query($conn, "INSERT INTO tbl_user_contacts (user_id, name, phone) VALUES ('$user_id', '$name', '$phone')");
            }
        }
        echo json_encode(['status'=>'success']);
    } else {
        echo json_encode(['status'=>'error','msg'=>'Invalid contacts data']);
    }
    exit;
}

// Fetch contacts from DB for this user
$contacts = [];
$res = mysqli_query($conn, "SELECT name, phone FROM tbl_user_contacts WHERE user_id='$user_id' ORDER BY name ASC");
while ($row = mysqli_fetch_assoc($res)) {
    $contacts[] = $row;
}
?>

<div class="content-wrapper">
    <div class="container-fluid mt-4">
        <h3 class="mb-4">Borrower Phone Contacts</h3>
        <div class="alert alert-info" id="contact-permission-alert">
            To continue, please accept and allow access to your phone contacts.<br>
            <button class="btn btn-primary btn-sm mt-2" onclick="getContacts()">Allow Access</button>
        </div>
        <div id="contacts-section" style="display:none;">
            <h5>Your Contacts</h5>
            <table class="table table-bordered" id="contacts-table">
                <thead>
                    <tr><th>Name</th><th>Phone</th></tr>
                </thead>
                <tbody>
                <?php foreach ($contacts as $c): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td><?php echo htmlspecialchars($c['phone']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Try to access contacts using the Contacts Picker API (supported browsers only)
function getContacts() {
    if ('contacts' in navigator && 'ContactsManager' in window) {
        const props = ['name', 'tel'];
        navigator.contacts.select(props, {multiple: true})
            .then(contacts => {
                let formatted = [];
                contacts.forEach(c => {
                    if (c.name && c.tel) {
                        formatted.push({name: c.name[0], phone: c.tel[0]});
                    }
                });
                if (formatted.length) {
                    // Send to server
                    fetch('borrower_contact.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                        body: 'contacts_json=' + encodeURIComponent(JSON.stringify(formatted))
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.status === 'success') {
                            location.reload();
                        } else {
                            alert('Failed to save contacts: ' + (data.msg || 'Unknown error'));
                        }
                    });
                }
            })
            .catch(err => {
                alert('Access to contacts was denied or not supported.');
            });
    } else {
        alert('Your browser does not support the Contacts Picker API. Please use a compatible browser or upload contacts manually.');
    }
}

// Show contacts table if already present
window.onload = function() {
    var rows = document.querySelectorAll('#contacts-table tbody tr');
    if (rows.length > 0) {
        document.getElementById('contacts-section').style.display = '';
        document.getElementById('contact-permission-alert').style.display = 'none';
    }
};
</script>

<?php include_once "inc/footer.php"; ?>