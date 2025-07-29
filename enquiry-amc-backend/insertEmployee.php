<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include 'db.php';
$data = json_decode(file_get_contents("php://input"));

// Collect values from request body
$employee_name   = $data->employee_name ?? '';
$employee_number = $data->employee_number ?? '';
$contact_no      = $data->contact_no ?? '';
$email_id        = $data->email_id ?? '';
$is_active       = $data->status ?? '1'; // default to active
$password        = $data->password ?? '';
$created_by      = $data->created_by ?? '';
$role_id         = $data->role_id ?? null; // new field

if (!$role_id) {
    echo json_encode(["error" => "role_id is required"]);
    exit();
}

// Prepare SQL safely using prepared statements
$sql = "INSERT INTO employees 
(employee_name, employee_number, contact_no, email_id, is_active, password, created_at, created_by, role_id) 
VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssi", $employee_name, $employee_number, $contact_no, $email_id, $is_active, $password, $created_by, $role_id);

if ($stmt->execute()) {
    echo json_encode(["message" => "Employee added successfully"]);
} else {
    echo json_encode(["error" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
