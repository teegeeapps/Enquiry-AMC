<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
include 'db.php';

if ($conn->connect_error) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $conn->connect_error
    ]));
}

// Read JSON body
$input = json_decode(file_get_contents("php://input"), true);
$mode = isset($input['mode']) ? strtolower(trim($input['mode'])) : '';

// Determine table name
$table = '';
if ($mode === 'enquiry') {
    $table = "enquiry_status";
} elseif ($mode === 'amc') {
    $table = "amc_status";
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid mode. Use mode=enquiry or mode=amc in JSON body"
    ]);
    exit;
}

// Query
$sql = "SELECT id, status_name FROM $table ORDER BY id ASC";
$result = $conn->query($sql);

$status_list = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $status_list[] = [
            "id" => $row["id"],
            "status_name" => $row["status_name"]
        ];
    }
}

// Response
echo json_encode([
    "status" => "success",
    "mode"   => $mode,
    "data"   => $status_list
]);

$conn->close();
?>