<?php
$localhost = "mysql-35587e6f-chrisryankent-c012.l.aivencloud.com";
$user = 'avnadmin';
$password = 'AVNS_hflCmGckDNHBftFbQ-s';
$dbname = 'defaultdb';
$port = 3306;
$ca_cert_path = "ca.pem"; // Ensure ca.pem is in this directory

// 🧪 Raw connectivity test
$socket = @fsockopen($localhost, $port, $errno, $errstr, 5);
if (!$socket) {
    die("❌ Raw connection to Aiven failed: $errstr ($errno)");
} else {
    fclose($socket);
}

// ✅ SSL-enabled MySQL connection
$conn = mysqli_init();
mysqli_ssl_set($conn, NULL, NULL, $ca_cert_path, NULL, NULL);
mysqli_real_connect($conn, $localhost, $user, $password, $dbname, $port, NULL, MYSQLI_CLIENT_SSL);

if($conn->connect_error){
    die('❌ MySQL connection failed: ' . $conn->connect_error);
}

define('INFOBIP_API_KEY', 'b08cb4893957abc1c864deb6fc356ef9-b03ffcc3-1370-4aef-8ed6-372ee34bb20e');
?>
