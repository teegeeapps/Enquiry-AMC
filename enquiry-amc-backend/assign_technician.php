<?php
require 'db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
/* Sample request
{
  "enquiry_id": "EQ001",
  "technician_employee_id": 5,
  "delivery_instructions": "Deliver carefully",
  "customer_location": "Google Maps URL",
  "assigned_by": "AdminUser",
  "visit_date": "2025-07-26"
}
*/
$data = json_decode(file_get_contents("php://input"), true);

$enquiryId = $data['enquiry_id'] ?? '';
$technicianId = $data['technician_employee_id'] ?? null;
$deliveryInstructions = $data['delivery_instructions'] ?? '';
$customerLocation = $data['customer_location'] ?? '';
$assignedBy = $data['assigned_by'] ?? 'system';
$visitDate = $data['visit_date'] ?? null; // Optional for visit history

if (!$enquiryId) {
    echo json_encode(["status" => "error", "message" => "Missing enquiry_id"]);
    exit();
}

// 🔹 START TRANSACTION for safety
$conn->begin_transaction();

try {
    $messages = [];

    // ✅ If technician assignment is provided
    if ($technicianId) {
        // Check if already assigned
        $checkSql = "SELECT id FROM enquiry_assignments WHERE enquiry_id=?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("s", $enquiryId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Update technician assignment
            $updateSql = "UPDATE enquiry_assignments 
                          SET technician_employee_id=?, delivery_instructions=?, customer_location=?, 
                              assigned_by=?, assigned_date=NOW()
                          WHERE enquiry_id=?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("issss", $technicianId, $deliveryInstructions, $customerLocation, $assignedBy, $enquiryId);
            $updateStmt->execute();
            $messages[] = "Technician assignment updated";
            $updateStmt->close();
        } else {
            // Insert new assignment
            $insertSql = "INSERT INTO enquiry_assignments 
                          (enquiry_id, technician_employee_id, delivery_instructions, customer_location, assigned_by, assigned_date)
                          VALUES (?, ?, ?, ?, ?, NOW())";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("sisss", $enquiryId, $technicianId, $deliveryInstructions, $customerLocation, $assignedBy);
            $insertStmt->execute();
            $messages[] = "Technician assigned successfully";
            $insertStmt->close();
        }
        $stmt->close();
    }

    // ✅ If visit date provided, add it to visit history
    if ($visitDate) {
        $visitSql = "INSERT INTO enquiry_visit_history (enquiry_id, visit_date, added_by, added_at) VALUES (?, ?, ?, NOW())";
        $visitStmt = $conn->prepare($visitSql);
        $visitStmt->bind_param("sss", $enquiryId, $visitDate, $assignedBy);
        $visitStmt->execute();
        $messages[] = "Visit date added successfully";
        $visitStmt->close();
    }

    // ✅ If neither technician nor visit_date passed → Do nothing
    if (!$technicianId && !$visitDate) {
        echo json_encode(["status" => "error", "message" => "No technician or visit_date provided"]);
        exit();
    }

    // ✅ Commit all changes
    $conn->commit();

    echo json_encode(["status" => "success", "messages" => $messages]);

} catch (Exception $e) {
    $conn->rollback(); // Rollback on failure
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$conn->close();
?>
