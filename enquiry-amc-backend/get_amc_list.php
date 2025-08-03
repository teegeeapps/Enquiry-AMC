<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require 'db.php';
/*
{
    "enquiry_id": "EQ005"
}
*/
$input = json_decode(file_get_contents("php://input"), true);
$enquiry_id = isset($input['enquiry_id']) ? trim($input['enquiry_id']) : '';

if (!$enquiry_id) {
    echo json_encode(['status' => 'error', 'message' => 'Missing enquiry_id']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        enquiry_id, client_name, contact_person_name, contact_no1,
        requirement_category, delivered_date, amc_date, amc_period,
        amc_status, created_by, created_at, modified_by, modified_at
    FROM amc_list
    WHERE enquiry_id = ?
");
$stmt->bind_param("s", $enquiry_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $amc = $result->fetch_assoc();
    echo json_encode(['status' => 'success', 'amc' => $amc]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'AMC not found for this enquiry']);
}

$stmt->close();
$conn->close();
?>
