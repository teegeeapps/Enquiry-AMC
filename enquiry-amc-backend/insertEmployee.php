<?php
include 'db.php';

//$data = json_decode(file_get_contents("php://input"));

//$employee_name = $data->employee_name;
//$employee_number = $data->employee_number;
//$contact_no = $data->contact_no;
//$email_id = $data->email_id;
//$status = $data->status;
//$password = $data->password;
//$created_by = $data->created_by;

$employee_name = 'Aki';
$employee_number = 'EMP123';
$contact_no = '9876543210';
$email_id = 'Aki@example.com';
$status = 'Active';
$password = 'password123';
$created_by = 'admin_user';

$sql = "INSERT INTO employees 
(employee_name, employee_number, contact_no, email_id, status, password, created_at, created_by) 
VALUES 
('$employee_name', '$employee_number', '$contact_no', '$email_id', '$status', '$password', NOW(), '$created_by')";

if ($conn->query($sql) === TRUE) {
    echo json_encode(["message" => "Employee added successfully"]);
} else {
    echo json_encode(["error" => "Error: " . $conn->error]);
}
?>
