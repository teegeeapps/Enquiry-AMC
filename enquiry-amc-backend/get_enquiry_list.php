<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
require 'db.php';

$data = json_decode(file_get_contents("php://input"), true);

$enquiryId    = $data['enquiry_id'] ?? null;
$technicianId = $data['technician_id'] ?? null;
$statusId     = $data['status_id'] ?? null;
$fromDate     = $data['from_date'] ?? null;
$toDate       = $data['to_date'] ?? null;

/**
 * Utility: fetch assigned technicians for an enquiry
 */
function getTechniciansForEnquiry($conn, $enquiryId) {
    $sql = "SELECT 
                etm.technician_employee_id AS employee_id,
                emp.employee_name,
                etm.completed_status,
                etm.assigned_by,
                etm.assigned_at
            FROM enquiry_assignments etm
            INNER JOIN employees emp 
                ON etm.technician_employee_id = emp.employee_number
            WHERE etm.enquiry_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $enquiryId);
    $stmt->execute();
    $result = $stmt->get_result();

    $techs = [];
    while ($row = $result->fetch_assoc()) {
        $techs[] = $row;
    }
    return $techs;
}

if ($enquiryId && !$technicianId) {
    // 🔹 Case 1: Enquiry details + follow-up + technician list
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
                 WHERE enquiry_id = (SELECT id FROM enquiries WHERE enquiry_id = ?)
                 ORDER BY created_at ASC";
        $fStmt = $conn->prepare($fSql);
        $fStmt->bind_param("s", $enquiryId);
        $fStmt->execute();
        $fResult = $fStmt->get_result();

        $followups = [];
        while ($row = $fResult->fetch_assoc()) {
            $followups[] = $row;
        }

        // Fetch technicians list with per-tech status
        $technicians = getTechniciansForEnquiry($conn, $enquiryId);

        $enquiry['followups']   = $followups;
        $enquiry['technicians'] = $technicians;

        echo json_encode([
            "status" => "success",
            "mode"   => "followup_history",
            "data"   => $enquiry
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Enquiry not found."]);
    }

} elseif ($technicianId && !$enquiryId) {
    // 🔹 Case 2: Technician-specific list
    $sql = "SELECT 
                e.enquiry_id, 
                e.client_name, 
                e.contact_person_name, 
                e.contact_no1,
                e.requirement_category,
                e.enquiry_date, 
                s.status_name
            FROM enquiries e
            INNER JOIN enquiry_assignments etm 
                ON e.enquiry_id = etm.enquiry_id AND etm.technician_employee_id = ?
            LEFT JOIN enquiry_status s ON e.enquiry_status_id = s.id
            WHERE e.is_active = 1";

    $params = [$technicianId];
    $types  = "s";

    if ($statusId) {
        $sql .= " AND e.enquiry_status_id = ?";
        $params[] = $statusId;
        $types   .= "i";
    }
    if ($fromDate && $toDate) {
        $sql .= " AND DATE(e.enquiry_date) BETWEEN ? AND ?";
        $params[] = $fromDate;
        $params[] = $toDate;
        $types   .= "ss";
    }

    $sql .= " ORDER BY e.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row['technicians'] = getTechniciansForEnquiry($conn, $row['enquiry_id']);
        $rows[] = $row;
    }

    $columns = ['enquiry_id','client_name','contact_person_name','contact_no1','requirement_category','enquiry_date','status_name','technicians'];

    echo json_encode([
        "status"        => "success",
        "mode"          => "technician_enquiries",
        "technician_id" => $technicianId,
        "filters"       => $data,
        "columns"       => $columns,
        "data"          => $rows
    ]);

} else {
    // 🔹 Case 3: All enquiries with filters
    $sql = "SELECT 
                e.enquiry_id,
                e.client_name,
                e.contact_person_name,
                e.contact_no1,
                e.requirement_category,
                e.enquiry_date,
                s.status_name
            FROM enquiries e
            LEFT JOIN enquiry_status s ON e.enquiry_status_id = s.id
            WHERE e.is_active = 1";

    $params = [];
    $types  = "";

    if ($statusId) {
        $sql .= " AND e.enquiry_status_id = ?";
        $params[] = $statusId;
        $types   .= "i";
    }
    if ($fromDate && $toDate) {
        $sql .= " AND DATE(e.enquiry_date) BETWEEN ? AND ?";
        $params[] = $fromDate;
        $params[] = $toDate;
        $types   .= "ss";
    }

    $sql .= " ORDER BY e.created_at DESC";
    $stmt = $conn->prepare($sql);
    if ($types) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row['technicians'] = getTechniciansForEnquiry($conn, $row['enquiry_id']);
        $rows[] = $row;
    }

    $columns = ['enquiry_id','client_name','contact_person_name','contact_no1','requirement_category','enquiry_date','status_name','technicians'];

    echo json_encode([
        "status"  => "success",
        "mode"    => "all_enquiries",
        "columns" => $columns,
        "data"    => $rows
    ]);
}

$conn->close();
?>
