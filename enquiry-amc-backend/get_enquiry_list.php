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
 * Utility: format date d-m-Y (safe)
 */
function fmt_date($date) {
    return (!empty($date) && $date !== "0000-00-00" && $date !== "0000-00-00 00:00:00")
        ? date("d-m-Y", strtotime($date))
        : null;
}

/**
 * Utility: fetch assigned technicians for an enquiry
 */
function getTechniciansForEnquiry($conn, $enquiryId) {
    $sql = "SELECT 
                etm.technician_employee_id AS employee_id,
                emp.employee_name,
                etm.completed_status,
                etm.assigned_by,
                etm.assigned_at,
                etm.completed_at
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
        $row['assigned_at']  = fmt_date($row['assigned_at']);
        $row['completed_at'] = fmt_date($row['completed_at']);
        $techs[] = $row;
    }
    return $techs;
}

/**
 * Utility: get follow-up history as concatenated string
 */
function getFollowupHistory($conn, $enquiryId) {
    $sql = "
        SELECT 
            CONCAT(
                '[', DATE_FORMAT(f.follow_up_date, '%d-%m-%Y %H:%i'), 
                ' | ', COALESCE(f.created_by, 'Unknown'), 
                ']: ', COALESCE(f.follow_up_notes, '')
            ) AS entry
        FROM enquiry_followups f
        WHERE f.enquiry_id = ?
        ORDER BY f.follow_up_date DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $enquiryId);
    $stmt->execute();
    $result = $stmt->get_result();

    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row['entry']; // ✅ Corrected: use 'entry'
    }

    return implode("\n", $history); // 🔹 Single concatenated string
}

if ($enquiryId && !$technicianId) {
    // 🔹 Case 1: Enquiry details
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
        $enquiry['enquiry_date']     = fmt_date($enquiry['enquiry_date']);
        $enquiry['technicians']      = getTechniciansForEnquiry($conn, $enquiryId);
        $enquiry['followup_history'] = getFollowupHistory($conn, $enquiryId);

        echo json_encode([
            "status" => "success",
            "mode"   => "followup_history",
            "data"   => $enquiry
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Enquiry not found."]);
    }

} elseif ($technicianId && !$enquiryId) {
    // 🔹 Case 2: Technician enquiries
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
        $row['enquiry_date']     = fmt_date($row['enquiry_date']);
        $row['technicians']      = getTechniciansForEnquiry($conn, $row['enquiry_id']);
        $row['followup_history'] = getFollowupHistory($conn, $row['enquiry_id']);
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
    // 🔹 Case 3: All enquiries
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
    if ($types) $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $row['enquiry_date']     = fmt_date($row['enquiry_date']);
        $row['technicians']      = getTechniciansForEnquiry($conn, $row['enquiry_id']);
        $row['followup_history'] = getFollowupHistory($conn, $row['enquiry_id']);
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
