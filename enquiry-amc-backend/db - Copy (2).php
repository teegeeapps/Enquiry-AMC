<?php
$host = 'sql311.infinityfree.com';
$user = 'if0_39624272';
$pass = 'QKtRJvOfQwwt';
$db = 'if0_39624272_enquiry_amc';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
