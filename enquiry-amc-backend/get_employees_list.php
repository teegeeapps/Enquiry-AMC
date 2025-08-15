<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include 'db.php';

// SQL to get employee list
$sql = "SELECT id, employee_name, employee_number, contact_no, email_id, status 
        FROM employees";
$result = $conn->query($sql);

// Prepare data array
$data = array();
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

// Define the column headers for UI
$columns = [
    "employee_name",
    "employee_number",
    "contact_no",
    "email_id",
    "status"
];

// Prepare final response
$response = [
    "status" => "success",
    "columns" => $columns,
    "data" => $data
];

// Output as JSON
echo json_encode($response);

$conn->close();
?>
