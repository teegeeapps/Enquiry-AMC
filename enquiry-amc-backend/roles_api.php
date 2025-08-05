<?php
require 'db.php';
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

/* Sample request
to list down all roles
{
  "action": "get"
}

To add new role
{
  "action": "add",
  "role_name": "Technician"
}

to update
{
  "action": "update",
  "role_id": 2,
  "role_name": "Field Technician"
}
*/

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);
$action = $data['action'] ?? '';

if (!$action) {
    echo json_encode(["status" => "error", "message" => "Missing action"]);
    exit();
}

try {
    if ($action === "get") {
        // ✅ Get all roles
        $result = $conn->query("SELECT id, role_name, created_at FROM roles ORDER BY id ASC");
        $roles = [];
        while ($row = $result->fetch_assoc()) {
            $roles[] = $row;
        }
        echo json_encode(["status" => "success", "roles" => $roles]);

    } elseif ($action === "add") {
        // ✅ Add new role
        $roleName = trim($data['role_name'] ?? '');
        if (empty($roleName)) {
            echo json_encode(["status" => "error", "message" => "role_name is required"]);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO roles (role_name) VALUES (?)");
        $stmt->bind_param("s", $roleName);
        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Role added successfully",
                "role_id" => $stmt->insert_id
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to add role (maybe duplicate?)"]);
        }
        $stmt->close();

    } elseif ($action === "update") {
        // ✅ Update existing role
        $roleId = $data['role_id'] ?? '';
        $roleName = trim($data['role_name'] ?? '');

        if (empty($roleId) || empty($roleName)) {
            echo json_encode(["status" => "error", "message" => "role_id and role_name are required"]);
            exit();
        }

        $stmt = $conn->prepare("UPDATE roles SET role_name=? WHERE id=?");
        $stmt->bind_param("si", $roleName, $roleId);
        if ($stmt->execute()) {
            echo json_encode([
                "status" => "success",
                "message" => "Role updated successfully"
            ]);
        } else {
            echo json_encode(["status" => "error", "message" => "Failed to update role"]);
        }
        $stmt->close();

    } else {
        echo json_encode(["status" => "error", "message" => "Invalid action"]);
    }

} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}

$conn->close();
?>