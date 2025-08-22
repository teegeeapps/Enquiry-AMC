<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require 'db.php';

// Read enquiry_id from request (GET or POST)
$input = json_decode(file_get_contents("php://input"), true);
$enquiry_id = $input['enquiry_id'] ?? ($_GET['enquiry_id'] ?? null);

if (!$enquiry_id) {
    echo json_encode(["status" => "error", "message" => "enquiry_id is required"]);
    exit();
}

// Query to fetch details
$sql = "SELECT enquiry_id, client_name, contact_person_name, contact_no1, 
               delivered_date, amc_status, amc_date, amc_period, requirement_category 
        FROM amc_list 
        WHERE enquiry_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $enquiry_id);
$stmt->execute();
$result = $stmt->get_result();

// Columns for UI
$columns = [
    "enquiry_id",
    "client_name",
    "contact_person_name",
    "contact_no1",
    "delivered_date",
    "amc_status",
    "amc_date",
    "amc_period",
    "requirement_category"
];

if ($result->num_rows > 0) {
    $data = $result->fetch_assoc();

    $response = [
        "status" => "success",
        "columns" => $columns,
        "data" => $data
    ];
} else {
    $response = [
        "status" => "error",
        "message" => "No AMC record found for given enquiry_id"
    ];
}

echo json_encode($response);

$conn->close();
?>
