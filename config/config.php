<?php
$localhost = "mysql-35587e6f-chrisryankent-c012.l.aivencloud.com";
$user = 'avnadmin';
$password = 'AVNS_hflCmGckDNHBftFbQ-s';
$dbname = 'defaultdb';
$port = 3306;

// SSL setup for Aiven
$ca_cert_path = __DIR__ . "/ca.pem"; // Ensure ca.pem is placed in the same directory

$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, $ca_cert_path, NULL, NULL);
mysqli_real_connect($conn, $localhost, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

if($conn->connect_error){
    echo 'connection failed';   
}

define('INFOBIP_API_KEY', 'b08cb4893957abc1c864deb6fc356ef9-b03ffcc3-1370-4aef-8ed6-372ee34bb20e');
?>
