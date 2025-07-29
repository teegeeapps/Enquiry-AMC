<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$enquiryId = $data['enquiry_id'] ?? null;
$technicianId = $data['technician_id'] ?? null;
$statusId = $data['status_id'] ?? null;
$fromDate = $data['from_date'] ?? null;
$toDate = $data['to_date'] ?? null;

if ($enquiryId && !$technicianId) {
    // 🔹 Case 1: Get enquiry with follow-up history
    $sql = "SELECT e.*, s.status_name
            FROM enquiries e
            LEFT JOIN enquiry_status s ON e.enquiry_status_id = s.id
            WHERE e.enquiry_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $enquiryId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $enquiry = $result->fetch_assoc();

        // Fetch follow-ups
        $fSql = "SELECT follow_up_date, follow_up_notes, created_at
                 FROM enquiry_followups
                 WHERE enquiry_id = (
                     SELECT id FROM enquiries WHERE enquiry_id = ?
                 ) ORDER BY created_at ASC";
        $fStmt = $conn->prepare($fSql);
        $fStmt->bind_param("s", $enquiryId);
        $fStmt->execute();
        $fResult = $fStmt->get_result();

        $followups = [];
        while ($row = $fResult->fetch_assoc()) {
            $followups[] = $row;
        }

        $enquiry['followups'] = $followups;

        echo json_encode([
            "status" => "success",
            "mode" => "followup_history",
            "data" => $enquiry
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Enquiry not found."]);
    }

} elseif ($technicianId && !$enquiryId) {
    // 🔹 Case 2: Technician-specific list
    $sql = "
        SELECT 
            e.enquiry_id, 
            e.client_name, 
            e.contact_person_name, 
            e.contact_no1,
            e.enquiry_date, 
            e.enquiry_status_id, 
            s.status_name,
            emp.employee_name AS technician_name,
            etm.assigned_by, 
            etm.assigned_date,
            f.follow_up_date, 
            f.follow_up_notes
        FROM enquiries e
        INNER JOIN enquiry_assignments etm 
            ON e.enquiry_id = etm.enquiry_id AND etm.technician_employee_id = ?
        INNER JOIN employees emp 
            ON etm.technician_employee_id = emp.employee_number
        LEFT JOIN (
            SELECT f1.*
            FROM enquiry_followups f1
            INNER JOIN (
                SELECT enquiry_id, MAX(created_at) AS max_time
                FROM enquiry_followups
                GROUP BY enquiry_id
            ) f2 ON f1.enquiry_id = f2.enquiry_id AND f1.created_at = f2.max_time
        ) f ON e.id = f.enquiry_id
        LEFT JOIN enquiry_status s 
            ON e.enquiry_status_id = s.id
        WHERE e.is_active = 1
    ";

    $params = [$technicianId];
    $types = "s";

    if ($statusId) {
        $sql .= " AND e.enquiry_status_id = ?";
        $params[] = $statusId;
        $types .= "i";
    }
    if ($fromDate && $toDate) {
        $sql .= " AND DATE(e.enquiry_date) BETWEEN ? AND ?";
        $params[] = $fromDate;
        $params[] = $toDate;
        $types .= "ss";
    }

    $sql .= " ORDER BY e.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode([
        "status" => "success",
        "mode" => "technician_enquiries",
        "technician_id" => $technicianId,
        "filters" => $data,
        "data" => $result->fetch_all(MYSQLI_ASSOC)
    ]);

} else {
    // 🔹 Case 3: All enquiries with filters
    $sql = "
        SELECT 
            e.enquiry_id,
            e.client_name,
            e.contact_person_name,
            e.contact_no1,
            emp.employee_name AS technician_name,
            s.status_name
        FROM enquiries e
        LEFT JOIN enquiry_assignments etm ON e.enquiry_id = etm.enquiry_id
        LEFT JOIN employees emp ON etm.technician_employee_id = emp.employee_number
        LEFT JOIN enquiry_status s ON e.enquiry_status_id = s.id
        WHERE e.is_active = 1
    ";

    $params = [];
    $types = "";

    if ($statusId) {
        $sql .= " AND e.enquiry_status_id = ?";
        $params[] = $statusId;
        $types .= "i";
    }
    if ($fromDate && $toDate) {
        $sql .= " AND DATE(e.enquiry_date) BETWEEN ? AND ?";
        $params[] = $fromDate;
        $params[] = $toDate;
        $types .= "ss";
    }

    $sql .= " ORDER BY e.created_at DESC";
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    echo json_encode([
        "status" => "success",
        "mode" => "all_enquiries",
        "data" => $result->fetch_all(MYSQLI_ASSOC)
    ]);
}

$conn->close();
?>
