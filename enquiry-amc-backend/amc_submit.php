<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

/*
{
    "enquiry_id": "EQ005",
    "client_name": "ABC Corp",
    "contact_person_name": "Ramesh",
    "contact_no1": "9876543210",
    "requirement_category": "AMC Renewal",
    "delivered_date": "2025-08-01",
    "amc_date": "2025-08-02",
    "amc_period": "1 Year",
    "amc_status": "Active",
    "user": "Admin"
}
*/

$enquiry_id = $input['enquiry_id'];
$client_name = $input['client_name'];
$contact_person_name = $input['contact_person_name'];
$contact_no1 = $input['contact_no1'];
$requirement_category = $input['requirement_category'];
$delivered_date = $input['delivered_date'];
$amc_date = $input['amc_date'];
$amc_period = $input['amc_period'];
$amc_status = $input['amc_status'];
$user = $input['user']; // created_by or modified_by

// -------- Generate Next AMC ID (Global Increment) --------
$lastIdQuery = $conn->query("SELECT amc_id FROM amc_list ORDER BY id DESC LIMIT 1");
if ($lastIdQuery && $lastIdQuery->num_rows > 0) {
    $lastRow = $lastIdQuery->fetch_assoc();
    $lastAmcId = $lastRow['amc_id'];

    // Extract number part from AMC ID
    preg_match('/(\d+)/', $lastAmcId, $matches);
    $nextNumber = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
} else {
    $nextNumber = 1;
}
$newAmcId = "AMC" . $nextNumber;

// -------- Insert New AMC Record --------
$insertStmt = $conn->prepare("
    INSERT INTO amc_list (
        amc_id, enquiry_id, client_name, contact_person_name, contact_no1,
        requirement_category, delivered_date, amc_date, amc_period,
        amc_status, created_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

$insertStmt->bind_param("sssssssssss", 
    $newAmcId, $enquiry_id, $client_name, $contact_person_name, $contact_no1,
    $requirement_category, $delivered_date, $amc_date, $amc_period,
    $amc_status, $user
);

$success = $insertStmt->execute();

if ($success) {
    echo json_encode([
        "status" => "success",
        "message" => "AMC record inserted",
        "amc_id" => $newAmcId
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => $conn->error
    ]);
}
?>
