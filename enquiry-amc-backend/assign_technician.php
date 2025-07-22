<?php
require 'db.php';

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// ✅ Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
// ✅ Example test payload (for debugging)
 $data = json_decode('{"enquiry_id": "EQ001"}', true);


// ✅ Extract enquiry_id if provided
$enquiryId = $data['enquiry_id'] ?? null;
$technicianId = $data['technician_employee_id'] ?? null;
$deliveryInstructions = $data['delivery_instructions'] ?? '';
$customerLocation = $data['customer_location'] ?? '';
$assignedBy = $data['assigned_by'] ?? 'system';
$visitDate = $data['visit_date'] ?? null;

// ✅ Always fetch Technician List
$techSql = "
    SELECT 
        e.id AS employee_id,
        e.employee_name
    FROM employees e
    JOIN employee_roles er ON e.id = er.employee_id
    JOIN roles r ON er.role_id = r.id
    WHERE 
        r.role_name = 'Technician'
        AND e.is_active = 1
";
$techResult = $conn->query($techSql);

$technicians = [];
if ($techResult && $techResult->num_rows > 0) {
    while ($row = $techResult->fetch_assoc()) {
        $technicians[] = $row;
    }
}

// ✅ Prepare variables for existing assignment & visit history
$existingAssignment = null;
$visitHistory = [];

// ✅ If enquiry_id is provided, fetch existing assignment & visit history
if ($enquiryId) {
    // Fetch assignment details
    $assignSql = "SELECT technician_employee_id, delivery_instructions, customer_location, assigned_by, assigned_date 
                  FROM enquiry_assignments WHERE enquiry_id = ?";
    $stmt = $conn->prepare($assignSql);
    $stmt->bind_param("s", $enquiryId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $existingAssignment = $row;
    }
    $stmt->close();

    // Fetch visit history
    $visitSql = "SELECT visit_date, added_by, added_at 
                 FROM enquiry_visit_history 
                 WHERE enquiry_id = ? 
                 ORDER BY added_at DESC";
    $stmt2 = $conn->prepare($visitSql);
    $stmt2->bind_param("s", $enquiryId);
    $stmt2->execute();
    $visitResult = $stmt2->get_result();
    while ($row = $visitResult->fetch_assoc()) {
        $visitHistory[] = $row;
    }
    $stmt2->close();
}

// ✅ If NO technician assignment or visit_date → just return existing info
if (!$technicianId && !$visitDate) {
    echo json_encode([
        "status" => "success",
        "message" => "Fetched technician list & existing assignment details",
        "technician_list" => $technicians,
        "assignment_details" => $existingAssignment,
        "visit_history" => $visitHistory
    ]);
    exit();
}

// ✅ Otherwise, we are updating assignment or visit date → Start transaction
$conn->begin_transaction();

try {
    $messages = [];

    // ✅ Technician assignment / update
    if ($technicianId) {
        // Check if already assigned
        $checkSql = "SELECT id FROM enquiry_assignments WHERE enquiry_id=?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("s", $enquiryId);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Already exists → UPDATE
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
            // New assignment → INSERT
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

    // ✅ Add visit history if provided
    if ($visitDate) {
        $visitSql = "INSERT INTO enquiry_visit_history (enquiry_id, visit_date, added_by, added_at) VALUES (?, ?, ?, NOW())";
        $visitStmt = $conn->prepare($visitSql);
        $visitStmt->bind_param("sss", $enquiryId, $visitDate, $assignedBy);
        $visitStmt->execute();
        $messages[] = "Visit date added successfully";
        $visitStmt->close();
    }

    // ✅ Commit all changes
    $conn->commit();

    // ✅ After update → fetch updated assignment & visit history again
    $updatedAssign = null;
    $updatedHistory = [];

    $stmt = $conn->prepare("SELECT technician_employee_id, delivery_instructions, customer_location, assigned_by, assigned_date 
                            FROM enquiry_assignments WHERE enquiry_id=?");
    $stmt->bind_param("s", $enquiryId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $updatedAssign = $row;
    }
    $stmt->close();

    $stmt2 = $conn->prepare("SELECT visit_date, added_by, added_at 
                             FROM enquiry_visit_history 
                             WHERE enquiry_id = ? 
                             ORDER BY added_at DESC");
    $stmt2->bind_param("s", $enquiryId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row = $res2->fetch_assoc()) {
        $updatedHistory[] = $row;
    }
    $stmt2->close();

    echo json_encode([
        "status" => "success",
        "messages" => $messages,
        "technician_list" => $technicians,
        "assignment_details" => $updatedAssign,
        "visit_history" => $updatedHistory
    ]);

} catch (Exception $e) {
    $conn->rollback(); // Rollback on failure
    echo json_encode([
        "status" => "error",
        "message" => "Database error: " . $e->getMessage(),
        "technician_list" => $technicians,
        "assignment_details" => $existingAssignment,
        "visit_history" => $visitHistory
    ]);
}

$conn->close();
?>




/*
{} -> to get all technicians


To insert or update
{
  "enquiry_id": "EQ001",
  "technician_employee_id": 5,
  "delivery_instructions": "Handle with care",
  "customer_location": "https://maps.google.com/location123",
  "assigned_by": "AdminUser",
  "visit_date": "2025-07-30"
}

To fetch 
{"enquiry_id": "EQ001"}

*/