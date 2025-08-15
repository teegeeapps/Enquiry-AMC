<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include 'db.php';

// Handle preflight (OPTIONS) request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"));

// Collect values from request body
$employee_name   = $data->employee_name ?? '';
$contact_no      = $data->contact_no ?? '';
$email_id        = $data->email_id ?? '';
$status       = $data->status ?? '1'; // default to active
$password        = $data->password ?? '';
$created_by      = $data->created_by ?? '';
$role_id         = $data->role_id ?? null; // new field

if (!$role_id) {
    echo json_encode(["status" => "error", "message" => "role_id is required"]);
    exit();
}

if (empty($contact_no)) {
    echo json_encode(["status" => "error", "message" => "Contact number is required"]);
    exit();
}

// ✅ Check if contact number already exists
$check_stmt = $conn->prepare("SELECT id FROM employees WHERE contact_no = ?");
$check_stmt->bind_param("s", $contact_no);
$check_stmt->execute();
$check_stmt->store_result();

if ($check_stmt->num_rows > 0) {
    echo json_encode(["status" => "error", "message" => "Contact number already exists"]);
    $check_stmt->close();
    $conn->close();
    exit();
}
$check_stmt->close();

// ✅ Generate employee_number automatically
$result = $conn->query("SELECT employee_number FROM employees ORDER BY id DESC LIMIT 1");

if ($result && $row = $result->fetch_assoc()) {
    $last_number = $row['employee_number']; // Example: E008
    $last_int = intval(substr($last_number, 1)); // Get numeric part
    $new_int = $last_int + 1;
    $employee_number = 'E' . str_pad($new_int, 3, '0', STR_PAD_LEFT);
} else {
    $employee_number = 'E001';
}

// ✅ Insert new employee
$sql = "INSERT INTO employees 
(employee_name, employee_number, contact_no, email_id, status, password, created_at, created_by, role_id) 
VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?)";

$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssssi", $employee_name, $employee_number, $contact_no, $email_id, $status, $password, $created_by, $role_id);

if ($stmt->execute()) {
    echo json_encode([
        "status" => "success",
        "message" => "Employee added successfully",
        "employee_number" => $employee_number
    ]);
} else {
    echo json_encode(["status" => "error", "message" => "Error: " . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
