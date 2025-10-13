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

$mode = strtoupper($input['mode']);
$ui_columns = [
    "client_name",
    "contact_person_name",
    "contact_no1",
    "service_date",
    "service_status",
    "technician_names"
];

switch ($mode) {

    // ----------------- INSERT -----------------
    case "INSERT":
        $stmt = $conn->prepare("
            INSERT INTO service_list 
                (enquiry_id, service_task_id, client_name, contact_person_name, contact_no1, service_status, service_date, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssssssss",
            $input['enquiry_id'],
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
            UPDATE service_list 
            SET client_name=?, contact_person_name=?, contact_no1=?, service_status=?, service_date=?, modified_by=? 
            WHERE id=?
        ");
        $stmt->bind_param(
            "ssssssi",
            $input['client_name'],
            $input['contact_person_name'],
            $input['contact_no1'],
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
        $sql = "
            SELECT 
                s.id,
                s.enquiry_id,
                s.service_task_id,
                s.client_name,
                s.contact_person_name,
                s.contact_no1,
                s.service_status,
                s.service_date,
                s.created_by,
                s.modified_by,
                IFNULL(
                    (
                        SELECT GROUP_CONCAT(e.employee_name SEPARATOR ', ')
                        FROM enquiry_assignments ea
                        INNER JOIN employees e 
                            ON ea.technician_employee_id = e.employee_number
                        WHERE ea.service_task_id = s.service_task_id
                    ),
                    ''
                ) AS technician_names
            FROM service_list s
            ORDER BY s.id DESC
        ";

        $result = $conn->query($sql);

        $data = [];
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                // Format date safely
                $row['service_date'] = (!empty($row['service_date']) && $row['service_date'] !== "0000-00-00")
                    ? date("d-m-Y", strtotime($row['service_date']))
                    : null;
                $data[] = $row;
            }
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

        $stmt = $conn->prepare("
            SELECT 
                s.*, 
                IFNULL(
                    (
                        SELECT GROUP_CONCAT(e.employee_name SEPARATOR ', ')
                        FROM enquiry_assignments ea
                        INNER JOIN employees e 
                            ON ea.technician_employee_id = e.employee_number
                        WHERE ea.service_task_id = s.service_task_id
                    ),
                    ''
                ) AS technician_names
            FROM service_list s
            WHERE s.id=?
        ");
        $stmt->bind_param("i", $input['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row) {
            $row['service_date'] = (!empty($row['service_date']) && $row['service_date'] !== "0000-00-00")
                ? date("d-m-Y", strtotime($row['service_date']))
                : null;
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
