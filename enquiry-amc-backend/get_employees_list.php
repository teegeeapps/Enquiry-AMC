<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

include 'db.php';

// SQL with JOIN to fetch role details
$sql = "SELECT 
            e.id,
            e.employee_name,
            e.employee_number,
            e.contact_no,
            e.email_id,
            e.status,
            e.role_id,
            r.role_name
        FROM employees e
        LEFT JOIN roles r ON e.role_id = r.id";

$result = $conn->query($sql);

// Prepare data array
$data = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

// Define the column headers for UI
$columns = [
    "employee_name",
    "employee_number",
    "contact_no",
    "email_id",
    "status",
    "role"
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
