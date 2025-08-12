<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
require 'db.php'; // your DB connection file
header("Content-Type: application/json");

// ✅ Fetch all technician assignments (both ENQUIRY & AMC)
$sql = "
    SELECT 
        ta.id AS assignment_id,
        ta.enquiry_id,
        e.client_name,
        e.contact_person,
        e.contact_no1,
        e.requirement_category,
        e.delivery_date,
        ta.technician_id,
        emp.employee_name AS technician_name,
        ta.assignment_type,
        ta.created_by,
        ta.created_on,
        ta.modified_by,
        ta.modified_on
    FROM technician_assignments ta
    LEFT JOIN enquiry e 
        ON ta.enquiry_id = e.id
    LEFT JOIN employees emp 
        ON ta.technician_id = emp.id
    ORDER BY ta.created_on DESC
";

$result = $conn->query($sql);

if (!$result) {
    echo json_encode([
        "status" => "error",
        "message" => "Query failed: " . $conn->error
    ]);
    exit;
}

$assignments = [];
while ($row = $result->fetch_assoc()) {
    $assignments[] = $row;
}

echo json_encode([
    "status" => "success",
    "data" => $assignments
]);
