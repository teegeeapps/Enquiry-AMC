<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require 'db.php';

// Handle preflight (OPTIONS) request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

// Accept contact number & password
$contact_no = $data['contact_no'] ?? '';
$password   = $data['password'] ?? '';

if (empty($contact_no) || empty($password)) {
    echo json_encode([
        "status" => "error",
        "message" => "Contact number and password are required"
    ]);
    exit;
}

// Fetch user by contact number
$stmt = $conn->prepare("SELECT * FROM employees WHERE contact_no = ?");
$stmt->bind_param("s", $contact_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Password check (plain-text for now; replace with password_verify() if hashed)
    if ($password === $user['password']) {
        $token = base64_encode("dummy_token_" . $user['employee_number']);

        // ✅ Update last_login_time
        $updateStmt = $conn->prepare("UPDATE employees SET last_login_time = NOW() WHERE id = ?");
        $updateStmt->bind_param("i", $user['id']);
        $updateStmt->execute();
        $updateStmt->close();

        echo json_encode([
            "status" => "success",
            "message" => "Login successful",
            "token" => $token,
            "employee_number" => $user['employee_number'],
            "employee_name" => $user['employee_name'],
            "role_id" => $user['role_id'],
            "status" => (int)$user['is_active']
        ]);
    } else {
        echo json_encode(["status" => "error", "message" => "Invalid password"]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "User not found"]);
}

$stmt->close();
$conn->close();
?>
