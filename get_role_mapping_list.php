<?php
require 'db.php';
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

try {
    $sql = "
        SELECT 
            er.id,
            e.employee_name,
            e.employee_number,
            r.role_name,
            er.role_id,
            er.employee_id,
            er.created_by,
            er.updated_by,
            er.created_at,
            er.updated_at
        FROM employee_roles er
        INNER JOIN employees e ON er.employee_id = e.id
        INNER JOIN roles r ON er.role_id = r.id
        ORDER BY er.updated_at DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "count" => count($results),
        "data" => $results
    ]);
} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Query failed: " . $e->getMessage()
    ]);
}
