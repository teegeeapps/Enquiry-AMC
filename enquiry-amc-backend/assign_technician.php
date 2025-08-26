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
function fmt_date($dt) {
    return $dt ? date("H:i:s d-m-Y", strtotime($dt)) : null;
}
function is_valid_date($date) {
    $d = DateTime::createFromFormat("Y-m-d", $date);
    return $d && $d->format("Y-m-d") === $date;
}

try {
    $inTransaction = false;

    // ---------------------------
    // INSERT / UPDATE assignment
    // ---------------------------
    if ($mode === 'insert' || $mode === 'update') {
        $enquiry_id            = $data['enquiry_id'] ?? null;
        $assignment_type       = strtoupper(trim($data['assignment_type'] ?? ''));
        $technicians           = $data['technicians'] ?? [];
        $delivery_instructions = $data['delivery_instructions'] ?? '';
        $customer_location     = $data['customer_location'] ?? '';
        $assigned_by           = $data['assigned_by'] ?? 'system';
        $visit_date            = $data['visit_date'] ?? null;
        $tech_status_map       = $data['technician_status'] ?? ($data['tech_status'] ?? []);

        // basic validation
        if (!$enquiry_id || !in_array($assignment_type, ['ENQUIRY','AMC','SERVICE'], true) || empty($technicians)) {
            throw new Exception("Missing required fields: enquiry_id, assignment_type, technicians[]");
        }
        if ($visit_date && !is_valid_date($visit_date)) {
            throw new Exception("Invalid visit_date format. Expected YYYY-MM-DD");
        }

        // AMC/SERVICE must have delivered_date in amc_list
        if ($assignment_type === 'AMC' || $assignment_type === 'SERVICE') {
            $chk = $conn->prepare("SELECT delivered_date FROM amc_list WHERE enquiry_id = ? AND delivered_date IS NOT NULL AND delivered_date <> '' LIMIT 1");
            $chk->bind_param("s", $enquiry_id);
            $chk->execute();
            $r = $chk->get_result();
            if ($r->num_rows === 0) {
                throw new Exception("$assignment_type assignment not allowed: Delivery Date is missing for this enquiry.");
            }
            $chk->close();
        }

        // verify technicians exist and active
        if (count($technicians) === 0) {
            throw new Exception("technicians[] cannot be empty");
        }
        $placeholders = implode(",", array_fill(0, count($technicians), "?"));
        $types = str_repeat("s", count($technicians));
        // prepare dynamic IN query safely
        $v_stmt_sql = "SELECT employee_number FROM employees WHERE employee_number IN ($placeholders) AND role_id = (SELECT id FROM roles WHERE role_name = 'Technician') AND status = 1";
        $v_stmt = $conn->prepare($v_stmt_sql);
        // bind params dynamically
        $bind_names[] = $types;
        foreach ($technicians as $k => $t) { $bind_names[] = $technicians[$k]; }
        // call_user_func_array for mysqli bind_param
        $tmp = [];
        foreach ($bind_names as $key => $value) $tmp[$key] = &$bind_names[$key];
        call_user_func_array([$v_stmt, 'bind_param'], $tmp);
        $v_stmt->execute();
        $vr = $v_stmt->get_result();
        $validTechs = [];
        while ($row = $vr->fetch_assoc()) $validTechs[] = $row['employee_number'];
        $v_stmt->close();
        if (count($validTechs) !== count($technicians)) {
            throw new Exception("One or more technicians are invalid or inactive");
        }

        // Begin transaction
        $conn->begin_transaction();
        $inTransaction = true;

        // If update => delete existing rows for this enquiry+type (we still upsert below but for safety keep this logic)
        if ($mode === 'update') {
            $del = $conn->prepare("DELETE FROM enquiry_assignments WHERE enquiry_id = ? AND assignment_type = ?");
            $del->bind_param("ss", $enquiry_id, $assignment_type);
            $del->execute();
            $del->close();
        }

        // Insert / upsert
        // We bind: enquiry_id (s), assignment_type (s), technician_employee_id (s), delivery_instructions (s),
        // customer_location (s), assigned_by (s), completed_status (i), completed_at (s or NULL)
        // placeholder order must match bind_param order below
        $ins_sql = "
            INSERT INTO enquiry_assignments
              (enquiry_id, assignment_type, technician_employee_id, delivery_instructions, customer_location, assigned_by, assigned_at, completed_status, completed_at, created_at, updated_at, is_active)
            VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, NOW(), NOW(), 1)
            ON DUPLICATE KEY UPDATE
              delivery_instructions = VALUES(delivery_instructions),
              customer_location     = VALUES(customer_location),
              assigned_by           = VALUES(assigned_by),
              assigned_at           = NOW(),
              completed_status      = VALUES(completed_status),
              completed_at          = VALUES(completed_at),
              updated_at            = NOW(),
              is_active             = 1
        ";
        $ins = $conn->prepare($ins_sql);
        if (!$ins) throw new Exception("Prepare failed: " . $conn->error);

        foreach ($technicians as $techEmpNo) {
            $techEmpNo = trim($techEmpNo);
            $completed_status = isset($tech_status_map[$techEmpNo]) ? to_bool_int($tech_status_map[$techEmpNo]) : 0;
            $completed_at = $completed_status ? date("Y-m-d H:i:s") : null; // DB datetime or null

            // bind parameters: 6 strings, 1 int, 1 string (or null) => "ssssssis"
            $ins->bind_param(
                "ssssssis",
                $enquiry_id,
                $assignment_type,
                $techEmpNo,
                $delivery_instructions,
                $customer_location,
                $assigned_by,
                $completed_status,
                $completed_at
            );
            $ins->execute();
            if ($ins->errno) {
                throw new Exception("Insert failed: " . $ins->error);
            }
        }
        $ins->close();

        // visit history
        if (!empty($visit_date)) {
            $vh = $conn->prepare("INSERT INTO enquiry_visit_history (enquiry_id, visit_date, added_by, added_at) VALUES (?, ?, ?, NOW())");
            $vh->bind_param("sss", $enquiry_id, $visit_date, $assigned_by);
            $vh->execute();
            $vh->close();
        }

        $conn->commit();
        $inTransaction = false;

        $response['status'] = "success";
        $response['message'] = ($mode === 'insert' ? "Assignment created" : "Assignment updated") . " successfully";
        echo json_encode($response);
        exit();
    }

    // ---------------------------
    // FETCH (Admin view)
    // ---------------------------
    if ($mode === 'fetch') {
        $sql = "
            SELECT ea.*, q.client_name, q.contact_no1, emp.employee_name
            FROM enquiry_assignments ea
            LEFT JOIN enquiries q ON q.enquiry_id = ea.enquiry_id
            LEFT JOIN employees emp ON emp.employee_number = ea.technician_employee_id
            ORDER BY ea.assigned_at DESC, ea.enquiry_id, ea.assignment_type
        ";
        $res = $conn->query($sql);
        $grouped = [];
        while ($row = $res->fetch_assoc()) {
            $key = $row['enquiry_id'].'|'.$row['assignment_type'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
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
                    "technicians"          => [],
                    "completed_summary"    => "0/0"
                ];
            }
            $grouped[$key]["technicians"][] = [
                "employee_number"  => $row['technician_employee_id'],
                "employee_name"    => $row['employee_name'],
                "completed_status" => (int)$row['completed_status'],
                "completed_at"     => fmt_date($row['completed_at'])
            ];
        }

        $final = [];
        foreach ($grouped as $g) {
            $total = count($g['technicians']);
            $done  = array_sum(array_column($g['technicians'], 'completed_status'));
            $g['completed_summary'] = "{$done}/{$total}";
            $g['technician_names']  = implode(", ", array_column($g['technicians'], 'employee_name'));
            $final[] = $g;
        }

        $response['status'] = "success";
        $response['message'] = "Assignments fetched successfully";
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
                    "my_status"            => 0,
                    "technicians"          => []
                ];
            }
            $tech = [
                "employee_number"  => $row['technician_employee_id'],
                "employee_name"    => $row['employee_name'],
                "completed_status" => (int)$row['completed_status'],
                "completed_at"     => fmt_date($row['completed_at'])
            ];
            if ($row['technician_employee_id'] === $my_emp_no) {
                $grouped[$key]['my_status'] = (int)$row['completed_status'];
            }
            $grouped[$key]['technicians'][] = $tech;
        }

        $response['status'] = "success";
        $response['message'] = "Assignments for technician fetched";
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
                "employee_number"       => $row['technician_employee_id'],
                "employee_name"         => $row['employee_name'],
                "completed_status"      => (int)$row['completed_status'],
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

    // ---------------------------
    // TASK_DETAILS (fetch assignment details by its id)
----------------------
if ($mode === "task_details") {
    $task_id = isset($data['task_id']) ? intval($data['task_id']) : 0;
    if ($task_id <= 0) throw new Exception("Invalid Task ID");

    $tsql = "
        SELECT ea.*, 
               emp.employee_name,
               e.client_name AS customer_name,
               e.contact_no1 AS customer_contact,
               e.email AS customer_email,
               e.product_name,
               e.product_model,
               e.delivery_date
        FROM enquiry_assignments ea
        LEFT JOIN employees emp ON emp.employee_number = ea.technician_employee_id
        LEFT JOIN enquiries e ON e.enquiry_id = ea.enquiry_id
        WHERE ea.id = ?
    ";
    $stmt = $conn->prepare($tsql);
    $stmt->bind_param("i", $task_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $task = $res->fetch_assoc();
    $stmt->close();

    if (!$task) throw new Exception("Task not found");

    // Format lifecycle fields
    $task['created_at']   = fmt_date($task['created_at']);
    $task['updated_at']   = fmt_date($task['updated_at']);
    $task['assigned_at']  = fmt_date($task['assigned_at']);
    $task['completed_at'] = fmt_date($task['completed_at']);

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
