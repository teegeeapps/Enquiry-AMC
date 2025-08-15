<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);
$employee_number = $input['employee_number'];

if (!$employee_number) {
    echo json_encode(['status' => 'error', 'message' => 'Employee number missing']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        e.id, 
        e.employee_name, 
        e.employee_number, 
        e.contact_no, 
        e.email_id,
        e.status, 
        e.last_login_time, 
        e.created_at, 
        e.updated_at,
        e.password, 
        e.role_id,
        r.role_name
    FROM employees e
    LEFT JOIN roles r ON e.role_id = r.id
    WHERE LOWER(e.employee_number) = LOWER(?)
");
$stmt->bind_param("s", $employee_number);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $profile = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'profile' => $profile]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?>
