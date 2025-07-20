<?php
require 'db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// Handle preflight (OPTIONS) request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Static data for testing (replace with actual API input later)
//$data = json_decode(file_get_contents("php://input"), true);
//$email = $data['email'] ?? '';
//$password = $data['password'] ?? '';

$email = 'aki@gmail.com';
$password = 'venkat';

if (empty($email) || empty($password)) {
    echo json_encode(["status" => "error", "message" => "Email and password are required"]);
    exit;
}

$stmt = $conn->prepare("SELECT * FROM employees WHERE email_id = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();

    // Password Check (Plain-text here for demo. Use password_verify() for hashed password in real apps.)
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
            "employee_number" => $user['employee_number']
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
