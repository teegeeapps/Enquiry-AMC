<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require 'db.php';

$input = json_decode(file_get_contents("php://input"), true);

/*
Sample JSON to send:
{
    "enquiry_id": "EQ005",
    "client_name": "XYZ Ltd",
    "contact_person_name": "Suresh",
    "contact_no1": "9876501234",
    "delivered_date": "2025-08-20",
    "amc_status": "Renewed",
    "amc_date": "2025-08-21",
    "user": "Admin"
}
*/

// Mandatory fields
$enquiry_id = $input['enquiry_id'];
$client_name = $input['client_name'];
$contact_person_name = $input['contact_person_name'];
$contact_no1 = $input['contact_no1'];
$delivered_date = $input['delivered_date'] ?? null; // optional
$amc_status = $input['amc_status'];
$amc_date = $input['amc_date'];
$user = $input['user'];

// Check if enquiry exists
$checkStmt = $conn->prepare("SELECT id FROM amc_list WHERE enquiry_id = ?");
$checkStmt->bind_param("s", $enquiry_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    // Update only allowed fields
    $updateStmt = $conn->prepare("
        UPDATE amc_list 
        SET client_name = ?, 
            contact_person_name = ?, 
            contact_no1 = ?, 
            delivered_date = ?, 
            amc_status = ?, 
            amc_date = ?, 
            modified_by = ?, 
            modified_at = NOW()
        WHERE enquiry_id = ?
    ");
    $updateStmt->bind_param(
        "ssssssss",
        $client_name,
        $contact_person_name,
        $contact_no1,
        $delivered_date,
        $amc_status,
        $amc_date,
        $user,
        $enquiry_id
    );

    $success = $updateStmt->execute();

    if ($success) {
        echo json_encode(["status" => "success", "message" => "AMC record updated"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "AMC record not found"]);
}

$conn->close();
?>
