<?php

function sendAccountCreatedEmail($to, $name, $password) {
    $url = 'https://mail-sender-api1.p.rapidapi.com/';
    $apiKey = '749ce8cd97mshc3979eaf8cf4225p1f8e97jsn97c56b994b54';
    $data = [
        'sendto' => $to,
        'name' => $name,
        'replyTo' => 'admin@go-mail.us.to',
        'ishtml' => 'false',
        'title' => 'JRApp Credit Account Created',
        'body' => "Welcome to JRApp Credit! Your account has been created successfully. Password: $password. You can now log in to the JRApp Credit app using your email and this password."
    ];
    $payload = json_encode($data);
    $headers = [
        'Content-Type: application/json',
        'x-rapidapi-host: mail-sender-api1.p.rapidapi.com',
        'x-rapidapi-key: ' . $apiKey
    ];
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
    if ($error) {
        return false;
    }
    return true;
}
