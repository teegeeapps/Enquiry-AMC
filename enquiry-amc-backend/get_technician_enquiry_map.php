<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
require 'db.php';
// { "enquiry_id": "EQ001" } -> sample request
$data = json_decode(file_get_contents("php://input"), true);
$enquiryId = $data['enquiry_id'] ?? '';

if (!$enquiryId) {
    echo json_encode(["status" => "error", "message" => "Missing enquiry_id"]);
    exit();
}

// ✅ Fetch enquiry basic details
$sql = "SELECT client_name, contact_person_name, contact_no1, address FROM enquiries WHERE enquiry_id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $enquiryId);
$stmt->execute();
$result = $stmt->get_result();
$enquiry = $result->fetch_assoc();
$stmt->close();

// ✅ Fetch technician assignment (if any)
$sql2 = "SELECT ea.technician_employee_id, e.employee_name AS technician_name,
                ea.delivery_instructions, ea.customer_location, ea.assigned_by, ea.assigned_date
         FROM enquiry_assignments ea
         LEFT JOIN employees e ON ea.technician_employee_id = e.employee_number
         WHERE ea.enquiry_id=?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("s", $enquiryId);
$stmt2->execute();
$result2 = $stmt2->get_result();
$assignment = $result2->fetch_assoc();
$stmt2->close();

// ✅ Fetch visit history
$sql3 = "SELECT visit_date, added_by, added_at FROM enquiry_visit_history WHERE enquiry_id=? ORDER BY added_at DESC";
$stmt3 = $conn->prepare($sql3);
$stmt3->bind_param("s", $enquiryId);
$stmt3->execute();
$result3 = $stmt3->get_result();
$visitHistory = [];
while ($row = $result3->fetch_assoc()) {
    $visitHistory[] = $row;
}
$stmt3->close();
$conn->close();

echo json_encode([
    "status" => "success",
    "enquiry" => $enquiry,
    "assignment" => $assignment,
    "visit_history" => $visitHistory
]);
?>
