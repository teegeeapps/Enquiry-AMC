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
	"amc_id": "AMC1",
    "client_name": "XYZ Ltd",
    "contact_person_name": "Suresh",
    "contact_no1": "9876501234",
    "delivered_date": "2025-08-20",
    "amc_status": "Renewed",
    "amc_date": "2025-08-21",
    "amc_period": "1 Year",
    "followup_date": "2025-09-10",
    "followup_notes": "Customer wants callback next week",
    "user": "Admin"
}
*/

// Mandatory fields
$enquiry_id          = $input['enquiry_id'] ?? null;
$amc_id          = $input['amc_id'] ?? null;
$client_name         = $input['client_name'] ?? null;
$contact_person_name = $input['contact_person_name'] ?? null;
$contact_no1         = $input['contact_no1'] ?? null;
$delivered_date      = $input['delivered_date'] ?? null;
$amc_status          = $input['amc_status'] ?? null;
$amc_date            = $input['amc_date'] ?? null;
$amc_period          = $input['amc_period'] ?? null;
$followup_date       = $input['followup_date'] ?? null;
$followup_notes      = $input['followup_notes'] ?? null;
$user                = $input['user'] ?? null;

// Check if AMC exists
$checkStmt = $conn->prepare("SELECT id FROM amc_list WHERE amc_id = ?");
$checkStmt->bind_param("s", $amc_id);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    // Update AMC details
    $updateStmt = $conn->prepare("
        UPDATE amc_list 
        SET client_name = ?, 
            contact_person_name = ?, 
            contact_no1 = ?, 
            delivered_date = ?, 
            amc_status = ?, 
            amc_date = ?, 
            amc_period = ?,
            modified_by = ?, 
            modified_at = NOW()
        WHERE amc_id = ?
    ");
    $updateStmt->bind_param(
        "sssssssss",
        $client_name,
        $contact_person_name,
        $contact_no1,
        $delivered_date,
        $amc_status,
        $amc_date,
        $amc_period,
        $user,
        $amc_id
    );

    $success = $updateStmt->execute();

    if ($success) {
        // Insert follow-up if provided
        if (!empty($followup_date) || !empty($followup_notes)) {
            $insertFollowup = $conn->prepare("
                INSERT INTO amc_followups (enquiry_id, followup_date, followup_notes, created_by)
                VALUES (?, ?, ?, ?)
            ");
            $insertFollowup->bind_param("ssss", $enquiry_id, $followup_date, $followup_notes, $user);
            $insertFollowup->execute();
        }

        echo json_encode(["status" => "success", "message" => "AMC updated successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => $conn->error]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "AMC record not found"]);
}

$conn->close();
?>
