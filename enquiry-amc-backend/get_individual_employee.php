<?php
require 'db.php';

// Get employee number from POST or Token
$employee_number = $_POST['employee_number']; // Example: You’ll pass this from frontend after login

if (!$employee_number) {
    echo json_encode(['status' => 'error', 'message' => 'Employee number missing']);
    exit;
}

$stmt = $conn->prepare("
    SELECT e.id, e.employee_name, e.employee_number, e.contact_no, e.email_id,
           e.status, e.last_login_time, e.created_at, e.updated_at,
           r.role_name
    FROM employees e
    JOIN employee_roles er ON e.id = er.employee_id
    JOIN roles r ON er.role_id = r.id
    WHERE e.employee_number = ?
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
