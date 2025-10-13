<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
include 'db.php';

// --------------------------------------------
// Define the UI columns
// --------------------------------------------
$columns = [
    "client_name",
    "contact_person_name",
    "contact_no_1",
    "requirement_category",
    "delivery_date",
    "refilling_date",
    "refilling_status",
    "technician_names"
];

// --------------------------------------------
// Read input
// --------------------------------------------
$mode = "all";
$amc_id = null;

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

// --------------------------------------------
// Helper: Get technician names for an AMC
// --------------------------------------------
function getTechniciansForAmc($conn, $amcId)
{
    $sql = "
        SELECT e.employee_name
        FROM enquiry_assignments a
        INNER JOIN employees e 
            ON a.technician_employee_id = e.employee_number
        WHERE a.assignment_type = 'REFILLING' AND a.enquiry_id IN (
            SELECT enquiry_id FROM amc_list WHERE amc_id = ?
        )
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $amcId);
    $stmt->execute();
    $result = $stmt->get_result();

    $names = [];
    while ($row = $result->fetch_assoc()) {
        $names[] = $row['employee_name'];
    }

    return implode(", ", $names);
}

// --------------------------------------------
// Base query
// --------------------------------------------
$sql = "
    SELECT 
        enquiry_id,
        amc_id,
        client_name,
        contact_person_name,
        contact_no1 AS contact_no_1,
        requirement_category,
        delivered_date,
        refilling_date,
        refilling_period,
        refilling_status
    FROM amc_list
";

// Apply mode
if ($mode === "single" && $amc_id) {
    $sql .= " WHERE amc_id = '$amc_id'";
}

$result = $conn->query($sql);

// --------------------------------------------
// Build response
// --------------------------------------------
$data = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {

        // Format dates safely
        $row['delivery_date'] = (!empty($row['delivered_date']) && $row['delivered_date'] !== "0000-00-00")
            ? date("d-m-Y", strtotime($row['delivered_date']))
            : null;

        $row['refilling_date'] = (!empty($row['refilling_date']) && $row['refilling_date'] !== "0000-00-00")
            ? date("d-m-Y", strtotime($row['refilling_date']))
            : null;

        unset($row['delivered_date']); // hide DB raw field

        // 🔹 Fetch all assigned technician names for this AMC
        $row['technician_names'] = getTechniciansForAmc($conn, $row['amc_id']);

        $data[] = $row;
    }

    $response = [
        "status" => "success",
        "mode" => $mode,
        "columns" => $columns,
        "data" => $data
    ];
} else {
    $response = [
        "status" => "No records found",
        "mode" => $mode,
        "columns" => $columns,
        "data" => []
    ];
}

echo json_encode($response);
$conn->close();
?>
