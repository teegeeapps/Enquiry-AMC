<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$enquiryId = $data['enquiry_id'] ?? '';
$technicianId = $data['technician_employee_id'] ?? '';
$assignedBy = $data['assigned_by'] ?? ''; // typically admin or manager's name or ID

if (!$enquiryId || !$technicianId) {
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit();
}

try {
    // Check if already assigned
    $checkSql = "SELECT id FROM enquiry_technician_map WHERE enquiry_id = :enquiry_id";
    $stmt = $conn->prepare($checkSql);
    $stmt->bindParam(':enquiry_id', $enquiryId);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        // Update assignment
        $updateSql = "UPDATE enquiry_technician_map SET technician_employee_id = :tech_id, assigned_by = :assigned_by, assigned_date = NOW() WHERE enquiry_id = :enquiry_id";
        $stmt = $conn->prepare($updateSql);
    } else {
        // Insert new assignment
        $insertSql = "INSERT INTO enquiry_technician_map (enquiry_id, technician_employee_id, assigned_by, assigned_date) VALUES (:enquiry_id, :tech_id, :assigned_by, NOW())";
        $stmt = $conn->prepare($insertSql);
    }

    $stmt->bindParam(':enquiry_id', $enquiryId);
    $stmt->bindParam(':tech_id', $technicianId);
    $stmt->bindParam(':assigned_by', $assignedBy);
    $stmt->execute();

    echo json_encode(["status" => "success", "message" => "Technician assigned successfully."]);
} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
