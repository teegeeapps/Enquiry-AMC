<?php
include 'db.php';

// Static data for testing
$employee_name = 'Akila';
$employee_number = 'E123';
$contact_no = '9887689098';
$email_id = 'aki@gmail.com';
$status = 'Active';
$password = 'venkat';
$updated_by = 'Admin user';

// ✅ Corrected query (missing closing quote & semicolon was fixed)
$sql = "UPDATE employees SET 
    employee_name = '$employee_name',
    employee_number = '$employee_number',
    contact_no = '$contact_no',
    email_id = '$email_id',
    status = '$status',
    password = '$password',
    updated_at = NOW(),
    updated_by = '$updated_by'
    WHERE id = '4'";  // ✔️ Closing quote here, no semicolon inside the string

if ($conn->query($sql) === TRUE) {
    echo json_encode(["message" => "Employee updated successfully"]);
} else {
    echo json_encode(["error" => "Error: " . $conn->error]);
}
?>
