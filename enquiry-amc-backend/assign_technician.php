<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");
require 'db.php';

// ✅ Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

$enquiryId            = $data['enquiry_id'] ?? null;
$technicianId         = $data['technician_employee_id'] ?? null;
$deliveryInstructions = $data['delivery_instructions'] ?? '';
$customerLocation     = $data['customer_location'] ?? '';
$assignedBy           = $data['assigned_by'] ?? 'system';
$visitDate            = $data['visit_date'] ?? null;
$completedStatus      = $data['completed_status'] ?? 'Pending'; // ✅ New field

// ✅ Always fetch Technician List
$techSql = "
    SELECT 
        id AS employee_id,
        employee_number,
        employee_name
    FROM employees
    WHERE 
        role_id = (SELECT id FROM roles WHERE role_name = 'Technician')
        AND is_active = 1
";
$techResult = $conn->query($techSql);

$technicians = [];
if ($techResult && $techResult->num_rows > 0) {
    while ($row = $techResult->fetch_assoc()) {
        $technicians[] = $row;
    }
}

$enquiryDetails     = null;
$existingAssignment = null;
$visitHistory       = [];

// ✅ Fetch Enquiry Basic Details if enquiry_id given
if ($enquiryId) {
    $enquirySql = "SELECT enquiry_id, client_name, contact_person_name, contact_no1, address 
                   FROM enquiries WHERE enquiry_id = ?";
    $stmt = $conn->prepare($enquirySql);
    $stmt->bind_param("s", $enquiryId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $enquiryDetails = $row;
    }
    $stmt->close();

    // ✅ Fetch existing technician assignment
    $assignSql = "SELECT technician_employee_id, delivery_instructions, customer_location, completed_status, assigned_by, assigned_date 
                  FROM enquiry_assignments WHERE enquiry_id = ?";
    $stmt2 = $conn->prepare($assignSql);
    $stmt2->bind_param("s", $enquiryId);
    $stmt2->execute();
    $result2 = $stmt2->get_result();
    if ($row = $result2->fetch_assoc()) {
        $existingAssignment = $row;
    }
    $stmt2->close();

    // ✅ Fetch visit history
    $visitSql = "SELECT visit_date, added_by, added_at 
                 FROM enquiry_visit_history 
                 WHERE enquiry_id = ? 
                 ORDER BY added_at DESC";
    $stmt3 = $conn->prepare($visitSql);
    $stmt3->bind_param("s", $enquiryId);
    $stmt3->execute();
    $visitResult = $stmt3->get_result();
    while ($row = $visitResult->fetch_assoc()) {
        $visitHistory[] = $row;
    }
    $stmt3->close();
}

// ✅ If NO update params → just return fetched data
if (!$technicianId && !$visitDate && !$deliveryInstructions && !$customerLocation && !$completedStatus) {
    echo json_encode([
        "status"             => "success",
        "message"            => "Fetched enquiry details, technician list & assignment",
        "enquiry_details"    => $enquiryDetails,
        "technician_list"    => $technicians,
        "assignment_details" => $existingAssignment,
        "visit_history"      => $visitHistory
    ]);
    $conn->close();
    exit();
}

// ✅ Otherwise → Update Mode
$conn->begin_transaction();

try {
    $messages = [];

    // ✅ Technician assignment insert/update
    if ($technicianId) {
        $checkSql = "SELECT id FROM enquiry_assignments WHERE enquiry_id=?";
        $stmt = $conn->prepare($checkSql);
        $stmt->bind_param("s", $enquiryId);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();

        if ($exists) {
            $updateSql = "UPDATE enquiry_assignments 
                          SET technician_employee_id=?, delivery_instructions=?, customer_location=?, 
                              completed_status=?, assigned_by=?, assigned_date=NOW()
                          WHERE enquiry_id=?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssssss", $technicianId, $deliveryInstructions, $customerLocation, $completedStatus, $assignedBy, $enquiryId);
            $updateStmt->execute();
            $messages[] = "Technician assignment updated";
            $updateStmt->close();
        } else {
            $insertSql = "INSERT INTO enquiry_assignments 
                          (enquiry_id, technician_employee_id, delivery_instructions, customer_location, completed_status, assigned_by, assigned_date)
                          VALUES (?, ?, ?, ?, ?, ?, NOW())";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bind_param("ssssss", $enquiryId, $technicianId, $deliveryInstructions, $customerLocation, $completedStatus, $assignedBy);
            $insertStmt->execute();
            $messages[] = "Technician assigned successfully";
            $insertStmt->close();
        }
    }

    // ✅ Visit date entry
    if ($visitDate) {
        $visitInsSql = "INSERT INTO enquiry_visit_history (enquiry_id, visit_date, added_by, added_at) VALUES (?, ?, ?, NOW())";
        $visitStmt = $conn->prepare($visitInsSql);
        $visitStmt->bind_param("sss", $enquiryId, $visitDate, $assignedBy);
        $visitStmt->execute();
        $messages[] = "Visit date added successfully";
        $visitStmt->close();
    }

    $conn->commit();

    // ✅ Fetch updated assignment & visit history
    $updatedAssign = null;
    $updatedHistory = [];

    $stmt2 = $conn->prepare("SELECT technician_employee_id, delivery_instructions, customer_location, completed_status, assigned_by, assigned_date 
                             FROM enquiry_assignments WHERE enquiry_id=?");
    $stmt2->bind_param("s", $enquiryId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    if ($row = $res2->fetch_assoc()) {
        $updatedAssign = $row;
    }
    $stmt2->close();

    $stmt3 = $conn->prepare("SELECT visit_date, added_by, added_at 
                             FROM enquiry_visit_history 
                             WHERE enquiry_id = ? 
                             ORDER BY added_at DESC");
    $stmt3->bind_param("s", $enquiryId);
    $stmt3->execute();
    $res3 = $stmt3->get_result();
    while ($row = $res3->fetch_assoc()) {
        $updatedHistory[] = $row;
    }
    $stmt3->close();

    echo json_encode([
        "status"             => "success",
        "messages"           => $messages,
        "enquiry_details"    => $enquiryDetails,
        "technician_list"    => $technicians,
        "assignment_details" => $updatedAssign,
        "visit_history"      => $updatedHistory
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode([
        "status"             => "error",
        "message"            => "Database error: " . $e->getMessage(),
        "enquiry_details"    => $enquiryDetails,
        "technician_list"    => $technicians,
        "assignment_details" => $existingAssignment,
        "visit_history"      => $visitHistory
    ]);
}

$conn->close();
?>
