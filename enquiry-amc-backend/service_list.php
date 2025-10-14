<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
include 'db.php';

if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "DB Connection failed"]);
    exit();
}

// Read JSON input
$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['mode'])) {
    echo json_encode(["status" => "error", "message" => "Mode is required"]);
    exit();
}

$mode = strtoupper(trim($input['mode']));

// UI Columns for frontend
$ui_columns = [
    "client_name",
    "contact_person_name",
    "contact_no1",
    "service_date",
    "service_status",
    "technician_names"
];

// ------------------------------------------------------------------
// ✅ Helper function to get technician names by service_task_id
// ------------------------------------------------------------------
function getTechniciansForService($conn, $serviceTaskId)
{
    $sql = "
        SELECT e.employee_name
        FROM enquiry_assignments a
        INNER JOIN employees e 
            ON a.technician_employee_id = e.employee_number
        WHERE a.assignment_type = 'SERVICE'
          AND a.service_task_id = ?
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $serviceTaskId);
    $stmt->execute();
    $result = $stmt->get_result();

    $names = [];
    while ($row = $result->fetch_assoc()) {
        $names[] = $row['employee_name'];
    }

    return implode(", ", $names);
}

// ------------------------------------------------------------------
// ✅ SWITCH CASE START
// ------------------------------------------------------------------
switch ($mode) {

    // ----------------- INSERT -----------------
    case "INSERT":
        $stmt = $conn->prepare("
            INSERT INTO service_list 
            (enquiry_id, assignment_id, service_task_id, client_name, contact_person_name, contact_no1, 
             service_status, service_date, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "sssssssss",
            $input['enquiry_id'],
            $input['assignment_id'],
            $input['service_task_id'],
            $input['client_name'],
            $input['contact_person_name'],
            $input['contact_no1'],
            $input['service_status'],
            $input['service_date'],
            $input['created_by']
        );

        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Service inserted successfully",
                "id" => $stmt->insert_id
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        $stmt->close();
        break;

    // ----------------- UPDATE -----------------
    case "UPDATE":
        if (!isset($input['id'])) {
            echo json_encode(["status" => "error", "message" => "Service ID required for update"]);
            exit();
        }

        $stmt = $conn->prepare("
            UPDATE service_list SET 
                client_name=?, contact_person_name=?, contact_no1=?, 
                requirement_category=?, service_status=?, service_date=?, modified_by=? 
            WHERE id=?
        ");
        $stmt->bind_param(
            "sssssssi",
            $input['client_name'],
            $input['contact_person_name'],
            $input['contact_no1'],
            $input['requirement_category'],
            $input['service_status'],
            $input['service_date'],
            $input['modified_by'],
            $input['id']
        );

        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Service updated successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => $stmt->error]);
        }
        $stmt->close();
        break;

    // ----------------- FETCH ALL -----------------
    case "FETCH_ALL":
        $sql = "SELECT * FROM service_list ORDER BY id DESC";
        $result = $conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            // Format date
            if (!empty($row['service_date']) && $row['service_date'] != "0000-00-00") {
                $row['service_date'] = date("d-m-Y", strtotime($row['service_date']));
            }

            // Get technician names (based on service_task_id)
            $row['technician_names'] = "";
            if (!empty($row['service_task_id'])) {
                $row['technician_names'] = getTechniciansForService($conn, $row['service_task_id']);
            }

            $data[] = $row;
        }

        echo json_encode([
            "status" => "success",
            "columns" => $ui_columns,
            "data" => $data
        ]);
        break;

    // ----------------- FETCH ONE -----------------
    case "FETCH_ONE":
        if (!isset($input['id'])) {
            echo json_encode(["status" => "error", "message" => "Service ID required for fetch"]);
            exit();
        }

        $stmt = $conn->prepare("SELECT * FROM service_list WHERE id=?");
        $stmt->bind_param("i", $input['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            if (!empty($row['service_task_id'])) {
                $row['technician_names'] = getTechniciansForService($conn, $row['service_task_id']);
            }
            echo json_encode(["status" => "success", "data" => $row]);
        } else {
            echo json_encode(["status" => "error", "message" => "Service not found"]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(["status" => "error", "message" => "Invalid mode"]);
}

$conn->close();
?>
