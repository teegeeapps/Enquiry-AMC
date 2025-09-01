<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require 'db.php';

// ✅ Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ✅ Read JSON input
$input = json_decode(file_get_contents("php://input"), true);
$assignment_id = $input['assignment_id'] ?? ($_GET['assignment_id'] ?? null);

if (!$assignment_id) {
    echo json_encode(["status" => "error", "message" => "assignment_id is required"]);
    exit();
}

// ✅ Check if record exists
$check_sql = "SELECT * FROM enquiry_assignment WHERE id = ?";
$stmt = $conn->prepare($check_sql);
$stmt->bind_param("i", $assignment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(["status" => "error", "message" => "No record found with given ID"]);
    exit();
}

// ✅ Delete the record
$delete_sql = "DELETE FROM enquiry_assignment WHERE id = ?";
$delete_stmt = $conn->prepare($delete_sql);
$delete_stmt->bind_param("i", $assignment_id);

if ($delete_stmt->execute()) {
    echo json_encode(["status" => "success", "message" => "Assignment deleted successfully"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to delete assignment"]);
}

$delete_stmt->close();
$stmt->close();
$conn->close();
?>
