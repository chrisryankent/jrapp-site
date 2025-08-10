<?php
$localhost = "fdb1028.awardspace.net";
$user = '4644371_brac';
$password = 'huncho@9971';
$dbname = '4644371_brac';

$conn =new mysqli($localhost, $user, $password, $dbname);

if($conn->connect_error){
    echo 'connection failed';   
}

define('INFOBIP_API_KEY', 'b08cb4893957abc1c864deb6fc356ef9-b03ffcc3-1370-4aef-8ed6-372ee34bb20e');

?>