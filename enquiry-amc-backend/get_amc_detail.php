<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require 'db.php';

// Read enquiry_id from request (JSON body or GET param)
$input = json_decode(file_get_contents("php://input"), true);
$enquiry_id = $input['enquiry_id'] ?? ($_GET['enquiry_id'] ?? null);

if (!$enquiry_id) {
    echo json_encode(["status" => "error", "message" => "enquiry_id is required"]);
    exit();
}

// ✅ Fetch AMC Details
$sql = "SELECT enquiry_id, client_name, contact_person_name, contact_no1, 
               delivered_date, amc_status, amc_date, amc_period, requirement_category 
        FROM amc_list 
        WHERE enquiry_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $enquiry_id);
$stmt->execute();
$result = $stmt->get_result();

// Define base response
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

    // ✅ Fetch AMC Follow-Up History
    $followup_sql = "SELECT followup_date, followup_remarks, created_by, created_at
                     FROM amc_followup 
                     WHERE enquiry_id = ?
                     ORDER BY created_at DESC";
    $followup_stmt = $conn->prepare($followup_sql);
    $followup_stmt->bind_param("s", $enquiry_id);
    $followup_stmt->execute();
    $followup_result = $followup_stmt->get_result();

    $followups = [];
    $followup_concat = "";

    while ($row = $followup_result->fetch_assoc()) {
        $followups[] = $row;
        $followup_concat .= $row['followup_date'] . " - " . $row['followup_remarks'] . " (" . $row['created_by'] . ")\n";
    }

    $response = [
        "status" => "success",
        "columns" => $columns,
        "data" => $data,
        "followup_history" => $followups,     // Structured history
        "followup_text" => trim($followup_concat) // Concatenated history
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
