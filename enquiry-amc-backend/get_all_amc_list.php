<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
include 'db.php';

// Define the UI columns
$columns = array(
    "client_name",
    "contact_person_name",
    "contact_no_1",
    "requirement_category",
    "delivery_date",
    "amc_date"
);

// Query to get AMC list
$sql = "SELECT 
            client_name,
            contact_person_name,
            contact_no1,
            requirement_category,
            delivered_date,
            amc_date
        FROM amc_list";

$result = $conn->query($sql);

$data = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $response = array(
        "status" => "success",
        "columns" => $columns,
        "data" => $data
    );
} else {
    $response = array(
        "status" => "No records found",
        "columns" => $columns,
        "data" => []
    );
}

echo json_encode($response);
?>
