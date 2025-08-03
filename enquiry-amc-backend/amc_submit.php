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
$user = $input['user']; // could be created_by or modified_by

// Check if AMC already exists for this enquiry
$checkStmt = $conn->prepare("SELECT id FROM amc_list WHERE enquiry_id = ?");
$checkStmt->bind_param("s", $enquiry_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    // Update
    $updateStmt = $conn->prepare("
        UPDATE amc_list SET 
            client_name = ?, contact_person_name = ?, contact_no1 = ?, 
            requirement_category = ?, delivered_date = ?, amc_date = ?, 
            amc_period = ?, amc_status = ?, modified_by = ?
        WHERE enquiry_id = ?
    ");
    $updateStmt->bind_param("ssssssssss", 
        $client_name, $contact_person_name, $contact_no1, 
        $requirement_category, $delivered_date, $amc_date, 
        $amc_period, $amc_status, $user, $enquiry_id
    );
    $success = $updateStmt->execute();
} else {
    // Insert
    $insertStmt = $conn->prepare("
        INSERT INTO amc_list (
            enquiry_id, client_name, contact_person_name, contact_no1,
            requirement_category, delivered_date, amc_date, amc_period,
            amc_status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $insertStmt->bind_param("ssssssssss", 
        $enquiry_id, $client_name, $contact_person_name, $contact_no1,
        $requirement_category, $delivered_date, $amc_date, $amc_period,
        $amc_status, $user
    );
    $success = $insertStmt->execute();
}

if ($success) {
    echo json_encode(["status" => "success", "message" => "AMC record saved"]);
} else {
    echo json_encode(["status" => "error", "message" => $conn->error]);
}
?>
