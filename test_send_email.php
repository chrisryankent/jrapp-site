<?php
include_once 'helpers/send_email.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'] ?? '';
    $name = $_POST['name'] ?? '';
    $password = $_POST['password'] ?? '';
    $result = sendAccountCreatedEmail($to, $name, $password);
    $msg = $result ? 'Email sent successfully!' : 'Failed to send email.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test Send Email</title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
</head>
<body>
<div class="container mt-5">
    <h2>Test Send Account Created Email</h2>
    <?php if (isset($msg)) { ?>
        <div class="alert alert-<?php echo ($result ? 'success' : 'danger'); ?>"><?php echo $msg; ?></div>
    <?php } ?>
    <form method="post">
        <div class="form-group">
            <label for="to">Recipient Email</label>
            <input type="email" class="form-control" id="to" name="to" required>
        </div>
        <div class="form-group">
            <label for="name">Recipient Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="text" class="form-control" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary">Send Test Email</button>
    </form>
</div>
</body>
</html>
