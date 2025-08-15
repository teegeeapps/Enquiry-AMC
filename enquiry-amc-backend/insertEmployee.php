<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include 'db.php';
$data = json_decode(file_get_contents("php://input"));

// Collect values from request body
$employee_name   = $data->employee_name ?? '';
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

// Generate employee_number automatically using MAX()
$result = $conn->query("SELECT MAX(CAST(SUBSTRING(employee_number, 2) AS UNSIGNED)) AS max_num FROM employees");

if ($result && $row = $result->fetch_assoc() && $row['max_num'] !== null) {
    $new_int = $row['max_num'] + 1;
    $employee_number = 'E' . str_pad($new_int, 3, '0', STR_PAD_LEFT);
} else {
    // No data in table yet
    $employee_number = 'E001';
}

// Prepare SQL safely using prepared statements
$sql = "INSERT INTO employees 
(employee_name, employee_number, contact_no, email_id, is_active, password, created_at, created_by, role_id) 
VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssi", $employee_name, $employee_number, $contact_no, $email_id, $is_active, $password, $created_by, $role_id);

if ($stmt->execute()) {
    echo json_encode([
        "message" => "Employee added successfully",
        "employee_number" => $employee_number
    ]);
} else {
    echo json_encode(["error" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
