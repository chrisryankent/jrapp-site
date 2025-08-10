<?php
// filepath: /opt/lampp/htdocs/htdocs/test_sendmail.php

$to      = "chrisryankent@gmail.com"; // Change to your email address for testing
$subject = "Sendmail Test from AwardSpace";
$message = "This is a test email sent using the server's sendmail path.";
$headers = "From: chrisryankent@gmail.com\r\n" .
           "Reply-To: chrisryankent@gmail.com\r\n" .
           "X-Mailer: PHP/" . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo "<b>Success:</b> Test email sent to $to. Check your inbox (and spam folder).";
} else {
    echo "<b>Error:</b> Email could not be sent. Check server mail configuration.";
}
?>