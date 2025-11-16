<?php
require 'db.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

$input = json_decode(file_get_contents("php://input"), true);
$enquiryId = $input['enquiry_id'] ?? null;
$technicianId = $input['technician_id'] ?? null;

try {
    if ($enquiryId && !$technicianId) {
        // 👁‍🗨 Case 1: Fetch full follow-up history for ONE enquiry
        $sql = "SELECT * FROM follow_ups WHERE enquiry_id = :enquiry_id ORDER BY created_at ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':enquiry_id', $enquiryId);
        $stmt->execute();

        echo json_encode([
            "status" => "success",
            "mode" => "followup_history",
            "enquiry_id" => $enquiryId,
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);

    } elseif ($technicianId && !$enquiryId) {
        // 👷 Case 2: Fetch all enquiries assigned to technician (with latest follow-up per enquiry)
        $sql = "
        SELECT 
            e.enquiry_id,
            e.client_name,
            e.contact_person_name,
            e.contact_no1,
            e.enquiry_date,
            e.enquiry_status_id,
            s.status_name,
            f.follow_up_date,
            f.follow_up_notes
        FROM enquiry e
        INNER JOIN enquiry_technician_map etm ON e.enquiry_id = etm.enquiry_id
        LEFT JOIN (
            SELECT f1.*
            FROM follow_ups f1
            INNER JOIN (
                SELECT enquiry_id, MAX(created_at) AS max_time
                FROM follow_ups
                GROUP BY enquiry_id
            ) f2 ON f1.enquiry_id = f2.enquiry_id AND f1.created_at = f2.max_time
        ) f ON e.enquiry_id = f.enquiry_id
        LEFT JOIN enquiry_status_list s ON e.enquiry_status_id = s.id
        WHERE etm.technician_id = :technician_id AND e.status = 1
        ORDER BY e.created_at DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':technician_id', $technicianId);
        $stmt->execute();

        echo json_encode([
            "status" => "success",
            "mode" => "technician_enquiries",
            "technician_id" => $technicianId,
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);

    } else {
        // 🧑‍💼 Case 3: Admin/Manager: Fetch all enquiries with latest follow-up
        $sql = "
        SELECT 
            e.enquiry_id,
            e.client_name,
            e.contact_person_name,
            e.contact_no1,
            e.enquiry_date,
            e.enquiry_status_id,
            s.status_name,
            f.follow_up_date,
            f.follow_up_notes
        FROM enquiry e
        LEFT JOIN (
            SELECT f1.*
            FROM follow_ups f1
            INNER JOIN (
                SELECT enquiry_id, MAX(created_at) AS max_time
                FROM follow_ups
                GROUP BY enquiry_id
            ) f2 ON f1.enquiry_id = f2.enquiry_id AND f1.created_at = f2.max_time
        ) f ON e.enquiry_id = f.enquiry_id
        LEFT JOIN enquiry_status_list s ON e.enquiry_status_id = s.id
        WHERE e.status = 1
        ORDER BY e.created_at DESC
        ";
        $stmt = $conn->prepare($sql);
        $stmt->execute();

        echo json_encode([
            "status" => "success",
            "mode" => "all_enquiries",
            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Query failed: " . $e->getMessage()
    ]);
}
