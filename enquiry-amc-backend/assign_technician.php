<?php
require 'db.php';
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);
$mode = $_GET['mode'] ?? ($data['mode'] ?? '');

$response = [
    "status"  => "error",
    "message" => "Invalid request",
    "columns" => [],
    "data"    => []
];

// ---------- Helpers ----------
function to_bool_int($v) {
    if ($v === 1 || $v === '1' || $v === true || $v === 'true' || $v === 'TRUE') return 1;
    return 0;
}

try {

    // ===========================
    // INSERT / UPDATE assignment
    // ===========================
    if ($mode === 'insert' || $mode === 'update') {
        $enquiry_id            = $data['enquiry_id'] ?? null;              // e.g. "EQ003"
        $assignment_type       = strtoupper(trim($data['assignment_type'] ?? '')); // "ENQUIRY" | "AMC"
        $technicians           = $data['technicians'] ?? [];               // array of technician employee_numbers e.g. ["E04","E07"]
        $delivery_instructions = $data['delivery_instructions'] ?? '';
        $customer_location     = $data['customer_location'] ?? '';
        $assigned_by           = $data['assigned_by'] ?? 'system';         // could be employee_number or name
        $visit_date            = $data['visit_date'] ?? null;              // optional, "YYYY-MM-DD"
        // per-tech status map (optional): { "E04":1, "E07":0 }
        $tech_status_map       = $data['technician_status'] ?? ($data['tech_status'] ?? []);

        if (!$enquiry_id || !in_array($assignment_type, ['ENQUIRY','AMC','SERVICE'], true) || empty($technicians)) {
            throw new Exception("Missing required fields: enquiry_iqqd, assignment_type, technicians[]");
        }

// ✅ Validation: AMC and SERVICE must have delivered_date in amc_list
if ($assignment_type === 'AMC' || $assignment_type === 'SERVICE') {
    $chk = $conn->prepare("SELECT delivered_date FROM amc_list WHERE enquiry_id = ? AND delivered_date IS NOT NULL AND delivered_date <> '' LIMIT 1");
    $chk->bind_param("s", $enquiry_id);
    $chk->execute();
    $res = $chk->get_result();

    if ($res->num_rows === 0) {
        throw new Exception("$assignment_type assignment not allowed: Delivery Date is missing for this enquiry.");
    }
}


        $conn->begin_transaction();

       // If update → remove previous rows for this (enquiry_id, type)
if ($mode === 'update') {
    $del = $conn->prepare("DELETE FROM enquiry_assignments WHERE enquiry_id = ? AND assignment_type = ?");
    $del->bind_param("ss", $enquiry_id, $assignment_type);
    $del->execute();
    $del->close();
}

// Insert rows (one per technician), store per-tech completed_status (0/1)
$ins = $conn->prepare("
    INSERT INTO enquiry_assignments 
        (enquiry_id, assignment_type, technician_employee_id, delivery_instructions, customer_location, assigned_by, assigned_at, completed_status, is_active)
    VALUES
        (?, ?, ?, ?, ?, ?, NOW(), ?, 1)
");

foreach ($technicians as $techEmpNo) {
    $techEmpNo = trim($techEmpNo); // technician employee number
    $completed_status = isset($tech_status_map[$techEmpNo]) 
        ? to_bool_int($tech_status_map[$techEmpNo]) 
        : 0;

    // Bind inside loop so each technician gets correct values
    $ins->bind_param(
        "ssssssi",
        $enquiry_id,
        $assignment_type,
        $techEmpNo,
        $delivery_instructions,
        $customer_location,
        $assigned_by,
        $completed_status
    );

    $ins->execute();
}

$ins->close();

        // ✅ Visit history logging (optional)
        if (!empty($visit_date)) {
            $vh = $conn->prepare("INSERT INTO enquiry_visit_history (enquiry_id, visit_date, added_by, added_at) VALUES (?, ?, ?, NOW())");
            $vh->bind_param("sss", $enquiry_id, $visit_date, $assigned_by);
            $vh->execute();
            $vh->close();
        }

        $conn->commit();

        $response['status']  = "success";
        $response['message'] = ($mode === 'insert' ? "Assignment created" : "Assignment updated") . " successfully";
        echo json_encode($response);
        exit();
    }

    // ===========================
    // FETCH (Admin view): all assignments grouped by enquiry+type
    // ===========================
    if ($mode === 'fetch') {
        // Grab all rows per tech and aggregate in PHP to produce: technicians list, completed X/Y, etc.
        $sql = "
            SELECT 
                ea.enquiry_id,
                ea.assignment_type,
                ea.technician_employee_id,
                ea.delivery_instructions,
                ea.customer_location,
                ea.assigned_by,
                ea.assigned_at,
                ea.completed_status,
                q.client_name,
                q.contact_no1,
                emp.employee_name
            FROM enquiry_assignments ea
            LEFT JOIN enquiries q
                   ON q.enquiry_id = ea.enquiry_id
            LEFT JOIN employees emp
                   ON emp.employee_number = ea.technician_employee_id
            ORDER BY ea.assigned_at DESC, ea.enquiry_id, ea.assignment_type
        ";

        $res = $conn->query($sql);
        $grouped = []; // key: enquiry_id|assignment_type
        while ($row = $res->fetch_assoc()) {
            $key = $row['enquiry_id'] . '|' . $row['assignment_type'];
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    "enquiry_id"          => $row['enquiry_id'],
                    "assignment_type"     => $row['assignment_type'],
                    "client_name"         => $row['client_name'],
                    "contact_no1"         => $row['contact_no1'],
                    "delivery_instructions"=> $row['delivery_instructions'],
                    "customer_location"   => $row['customer_location'],
                    "assigned_by"         => $row['assigned_by'],
                    "assigned_at"         => $row['assigned_at'],
                    "technicians"         => [], // array of {employee_number, employee_name, completed_status}
                    "completed_summary"   => "0/0"
                ];
            }
            $grouped[$key]["technicians"][] = [
                "employee_number"  => $row['technician_employee_id'],
                "employee_name"    => $row['employee_name'],
                "completed_status" => (int)$row['completed_status']
            ];
        }
        // compute completed X/Y & a display string of names
        $final = [];
        foreach ($grouped as $g) {
            $total = count($g['technicians']);
            $done  = 0;
            $names = [];
            foreach ($g['technicians'] as $t) {
                if ($t['completed_status'] === 1) $done++;
                $names[] = $t['employee_name'];
            }
            $g['completed_summary'] = "{$done}/{$total}";
            $g['technician_names']  = implode(", ", $names);
            $final[] = $g;
        }

        $response['status']  = "success";
        $response['message'] = "Assignments fetched successfully";
        $response['columns'] = [
         
            "client_name",
            "contact_no1",
            "technician_names",
            "completed_summary",
            "delivery_instructions",
            "customer_location",
            "assigned_at"
        ];
        $response['data']    = array_map(function($g) {
            return [
                "enquiry_id"            => $g['enquiry_id'],
                "assignment_type"       => $g['assignment_type'],
                "client_name"           => $g['client_name'],
                "contact_no1"           => $g['contact_no1'],
                "technician_names"      => $g['technician_names'],
                "completed_summary"     => $g['completed_summary'],
                "delivery_instructions" => $g['delivery_instructions'],
                "customer_location"     => $g['customer_location'],
                "assigned_by"           => $g['assigned_by'],
                "assigned_at"           => $g['assigned_at'],
                // include raw list so FE can render badges or edit forms
                "technicians"           => $g['technicians']
            ];
        }, $final);

        echo json_encode($response);
        exit();
    }

    // ===========================
    // FETCH by Technician (login-based): show my rows + peers
    // ===========================
    if ($mode === 'fetch_by_technician') {
        $my_emp_no = $data['technician_employee_id'] ?? null; // e.g. "E04"
        if (!$my_emp_no) {
            throw new Exception("technician_employee_id is required");
        }

        $sql = "
            SELECT 
                ea.enquiry_id,
                ea.assignment_type,
                ea.technician_employee_id,
                ea.delivery_instructions,
                ea.customer_location,
                ea.assigned_by,
                ea.assigned_at,
                ea.completed_status,
                q.client_name,
                q.contact_no1,
                emp.employee_name
            FROM enquiry_assignments ea
            LEFT JOIN enquiries q
                   ON q.enquiry_id = ea.enquiry_id
            LEFT JOIN employees emp
                   ON emp.employee_number = ea.technician_employee_id
            WHERE EXISTS (
                SELECT 1 
                FROM enquiry_assignments x 
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
    $key = $row['enquiry_id'] . '|' . $row['assignment_type'];
    
    if (!isset($grouped[$key])) {
        $grouped[$key] = [
            "enquiry_id"            => $row['enquiry_id'],
            "assignment_type"       => $row['assignment_type'],
            "client_name"           => $row['client_name'],
            "contact_no1"           => $row['contact_no1'],
            "delivery_instructions" => $row['delivery_instructions'],
            "customer_location"     => $row['customer_location'],
            "assigned_by"           => $row['assigned_by'],
            "assigned_at"           => $row['assigned_at'],
            "my_status"             => 0, // will update below
            "technicians"           => [] // full technician list
        ];
    }

    $tech = [
        "employee_number"  => $row['technician_employee_id'],
        "employee_name"    => $row['employee_name'],
        "completed_status" => (int)$row['completed_status']
    ];

    // If this is the logged-in technician, set my_status separately
    if ($row['technician_employee_id'] === $my_emp_no) {
        $grouped[$key]['my_status'] = (int)$row['completed_status'];
    }

    $grouped[$key]['technicians'][] = $tech;
}


        $response['status']  = "success";
        $response['message'] = "Assignments for technician fetched";
        $response['columns'] = [
            "client_name",
            "contact_no",
            "delivery_instructions",
            "customer_location",
            "assigned_at",
            "my_status",
            "technicians"
        ];
        // format for FE
        $response['data'] = array_values(array_map(function($g) {
            return [
                "enquiry_id"            => $g['enquiry_id'],
                "assignment_type"       => $g['assignment_type'],
                "client_name"           => $g['client_name'],
                "contact_no1"           => $g['contact_no1'],
                "delivery_instructions" => $g['delivery_instructions'],
                "customer_location"     => $g['customer_location'],
                "assigned_by"           => $g['assigned_by'],
                "assigned_at"           => $g['assigned_at'],
                "my_status"             => $g['my_status'],
                "technicians"     => $g['technicians']
            ];
        }, $grouped));

        echo json_encode($response);
        exit();
    }

    // ===========================
    // GET_ENQUIRY: details + existing assignments + visit history + technician list
    // ===========================
    if ($mode === 'get_enquiry') {
        $enquiry_id = $data['enquiry_id'] ?? null;
        if (!$enquiry_id) throw new Exception("enquiry_id is required");

        // enquiry basic
        $es = $conn->prepare("SELECT enquiry_id, client_name, contact_person_name, contact_no1, address FROM enquiries WHERE enquiry_id = ? LIMIT 1");
        $es->bind_param("s", $enquiry_id);
        $es->execute();
        $enquiry = $es->get_result()->fetch_assoc();
        $es->close();

        // all assignments for this enquiry
        $asql = "
            SELECT 
                ea.assignment_type,
                ea.technician_employee_id,
                ea.completed_status,
                ea.delivery_instructions,
                ea.customer_location,
                ea.assigned_by,
                ea.assigned_at,
                emp.employee_name
            FROM enquiry_assignments ea
            LEFT JOIN employees emp
                   ON emp.employee_number = ea.technician_employee_id
            WHERE ea.enquiry_id = ?
            ORDER BY ea.assignment_type, ea.assigned_at DESC
        ";
        $a = $conn->prepare($asql);
        $a->bind_param("s", $enquiry_id);
        $a->execute();
        $ar = $a->get_result();

        $assignments = [
            "ENQUIRY" => [],
            "AMC"     => [],
	"SERVICE" => []
        ];
        while ($row = $ar->fetch_assoc()) {
            $assignments[$row['assignment_type']][] = [
                "employee_number"      => $row['technician_employee_id'],
                "employee_name"        => $row['employee_name'],
                "completed_status"     => (int)$row['completed_status'],
                "delivery_instructions"=> $row['delivery_instructions'],
                "customer_location"    => $row['customer_location'],
                "assigned_by"          => $row['assigned_by'],
                "assigned_at"          => $row['assigned_at']
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
            $visits[] = $row;
        }
        $vh->close();

        // technician list (active)
        $tq = "
            SELECT id AS employee_id, employee_number, employee_name
            FROM employees
            WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Technician')
              AND status = 1
            ORDER BY employee_name ASC
        ";
        $tres = $conn->query($tq);
        $tech_list = [];
        while ($row = $tres->fetch_assoc()) {
            $tech_list[] = $row; // keep both employee_id & employee_number for FE dropdowns
        }

        $response['status']   = "success";
        $response['message']  = "Enquiry details fetched";
        $response['columns']  = [ "employee_number", "technician_name", "completed", "assigned_by", "assigned_at" ];
        $response['data']     = [
            "enquiry"          => $enquiry,
            "assignments"      => $assignments,
            "visit_history"    => $visits,
            "technician_list"  => $tech_list
        ];
        echo json_encode($response);
        exit();
    }

    // ===========================
    // TECH_LIST (standalone, optional)
    // ===========================
    if ($mode === 'tech_list') {
        $tq = "
            SELECT id AS employee_id, employee_number, employee_name
            FROM employees
            WHERE role_id = (SELECT id FROM roles WHERE role_name = 'Technician')
              AND status = 1
            ORDER BY employee_name ASC
        ";
        $tres = $conn->query($tq);
        $list = [];
        while ($row = $tres->fetch_assoc()) {
            $list[] = $row;
        }
        $response['status']  = "success";
        $response['message'] = "Technicians fetched";
        $response['columns'] = ["employee_number", "technician_name"];
        $response['data']    = $list;
        echo json_encode($response);
        exit();
    }

    // Fallback
    throw new Exception("Unsupported mode: $mode");

} catch (Exception $e) {
    if ($conn->errno === 0) { // if we are in a tx, safe to try rollback
        @ $conn->rollback();
    }
    $response['status']  = "error";
    $response['message'] = $e->getMessage();
    echo json_encode($response);
}
