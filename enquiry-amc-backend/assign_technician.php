<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
include 'db.php';

$data = json_decode(file_get_contents("php://input"), true);
$mode = $_GET['mode'] ?? $data['mode'] ?? '';

$response = ["status" => 0, "message" => "Invalid request", "columns" => [], "data" => []];

try {
    if ($mode === "insert" || $mode === "update") {
        $enquiry_id        = $data['enquiry_id'] ?? null;
        $assignment_type   = strtoupper($data['assignment_type'] ?? '');
        $technicians       = $data['technicians'] ?? [];
        $delivery_instructions = $data['delivery_instructions'] ?? '';
        $customer_location = $data['customer_location'] ?? '';
        $assigned_by       = $data['assigned_by'] ?? null;

        if (!$enquiry_id || !$assignment_type || empty($technicians)) {
            throw new Exception("Missing required fields");
        }

        // AMC validation: ensure delivery date exists
        if ($assignment_type === "AMC") {
            $chk = $conn->prepare("SELECT delivered_date FROM amc_list WHERE enquiry_id = ?");
            $chk->bind_param("i", $enquiry_id);
            $chk->execute();
            $res = $chk->get_result()->fetch_assoc();
            if (empty($res['delivered_date'])) {
                throw new Exception("AMC assignment not allowed without delivery date");
            }
        }

        $conn->begin_transaction();

        if ($mode === "update") {
            $del = $conn->prepare("DELETE FROM enquiry_assignments WHERE enquiry_id=? AND assignment_type=?");
            $del->bind_param("is", $enquiry_id, $assignment_type);
            $del->execute();
        }

        $ins = $conn->prepare("
            INSERT INTO enquiry_assignments 
            (enquiry_id, assignment_type, technician_id, delivery_instructions, customer_location, assigned_by, status) 
            VALUES (?, ?, ?, ?, ?, ?, 1)
        ");

        foreach ($technicians as $tech_id) {
            $ins->bind_param("isissi", $enquiry_id, $assignment_type, $tech_id, $delivery_instructions, $customer_location, $assigned_by);
            $ins->execute();
        }

        $conn->commit();
        $response = ["status" => 1, "message" => "Technician assignment " . ($mode === "insert" ? "saved" : "updated") . " successfully"];

    } elseif ($mode === "fetch") { // Admin view
        $sql = "
            SELECT ea.enquiry_id, ea.assignment_type, 
                   GROUP_CONCAT(e.employee_name SEPARATOR ', ') as technicians,
                   ea.delivery_instructions, ea.customer_location, ea.assigned_by, ea.assigned_at,
                   ea.status
            FROM enquiry_assignments ea
            JOIN employees e ON ea.technician_id = e.id
            GROUP BY ea.enquiry_id, ea.assignment_type, ea.delivery_instructions, ea.customer_location, ea.assigned_by, ea.assigned_at, ea.status
        ";
        $result = $conn->query($sql);

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $row['status'] = ($row['status'] == 1 ? "Active" : "Inactive");
            $data[] = $row;
        }

        $response = [
            "status" => 1,
            "message" => "Assignments fetched successfully",
            "columns" => ["Enquiry ID", "Assignment Type", "Technicians", "Location", "Delivery Instructions", "Assigned By", "Assigned At", "Status"],
            "data" => $data
        ];

    } elseif ($mode === "fetch_by_technician") { // Technician login
        $tech_id = $data['technician_id'] ?? null;
        if (!$tech_id) throw new Exception("Technician ID required");

        $sql = "
            SELECT ea.enquiry_id, ea.assignment_type, 
                   GROUP_CONCAT(e.employee_name SEPARATOR ', ') as technicians,
                   ea.delivery_instructions, ea.customer_location, ea.assigned_by, ea.assigned_at,
                   ea.status
            FROM enquiry_assignments ea
            JOIN employees e ON ea.technician_id = e.id
            WHERE ea.enquiry_id IN (SELECT enquiry_id FROM enquiry_assignments WHERE technician_id = ?)
            GROUP BY ea.enquiry_id, ea.assignment_type, ea.delivery_instructions, ea.customer_location, ea.assigned_by, ea.assigned_at, ea.status
        ";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $tech_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $data = [];
        while ($row = $result->fetch_assoc()) {
            $row['status'] = ($row['status'] == 1 ? "Active" : "Inactive");
            $data[] = $row;
        }

        $response = [
            "status" => 1,
            "message" => "Assignments fetched for technician",
            "columns" => ["Enquiry ID", "Assignment Type", "Technicians", "Location", "Delivery Instructions", "Assigned By", "Assigned At", "Status"],
            "data" => $data
        ];
    }

} catch (Exception $e) {
    $conn->rollback();
    $response = ["status" => 0, "message" => $e->getMessage()];
}

echo json_encode($response);
?>
