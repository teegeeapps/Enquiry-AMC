<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
include 'db.php';

//$sql = "SELECT * FROM employees";
$sql = "SELECT id, employee_name, employee_number, contact_no, email_id, is_active FROM employees";
$result = $conn->query($sql);

$data = array();
while($row = $result->fetch_assoc()) {
  $data[] = $row;
}
echo json_encode($data);
?>