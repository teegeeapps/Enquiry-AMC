<?php
require 'db.php';
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

// Preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$mode = $_GET['mode'] ?? ($data['mode'] ?? '');

// Default response
$response = [
    "status"  => "error",
    "message" => "Invalid request",
    "columns" => [],
    "data"    => []
];

// Helpers
function to_bool_int($v) {
    if ($v === 1 || $v === '1' || $v === true || $v === 'true' || $v === 'TRUE') return 1;
    return 0;
}
//function fmt_date($dt) {
  //  return $dt ? date("H:i:s d-m-Y", strtotime($dt)) : null;
//}
function fmt_date($dt) {
    if (!$dt || $dt == "0000-00-00" || $dt == "0000-00-00 00:00:00") {
        return null; // keep null instead of bad dates
    }
    return date("d-m-Y", strtotime($dt));
}
function is_valid_date($date) {
    $d = DateTime::createFromFormat("Y-m-d", $date);
    return $d && $d->format("Y-m-d") === $date;
}

try {
    $inTransaction = false;

    // ---------------------------
// ---------------------------
// INSERT assignment
// ---------------------------
if ($mode === 'insert') {
    $enquiry_id            = $data['enquiry_id'] ?? null;
    $assignment_type       = strtoupper(trim($data['assignment_type'] ?? ''));
    $technicians           = $data['technicians'] ?? [];
    $delivery_instructions = trim($data['delivery_instructions'] ?? '');
    $customer_location     = trim($data['customer_location'] ?? '');
    $visit_date            = trim($data['visit_date'] ?? '');

    // --- Validations ---
    if (!$enquiry_id) {
        echo json_encode(["status" => "error", "message" => "Missing enquiry_id"]);
        exit();
    }

    $validTypes = ["ENQUIRY", "AMC", "SERVICE"];
    if (!in_array($assignment_type, $validTypes)) {
        echo json_encode(["status" => "error", "message" => "Invalid assignment_type"]);
        exit();
    }

    if (!is_array($technicians) || empty($technicians)) {
        echo json_encode(["status" => "error", "message" => "Technician list required"]);
        exit();
    }

    if ($visit_date && !preg_match("/^\d{4}-\d{2}-\d{2}$/", $visit_date)) {
        echo json_encode(["status" => "error", "message" => "Invalid visit_date format (Y-m-d)"]);
        exit();
    }

    // Restrict AMC/SERVICE unless delivered_date exists
    if (in_array($assignment_type, ["AMC", "SERVICE"])) {
        $chk = $conn->prepare("SELECT delivered_date FROM amc_list WHERE enquiry_id=? LIMIT 1");
        $chk->bind_param("s", $enquiry_id);
        $chk->execute();
        $del = $chk->get_result()->fetch_assoc();
        if (empty($del['delivered_date']) || $del['delivered_date'] === '0000-00-00') {
            echo json_encode(["status" => "error", "message" => "$assignment_type assignment not allowed until delivered_date is set"]);
            exit();
        }
    }

    // Verify all technicians are valid + active (using JOIN correctly)
    $placeholders = implode(",", array_fill(0, count($technicians), "?"));
    $types = str_repeat("s", count($technicians));

    $sql = "
        SELECT COUNT(DISTINCT e.employee_number) AS cnt
        FROM employees e
        JOIN employee_roles er ON e.id = er.employee_id
        JOIN roles r ON er.role_id = r.id
        WHERE e.employee_number IN ($placeholders)
          AND r.role_name = 'Technician'
          AND e.status = 1
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$technicians);
    $stmt->execute();
    $cnt = $stmt->get_result()->fetch_assoc()['cnt'];

    if ($cnt != count($technicians)) {
        echo json_encode(["status" => "error", "message" => "Invalid technician(s)"]);
        exit();
    }

    // Transaction start
    $conn->begin_transaction();
    try {
        // Remove existing uncompleted assignments
        $delq = $conn->prepare("DELETE FROM enquiry_assignments WHERE enquiry_id=? AND assignment_type=? AND completed_status IS NULL");
        $delq->bind_param("ss", $enquiry_id, $assignment_type);
        $delq->execute();

        // Insert new assignments
        $ins = $conn->prepare("
            INSERT INTO enquiry_assignments 
            (enquiry_id, assignment_type, technician_employee_id, delivery_instructions, customer_location, assigned_by, assigned_at, is_active, updated_by, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW(), 1, ?, NOW())
        ");
        foreach ($technicians as $tech) {
            $ins->bind_param("ssssss", $enquiry_id, $assignment_type, $tech, $delivery_instructions, $customer_location, $loggedInUser);
            $ins->execute();
        }

        // Log visit history
        if ($visit_date) {
            $vh = $conn->prepare("
                INSERT INTO enquiry_visit_history 
                (enquiry_id, visit_date, added_by, added_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $vh->bind_param("sss", $enquiry_id, $visit_date, $loggedInUser);
            $vh->execute();
        }

        $conn->commit();

        // Format visit_date for response (d-m-Y)
        $response = [
            "status" => "success",
            "message" => "Assignments inserted",
            "visit_date" => $visit_date ? date("d-m-Y", strtotime($visit_date)) : null
        ];
        echo json_encode($response);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Insert failed: ".$e->getMessage()]);
    }
    exit();
}

// ---------------------------
// FETCH (Admin view - Flat list with completed_summary + technician_names)
// ------------------completed_status---------
if ($mode === 'fetch_admin') {
    $sql = "
        SELECT 
            ea.id AS assignment_id,
            ea.enquiry_id,
            ea.assignment_type,
            ea.delivery_instructions,
            ea.customer_location,
            ea.assigned_by,
            ea.assigned_at,
            ea.created_at,
            ea.updated_at,
            ea.technician_employee_id,
            ea.completed_status,
            ea.completed_at,
            q.client_name,
            q.contact_no1,
            emp.employee_name
        FROM enquiry_assignments ea
        LEFT JOIN enquiries q ON q.enquiry_id = ea.enquiry_id
        LEFT JOIN employees emp ON emp.employee_number = ea.technician_employee_id
        ORDER BY ea.assigned_at DESC, ea.enquiry_id, ea.assignment_type, emp.employee_name
    ";

    $res = $conn->query($sql);

    // Preload team + summary info
    $team_sql = "
        SELECT 
            ea.enquiry_id, ea.assignment_type,
            GROUP_CONCAT(emp.employee_name ORDER BY emp.employee_name SEPARATOR ', ') AS technician_names,
            COUNT(*) AS total,
            SUM(ea.completed_status) AS done
        FROM enquiry_assignments ea
        LEFT JOIN employees emp ON emp.employee_number = ea.technician_employee_id
        GROUP BY ea.enquiry_id, ea.assignment_type
    ";
    $team_res = $conn->query($team_sql);
    $team_map = [];
    while ($t = $team_res->fetch_assoc()) {
        $key = $t['enquiry_id'].'|'.$t['assignment_type'];
        $team_map[$key] = [
            "technician_names"  => $t['technician_names'],
            "completed_summary" => $t['done']."/".$t['total']
        ];
    }

    $final = [];
    while ($row = $res->fetch_assoc()) {
        $key  = $row['enquiry_id'].'|'.$row['assignment_type'];
        $team = $team_map[$key] ?? ["technician_names"=>"","completed_summary"=>"0/0"];

        $final[] = [
            "assignment_id"        => $row['assignment_id'],
            "enquiry_id"           => $row['enquiry_id'],
            "assignment_type"      => $row['assignment_type'],
            "client_name"          => $row['client_name'],
            "contact_no1"          => $row['contact_no1'],
            "delivery_instructions"=> $row['delivery_instructions'],
            "customer_location"    => $row['customer_location'],
            "assigned_by"          => $row['assigned_by'],
            "assigned_at"          => fmt_date($row['assigned_at']),
            "created_at"           => fmt_date($row['created_at']),
            "updated_at"           => fmt_date($row['updated_at']),
            "employee_number"      => $row['technician_employee_id'],
            "employee_name"        => $row['employee_name'],
            "completed_status"     => (string)$row['completed_status'],
            "completed_at"         => fmt_date($row['completed_at']),
            "completed_summary"    => $team['completed_summary'],
            "technician_names"     => $team['technician_names']
        ];
    }

    $response['status'] = "success";
    $response['message'] = "Assignments fetched successfully";
    $response['columns'] = [
        "client_name",
        "contact_no1",
        "employee_name",
        "completed_status",
        "technician_names",
        "delivery_instructions",
        "customer_location",
        "assigned_at"
    ];
    $response['data'] = $final;
    echo json_encode($response);
    exit();
}

// ---------------------------
// FETCH BY TECHNICIAN
// ---------------------------
if ($mode === 'fetch_by_technician') {
    $my_emp_no = $data['technician_employee_id'] ?? null;
    if (!$my_emp_no) throw new Exception("technician_employee_id is required");

    $sql = "
        SELECT ea.*, q.client_name, q.contact_no1, emp.employee_name
        FROM enquiry_assignments ea
        LEFT JOIN enquiries q ON q.enquiry_id = ea.enquiry_id
        LEFT JOIN employees emp ON emp.employee_number = ea.technician_employee_id
        WHERE EXISTS (
            SELECT 1 FROM enquiry_assignments x
            WHERE x.enquiry_id = ea.enquiry_id
              AND x.assignment_type = ea.assignment_type
              AND x.technician_employee_id = ?
        )
        ORDER BY ea.assigned_at DESC, ea.enquiry_id, ea.assignment_type
    ";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $my_emp_no);
    $stmt->execute();
    $res = $stmt->get_result();

    $grouped = [];
    while ($row = $res->fetch_assoc()) {
        $key = $row['enquiry_id'].'|'.$row['assignment_type'];

        if (!isset($grouped[$key])) {
            $grouped[$key] = [
                "assignment_id"        => $row['id'], // primary key
                "enquiry_id"           => $row['enquiry_id'],
                "assignment_type"      => $row['assignment_type'],
                "client_name"          => $row['client_name'],
                "contact_no1"          => $row['contact_no1'],
                "delivery_instructions"=> $row['delivery_instructions'],
                "customer_location"    => $row['customer_location'],
                "assigned_by"          => $row['assigned_by'],
                "assigned_at"          => fmt_date($row['assigned_at']),
                "created_at"           => fmt_date($row['created_at']),
                "updated_at"           => fmt_date($row['updated_at']),
                "my_status"            => "Pending", // default text
                "technicians"          => []
            ];
        }

        $tech = [
            "employee_number"  => $row['technician_employee_id'],
            "employee_name"    => $row['employee_name'],
            "completed_status" => $row['completed_status'], // keep as text ("Pending"/"Completed")
            "completed_at"     => fmt_date($row['completed_at'])
        ];

        // ✅ Set my_status as text instead of int
        if ((string)$row['technician_employee_id'] === (string)$my_emp_no) {
            $grouped[$key]['my_status'] = $row['completed_status'];
        }

        $grouped[$key]['technicians'][] = $tech;
    }

    $response['status'] = "success";
    $response['message'] = "Assignments for technician fetched";
    $response['columns'] = [
        "client_name",
        "contact_no1",
        "my_status",
        "delivery_instructions",
        "customer_location",
        "assigned_at"
    ];
    $response['data'] = array_values($grouped);
    echo json_encode($response);
    exit();
}

    // ---------------------------
    // GET_ENQUIRY (details)
    // ---------------------------
    if ($mode === 'get_enquiry') {
        $enquiry_id = $data['enquiry_id'] ?? null;
        if (!$enquiry_id) throw new Exception("enquiry_id is required");

        // enquiry basic
        $es = $conn->prepare("SELECT enquiry_id, client_name, contact_person_name, contact_no1, address FROM enquiries WHERE enquiry_id = ? LIMIT 1");
        $es->bind_param("s", $enquiry_id);
        $es->execute();
        $enquiry = $es->get_result()->fetch_assoc();
        $es->close();

        // assignments for this enquiry (flat list, each with lifecycle)
        $asql = "
            SELECT ea.*, emp.employee_name
            FROM enquiry_assignments ea
            LEFT JOIN employees emp ON emp.employee_number = ea.technician_employee_id
            WHERE ea.enquiry_id = ?
            ORDER BY ea.assignment_type, ea.assigned_at DESC
        ";
        $a = $conn->prepare($asql);
        $a->bind_param("s", $enquiry_id);
        $a->execute();
        $ar = $a->get_result();

        $assignments = [];
        while ($row = $ar->fetch_assoc()) {
            $assignments[] = [
		"assignment_id"        => $row['id'],
                "employee_number"       => $row['technician_employee_id'],
                "employee_name"         => $row['employee_name'],
                "completed_status"      => $row['completed_status'],
                "completed_at"          => fmt_date($row['completed_at']),
                "delivery_instructions" => $row['delivery_instructions'],
                "customer_location"     => $row['customer_location'],
                "assigned_by"           => $row['assigned_by'],
                "assigned_at"           => fmt_date($row['assigned_at']),
                "created_at"            => fmt_date($row['created_at']),
                "updated_at"            => fmt_date($row['updated_at']),
                "ass_type"              => $row['assignment_type']
            ];
        }
        $a->close();

        // visit history
        $vh = $conn->prepare("SELECT visit_date, added_by, added_at FROM enquiry_visit_history WHERE enquiry_id = ? ORDER BY added_at DESC");
        $vh->bind_param("s", $enquiry_id);
        $vh->execute();
        $vhres = $vh->get_result();
        $visits = [];
        while ($row = $vhres->fetch_assoc()) {
            $row['added_at'] = fmt_date($row['added_at']);
            $visits[] = $row;
        }
        $vh->close();

        // technician list
        $tq = "SELECT id AS employee_id, employee_number, employee_name FROM employees WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Technician') AND status = 1 ORDER BY employee_name ASC";
        $tres = $conn->query($tq);
        $tech_list = [];
        while ($row = $tres->fetch_assoc()) $tech_list[] = $row;

        $response['status'] = "success";
        $response['message'] = "Enquiry details fetched";
        $response['data'] = [
            "enquiry" => $enquiry,
            "assignments" => $assignments,
            "visit_history" => $visits,
            "technician_list" => $tech_list
        ];
        echo json_encode($response);
        exit();
    }

   // TASK_DETAILS (fetch single assignment details by its id)
if ($mode === "task_details") {
    $task_id = isset($data['task_id']) ? intval($data['task_id']) : 0;
    if ($task_id <= 0) throw new Exception("Invalid Task ID");

    $tsql = "SELECT * FROM enquiry_assignments WHERE id = ?";
    $stmt = $conn->prepare($tsql);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $task = $res->fetch_assoc();
    $stmt->close();

    if (!$task) throw new Exception("Task not found");

    // format datetime fields if they exist
    $task['created_at']   = isset($task['created_at'])   ? fmt_date($task['created_at'])   : null;
    $task['updated_at']   = isset($task['updated_at'])   ? fmt_date($task['updated_at'])   : null;
    $task['assigned_at']  = isset($task['assigned_at'])  ? fmt_date($task['assigned_at'])  : null;
    $task['completed_at'] = isset($task['completed_at']) ? fmt_date($task['completed_at']) : null;

    $response['status']  = "success";
    $response['message'] = "Task details fetched";
    $response['data']    = $task;
    echo json_encode($response);
    exit();
}

    // ---------------------------
    // TECH_LIST
    // ---------------------------
    if ($mode === 'tech_list') {
        $tq = "SELECT id AS employee_id, employee_number, employee_name FROM employees WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Technician') AND status = 1 ORDER BY employee_name ASC";
        $tres = $conn->query($tq);
        $list = [];
        while ($row = $tres->fetch_assoc()) $list[] = $row;

        $response['status'] = "success";
        $response['message'] = "Technicians fetched";
        $response['data'] = $list;
        echo json_encode($response);
        exit();
    }

    throw new Exception("Unsupported mode: $mode");

} catch (Exception $e) {
    // rollback if in transaction
    if (!empty($inTransaction)) {
        $conn->rollback();
    }
    $response['status'] = "error";
    $response['message'] = $e->getMessage();
    http_response_code(400);
    echo json_encode($response);
    exit();
}

