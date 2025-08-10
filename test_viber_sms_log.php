<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = $_POST['to'] ?? '';
    $text = $_POST['text'] ?? '';
    $url = 'https://sms77io.p.rapidapi.com/sms';
    $headers = [
        'Content-Type: application/x-www-form-urlencoded',
        'x-rapidapi-host: sms77io.p.rapidapi.com',
        'x-rapidapi-key: 749ce8cd97mshc3979eaf8cf4225p1f8e97jsn97c56b994b54'
    ];
    $postFields = http_build_query([
        'to' => $to,
        'text' => $text,
        'from' => 'JRApp' // Sender name (optional, can be customized)
    ]);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
        $msg = 'Error: ' . $error;
    } else {
        $msg = 'Response: ' . htmlspecialchars($response);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Test SMS77io SMS API</title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
</head>
<body>
<div class="container mt-5">
    <h2>Test SMS77io SMS API</h2>
    <?php if (isset($msg)) { ?>
        <div class="alert alert-info"><?php echo $msg; ?></div>
    <?php } ?>
    <form method="post">
        <div class="form-group">
            <label for="to">Recipient Phone Number</label>
            <input type="text" class="form-control" id="to" name="to" required placeholder="e.g. +1234567890">
        </div>
        <div class="form-group">
            <label for="text">Message</label>
            <textarea class="form-control" id="text" name="text" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Send SMS</button>
    </form>
</div>
</body>
</html>
