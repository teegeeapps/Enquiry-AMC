<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
include 'db.php';

//{
  //"mode": "single",
  //"enquiry_id": "ENQ1001"
//}

// Define the UI columns
$columns = array(
    "client_name",
    "contact_person_name",
    "contact_no_1",
    "requirement_category",
    "delivery_date",
    "amc_date"
);

// Default params
$mode = "all";
$enquiry_id = null;

// Read input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (isset($input['mode'])) {
        $mode = strtolower(trim($input['mode']));
    }
    if (isset($input['enquiry_id'])) {
        $enquiry_id = $conn->real_escape_string($input['enquiry_id']);
    }
} else {
    if (isset($_GET['mode'])) {
        $mode = strtolower(trim($_GET['mode']));
    }
    if (isset($_GET['enquiry_id'])) {
        $enquiry_id = $conn->real_escape_string($_GET['enquiry_id']);
    }
}

// Base query
$sql = "SELECT 
            enquiry_id,
            client_name,
            contact_person_name,
            contact_no1 AS contact_no_1,
            requirement_category,
            delivered_date AS delivery_date,
            amc_date,
		amc_period,
		amc_status
        FROM amc_list";

// Apply mode
if ($mode === "single" && $enquiry_id) {
    $sql .= " WHERE enquiry_id = '$enquiry_id'";
}

$result = $conn->query($sql);

$data = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
    $response = array(
        "status" => "success",
        "mode" => $mode,
        "columns" => $columns,
        "data" => $data
    );
} else {
    $response = array(
        "status" => "No records found",
        "mode" => $mode,
        "columns" => $columns,
        "data" => []
    );
}

echo json_encode($response);
?>
