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
    "amc_date",
"amc_status"
);

// Default params
$mode = "all";
$amc_id = null;

// Read input
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    if (isset($input['mode'])) {
        $mode = strtolower(trim($input['mode']));
    }
    if (isset($input['amc_id'])) {
        $amc_id = $conn->real_escape_string($input['amc_id']);
    }
} else {
    if (isset($_GET['mode'])) {
        $mode = strtolower(trim($_GET['mode']));
    }
    if (isset($_GET['amc_id'])) {
        $amc_id = $conn->real_escape_string($_GET['amc_id']);
    }
}

// Base query
$sql = "SELECT 
            enquiry_id,
		amc_id,
            client_name,
            contact_person_name,
            contact_no1 AS contact_no_1,
            requirement_category,
            delivered_date,
            amc_date,
            amc_period,
            amc_status
        FROM amc_list";

// Apply mode
if ($mode === "single" && $amc_id) {
    $sql .= " WHERE amc_id = '$amc_id'";
}

$result = $conn->query($sql);

$data = array();
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format dates safely
        $row['delivery_date'] = (!empty($row['delivered_date']) && $row['delivered_date'] !== "0000-00-00")
            ? date("d-m-Y", strtotime($row['delivered_date']))
            : null;

        $row['amc_date'] = (!empty($row['amc_date']) && $row['amc_date'] !== "0000-00-00")
            ? date("d-m-Y", strtotime($row['amc_date']))
            : null;

        unset($row['delivered_date']); // remove raw db field (optional)

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
