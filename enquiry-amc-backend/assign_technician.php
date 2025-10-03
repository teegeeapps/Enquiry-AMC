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
    $enquiry_id        = trim($data['enquiry_id'] ?? '');
    $assignment_type   = strtoupper(trim($data['assignment_type'] ?? ''));
    $technicians       = $data['technicians'] ?? [];
    $delivery_instructions = trim($data['delivery_instructions'] ?? '');
    $customer_location = trim($data['customer_location'] ?? '');
    $visit_date        = trim($data['visit_date'] ?? '');
    $assigned_by       = trim($data['assigned_by'] ?? 'admin');

    // Task IDs passed from request (may be null)
    $enq_task_id_req     = $data['enq_task_id'] ?? null;
    $amc_task_id_req     = $data['amc_task_id'] ?? null;
    $service_task_id_req = $data['service_task_id'] ?? null;

    // --- Validations ---
    if (!$enquiry_id) {
        echo json_encode(["status" => "error", "message" => "Missing enquiry_id"]);
        exit();
    }

    $validTypes = ["ENQUIRY", "REFILLING", "SERVICE"];
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
    if (in_array($assignment_type, ["REFILLING", "SERVICE"])) {
        $chk = $conn->prepare("SELECT delivered_date FROM amc_list WHERE enquiry_id=? LIMIT 1");
        $chk->bind_param("s", $enquiry_id);
        $chk->execute();
        $del = $chk->get_result()->fetch_assoc();
        if (empty($del['delivered_date'])) {
            echo json_encode(["status" => "error", "message" => "$assignment_type assignment not allowed until delivered_date is set"]);
            exit();
        }
    }

    // Verify all technicians are valid + active using role_id=3
    $placeholders = implode(",", array_fill(0, count($technicians), "?"));
    $types = str_repeat("s", count($technicians));
    $verify_sql = "SELECT COUNT(*) AS cnt FROM employees WHERE employee_number IN ($placeholders) AND status=1 AND role_id=3";
    $verify = $conn->prepare($verify_sql);
    $verify->bind_param($types, ...$technicians);
    $verify->execute();
    $cnt = $verify->get_result()->fetch_assoc()['cnt'];
    if ($cnt != count($technicians)) {
        echo json_encode(["status" => "error", "message" => "Invalid technician(s)"]);
        exit();
    }

    // Transaction start
    $conn->begin_transaction();
    try {
        // Remove existing uncompleted assignments of same type
        $delq = $conn->prepare("DELETE FROM enquiry_assignments WHERE enquiry_id=? AND assignment_type=? AND completed_status IS NULL");
        $delq->bind_param("ss", $enquiry_id, $assignment_type);
        $delq->execute();

        // Insert statement
        $ins = $conn->prepare("
            INSERT INTO enquiry_assignments (
                enquiry_id,
                assignment_type,
                technician_employee_id,
                delivery_instructions,
                customer_location,
                assigned_by,
                assigned_on,
                is_active,
                updated_by,
                updated_at,
                completed_status,
                enq_task_id,
                amc_task_id,
                service_task_id
            ) VALUES (?, ?, ?, ?, ?, ?, NOW(), 1, ?, NOW(), 1, ?, ?, ?)
        ");

        $generated_ids = [];

        foreach ($technicians as $tech) {
            // --- Generate unique task ID per technician ---
            if ($assignment_type === "ENQUIRY") {
                $res = $conn->query("SELECT enq_task_id FROM enquiry_assignments WHERE enq_task_id IS NOT NULL ORDER BY id DESC LIMIT 1");
                $last = $res->fetch_assoc();
                $nextNum = $last ? (intval(substr($last['enq_task_id'], 2)) + 1) : 1;
                $enq_task_id = "ET" . $nextNum;
                $amc_task_id = null;
                $service_task_id = null;
            } elseif ($assignment_type === "REFILLING") {
                $res = $conn->query("SELECT amc_task_id FROM enquiry_assignments WHERE amc_task_id IS NOT NULL ORDER BY id DESC LIMIT 1");
                $last = $res->fetch_assoc();
                $nextNum = $last ? (intval(substr($last['amc_task_id'], 3)) + 1) : 1;
                $amc_task_id = "AMC" . $nextNum;
                $enq_task_id = $enq_task_id_req;
                $service_task_id = null;
            } elseif ($assignment_type === "SERVICE") {
                $res = $conn->query("SELECT service_task_id FROM enquiry_assignments WHERE service_task_id IS NOT NULL ORDER BY id DESC LIMIT 1");
                $last = $res->fetch_assoc();
                $nextNum = $last ? (intval(substr($last['service_task_id'], 2)) + 1) : 1;
                $service_task_id = "ST" . $nextNum;
                $enq_task_id = $enq_task_id_req;
                $amc_task_id = $amc_task_id_req;
            }

            // --- Bind & insert row ---
            $ins->bind_param(
                "ssssssssss",
                $enquiry_id,
                $assignment_type,
                $tech,
                $delivery_instructions,
                $customer_location,
                $assigned_by,
                $assigned_by,   // updated_by
                $enq_task_id,
                $amc_task_id,
                $service_task_id
            );
            $ins->execute();

            // Collect for response
            $generated_ids[] = [
                "technician"     => $tech,
                "enq_task_id"    => $enq_task_id,
                "amc_task_id"    => $amc_task_id,
                "service_task_id"=> $service_task_id
            ];
        }

        // Log visit history if provided
        if ($visit_date) {
            $vh = $conn->prepare("INSERT INTO enquiry_visit_history (enquiry_id, visit_date, added_by, added_at) VALUES (?, ?, ?, NOW())");
            $vh->bind_param("sss", $enquiry_id, $visit_date, $assigned_by);
            $vh->execute();
        }

        $conn->commit();
        echo json_encode([
            "status" => "success",
            "message" => "Assignments inserted",
            "generated_task_ids" => $generated_ids
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Insert failed: ".$e->getMessage()]);
    }
    exit();
}
// ---------------------------
// UPDATE ASSIGNMENTS (with technician status)
// ---------------------------
if ($mode === 'update') {
    $enquiry_id            = $data['enquiry_id'] ?? null;
$assignment_id            = $data['id'] ?? null;
    $assignment_type       = strtoupper(trim($data['assignment_type'] ?? ''));
    $technicians           = $data['technicians'] ?? [];
    $delivery_instructions = trim($data['delivery_instructions'] ?? '');
    $customer_location     = trim($data['customer_location'] ?? '');
    $visit_date            = trim($data['visit_date'] ?? '');
    $technician_status     = $data['technician_status'] ?? []; // e.g. { "E002": 1 }

    if (!$assignment_id || !is_array($technicians)) {
        echo json_encode(["status" => "error", "message" => "Missing required fields"]);
        exit();
    }

    $conn->begin_transaction();
    try {
        // Soft delete old unselected technicians (only those with NULL completed_status)
        if (!empty($technicians)) {
            $placeholders = implode(",", array_fill(0, count($technicians), "?"));
            $del = $conn->prepare("
                DELETE FROM enquiry_assignments 
                WHERE id=? AND assignment_type=? 
                AND completed_status IS NULL
                AND technician_employee_id NOT IN ($placeholders)
            ");
            $types = "ss" . str_repeat("s", count($technicians));
            $params = array_merge([$assignment_id, $assignment_type], $technicians);
            $del->bind_param($types, ...$params);
            $del->execute();
        }

        // Insert or update assignments
        foreach ($technicians as $tech) {
            $completed_status = isset($technician_status[$tech]) ? (int)$technician_status[$tech] : null;

            // Check if record exists
            $check = $conn->prepare("
                SELECT id FROM enquiry_assignments 
                WHERE id=? AND assignment_type=? AND technician_employee_id=? LIMIT 1
            ");
            $check->bind_param("sss", $assignment_id, $assignment_type, $tech);
            $check->execute();
            $row = $check->get_result()->fetch_assoc();

            if ($row) {
                // Update existing assignment
                $upd = $conn->prepare("
                    UPDATE enquiry_assignments 
                    SET delivery_instructions=?, customer_location=?, completed_status=?, 
                        completed_at=IFNULL(completed_at, IF(? IS NOT NULL, NOW(), NULL)),
                        updated_by='admin', updated_at=NOW()
                    WHERE id=?
                ");
                $upd->bind_param("ssisi", $delivery_instructions, $customer_location, $completed_status, $completed_status, $assignment_id);
                $upd->execute();
if ($completed_status === 1) {
    $updateService = $conn->prepare("
        UPDATE service_list 
        SET service_status='Completed', modified_by='admin', modified_at=NOW()
        WHERE enquiry_id = ?
    ");
    $updateService->bind_param("s", $enquiry_id);
    $updateService->execute();
}

            } else {
                // Insert new assignment
                $ins = $conn->prepare("
                    INSERT INTO enquiry_assignments 
                    (enquiry_id, assignment_type, technician_employee_id, delivery_instructions, customer_location, 
                     completed_status, completed_at, assigned_by, assigned_on, is_active, updated_by, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, IF(? IS NOT NULL, NOW(), NULL), ?, NOW(), 1, 'admin', NOW())
                ");
                $ins->bind_param("ssssisss", $enquiry_id, $assignment_type, $tech, $delivery_instructions, 
                                 $customer_location, $completed_status, $completed_status, $loggedInUser);
                $ins->execute();
if ($completed_status === 1) {
    $updateService = $conn->prepare("
        UPDATE service_list 
        SET service_status='Completed', modified_by='admin', modified_at=NOW()
        WHERE enquiry_id = ?
    ");
    $updateService->bind_param("s", $enquiry_id);
    $updateService->execute();
}
            }
        }

        // Log visit history if visit_date provided
        if ($visit_date) {
            $vh = $conn->prepare("
                INSERT INTO enquiry_visit_history (enquiry_id, added_by, visit_date, created_at) 
                VALUES (?, ?, ?, NOW())
            ");
            $vh->bind_param("sss", $enquiry_id, 'admin', $visit_date);
            $vh->execute();
        }

        $conn->commit();
        echo json_encode(["status" => "success", "message" => "Assignments updated with technician status"]);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "Update failed", "error" => $e->getMessage()]);
    }
    exit();
}
// ---------------------------
// FETCH assignments (with d-m-Y date format)
// ---------------------------
if ($mode === 'fetch_detail') {
    $enquiry_id      = $data['enquiry_id'] ?? null;
    $assignment_type = strtoupper(trim($data['assignment_type'] ?? ''));

    $result = [];
    $sql = "SELECT 
                ea.id AS assignment_id,
                ea.enquiry_id,
		ea.enq_task_id,
	    	ea.amc_task_id,
	    	ea.service_task_id,
                ea.assignment_type,
                ea.technician_employee_id,
                t.employee_name AS technician_name,
                ea.assigned_by,
                ea.delivery_instructions,
                ea.customer_location,
                DATE_FORMAT(ea.assigned_on, '%d-%m-%Y') AS assigned_on,
                ea.completed_status,
                DATE_FORMAT(ea.completed_at, '%d-%m-%Y') AS completed_at,
                ea.remarks,
                DATE_FORMAT(ea.updated_at, '%d-%m-%Y') AS updated_at,
                CASE WHEN ea.updated_by='technician' THEN 'technician_update' ELSE 'admin_update' END AS last_update_source
            FROM enquiry_assignments ea
            LEFT JOIN employees t ON ea.technician_employee_id = t.employee_number
            WHERE ea.enquiry_id=? AND ea.assignment_type=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $enquiry_id, $assignment_type);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $result[] = $row;
    }

    echo json_encode(["status" => "success", "data" => $result]);
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
	    ea.enq_task_id,
	    ea.amc_task_id,
	    ea.service_task_id,
            ea.assignment_type,
            ea.delivery_instructions,
            ea.customer_location,
            ea.assigned_by,
            ea.assigned_on,
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
        ORDER BY ea.assigned_on DESC, ea.enquiry_id, ea.assignment_type, emp.employee_name
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
	"enq_task_id"      => $row['enq_task_id'],
	"amc_task_id"      => $row['amc_task_id'],
	"service_task_id"      => $row['service_task_id'],
            "client_name"          => $row['client_name'],
            "contact_no1"          => $row['contact_no1'],
            "delivery_instructions"=> $row['delivery_instructions'],
            "customer_location"    => $row['customer_location'],
            "assigned_by"          => $row['assigned_by'],
            "assigned_on"          => fmt_date($row['assigned_on']),
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
        "assignment_type",
        "customer_location",
        "assigned_on"
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
        ORDER BY ea.assigned_on DESC, ea.enquiry_id, ea.assignment_type
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
		"enq_task_id"      => $row['enq_task_id'],
		"amc_task_id"      => $row['amc_task_id'],
		"service_task_id"      => $row['service_task_id'],

                "client_name"          => $row['client_name'],
                "contact_no1"          => $row['contact_no1'],
                "delivery_instructions"=> $row['delivery_instructions'],
                "customer_location"    => $row['customer_location'],
                "assigned_by"          => $row['assigned_by'],
                "assigned_on"          => fmt_date($row['assigned_on']),
                "created_at"           => fmt_date($row['created_at']),
                "updated_at"           => fmt_date($row['updated_at']),
                "completed_status"     => (string)$row['completed_status'],
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
      //  if ((string)$row['technician_employee_id'] === (string)$my_emp_no) {
        //    $grouped[$key]['my_status'] = $row['completed_status'];
        //}

        $grouped[$key]['technicians'][] = $tech;
    }

    $response['status'] = "success";
    $response['message'] = "Assignments for technician fetched";
    $response['columns'] = [
        "client_name",
        "contact_no1",
        "completed_status",
        "assignment_type",
        "customer_location",
        "assigned_on"
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
            ORDER BY ea.assignment_type, ea.assigned_on DESC
        ";
        $a = $conn->prepare($asql);
        $a->bind_param("s", $enquiry_id);
        $a->execute();
        $ar = $a->get_result();

        $assignments = [];
        while ($row = $ar->fetch_assoc()) {
            $assignments[] = [
		"assignment_id"        => $row['id'],
		"enq_task_id"      => $row['enq_task_id'],
		"amc_task_id"      => $row['amc_task_id'],
		"service_task_id"      => $row['service_task_id'],

                "employee_number"       => $row['technician_employee_id'],
                "employee_name"         => $row['employee_name'],
                "completed_status"      => $row['completed_status'],
                "completed_at"          => fmt_date($row['completed_at']),
                "delivery_instructions" => $row['delivery_instructions'],
                "customer_location"     => $row['customer_location'],
                "assigned_by"           => $row['assigned_by'],
                "assigned_on"           => fmt_date($row['assigned_on']),
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
    $task['assigned_on']  = isset($task['assigned_on'])  ? fmt_date($task['assigned_on'])  : null;
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

