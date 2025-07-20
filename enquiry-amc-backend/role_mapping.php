<?php
require 'db.php'; // assumes $conn is your mysqli connection

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Read input
$data = json_decode(file_get_contents("php://input"), true);
$employeeNumber = $data['employee_number'] ?? null;
$roleId = $data['role_id'] ?? null;
$currentUser = $data['modified_by'] ?? 'system';

if (!$employeeNumber || !$roleId) {
    echo json_encode(["status" => "error", "message" => "Missing employee_number or role_id"]);
    exit();
}

// Lookup employee_id from employee_number
$getEmpSql = "SELECT id FROM employees WHERE employee_number = ?";
$stmt = $conn->prepare($getEmpSql);
$stmt->bind_param("s", $employeeNumber);
$stmt->execute();
$stmt->bind_result($employeeId);
$stmt->fetch();
$stmt->close();

if (!$employeeId) {
    echo json_encode(["status" => "error", "message" => "Employee not found."]);
    exit();
}

try {
    // Check if mapping exists
    $checkSql = "SELECT id FROM employee_roles WHERE employee_id = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $employeeId);
    $checkStmt->execute();
    $checkStmt->store_result();

    if ($checkStmt->num_rows > 0) {
        // Update mapping
        $updateSql = "UPDATE employee_roles SET role_id = ?, updated_by = ?, updated_at = NOW() WHERE employee_id = ?";
        $updateStmt = $conn->prepare($updateSql);
        $updateStmt->bind_param("isi", $roleId, $currentUser, $employeeId);
        $updateStmt->execute();
        $updateStmt->close();
    } else {
        // Insert new mapping
        $insertSql = "INSERT INTO employee_roles (employee_id, role_id, created_by, updated_by, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("iiss", $employeeId, $roleId, $currentUser, $currentUser);
        $insertStmt->execute();
        $insertStmt->close();
    }

    echo json_encode(["status" => "success", "message" => "Role mapping saved successfully."]);

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$conn->close();
