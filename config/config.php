<?php
$localhost = "mysql-35587e6f-chrisryankent-c012.l.aivencloud.com";
$user = 'avnadmin';
$password = 'AVNS_hflCmGckDNHBftFbQ-s';
$dbname = 'defaultdb';

$conn =new mysqli($localhost, $user, $password, $dbname);

if($conn->connect_error){
    echo 'connection failed';   
}

define('INFOBIP_API_KEY', 'b08cb4893957abc1c864deb6fc356ef9-b03ffcc3-1370-4aef-8ed6-372ee34bb20e');

?>
