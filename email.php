<?php
// Send email using Mailgun API via cURL

$api_key = '5db80eeac36d3354cca79daf9bac65c9-0ce15100-4f5a1dd0';
$domain = 'sandbox664017f93e12479fb8b98673d2a9ad47.mailgun.org';

$ch = curl_init();

curl_setopt($ch, CURLOPT_URL, "https://api.mailgun.net/v3/$domain/messages");
curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
curl_setopt($ch, CURLOPT_USERPWD, "api:$api_key");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

$postData = [
    'from'    => 'Mailgun Sandbox <postmaster@sandbox664017f93e12479fb8b98673d2a9ad47.mailgun.org>',
    'to'      => 'chris ryan kent <chrisryankent@gmail.com>',
    'subject' => 'Hello chris ryan kent',
    'text'    => 'Congratulations chris ryan kent, you just sent an email with Mailgun! You are truly awesome!'
];

curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);

$result = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Curl error: ' . curl_error($ch);
} else {
    echo $result;
}

curl_close($ch);
?>