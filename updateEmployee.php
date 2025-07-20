<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
include 'db.php';

$data = json_decode(file_get_contents("php://input"));
// Static data for testing

$employee_name = $data->employee_name;
$employee_number = $data->employee_number;
$contact_no = $data->contact_no;
$email_id = $data->email_id;
$is_active = $data->status;
$password = $data->password;
$created_by = $data->created_by;
$updated_by = $data->updated_by;

// ✅ Corrected query (missing closing quote & semicolon was fixed)
$sql = "UPDATE employees SET 
    employee_name = '$employee_name',
    contact_no = '$contact_no',
    email_id = '$email_id',
    is_active = '$is_active',
    password = '$password',
    updated_at = NOW(),
    updated_by = '$updated_by'
    WHERE employee_number = '$employee_number'";  // ✔️ Closing quote here, no semicolon inside the string

if ($conn->query($sql) === TRUE) {
    echo json_encode(["message" => "Employee updated successfully"]);
} else {
    echo json_encode(["error" => "Error: " . $conn->error]);
}
?>
