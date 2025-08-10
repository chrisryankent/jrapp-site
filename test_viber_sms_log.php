<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $request_id = $_POST['request_id'] ?? '';
    $url = 'https://d7sms.p.rapidapi.com/report/v1/viber-log/' . urlencode($request_id);
    $headers = [
        'x-rapidapi-host: d7sms.p.rapidapi.com',
        'x-rapidapi-key: 749ce8cd97mshc3979eaf8cf4225p1f8e97jsn97c56b994b54'
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPGET, true);
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
    <title>Test Viber SMS Log API</title>
    <link rel="stylesheet" href="assets/css/bootstrap.css">
</head>
<body>
<div class="container mt-5">
    <h2>Test Viber SMS Log API</h2>
    <?php if (isset($msg)) { ?>
        <div class="alert alert-info"><?php echo $msg; ?></div>
    <?php } ?>
    <form method="post">
        <div class="form-group">
            <label for="request_id">Request ID</label>
            <input type="text" class="form-control" id="request_id" name="request_id" required>
        </div>
        <button type="submit" class="btn btn-primary">Check SMS Log</button>
    </form>
</div>
</body>
</html>
