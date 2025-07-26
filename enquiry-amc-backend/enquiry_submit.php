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
$data = json_decode(file_get_contents("php://input"), true);

// ✅ Example test payload for debugging (REMOVE IN PRODUCTION)
// $data = json_decode('{"enquiry_id":"EQ003","client_name":"TATA","contact_person_name":"Kumar","contact_no1":"9876432189","contact_no2":"9876432189","email_id":"kumar123@gmail.com","address":"Industrial estate","requirement":"Fire Extinguisher","requirement_category":"New","source_of_enquiry":"Reference","enquiry_date":"2025-07-26","enquiry_status_id":1,"follow_up_date":"2025-07-27","follow_up_notes":"To be followed","delivered_date":"2025-08-01","requested_delivery_date":"2025-07-30","amc_date":"2025-08-01","created_by":"Admin","updated_by":"Admin"}', true);

// ✅ Required fields
$required = [
    'client_name', 'contact_person_name', 'contact_no1',
    'address', 'requirement', 'requirement_category',
    'enquiry_date', 'enquiry_status_id'
];

// ✅ Validate required fields
$missing = [];
foreach ($required as $field) {
    if (empty($data[$field])) {
        $missing[] = $field;
    }
}

if (!empty($missing)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Missing required fields: ' . implode(', ', $missing)
    ]);
    exit;
}

// ✅ Extract input
$enquiry_id = $data['enquiry_id'] ?? '';
$is_update = !empty($enquiry_id);

$client_name         = $data['client_name'];
$contact_person_name = $data['contact_person_name'];
$contact_no1         = $data['contact_no1'];
$contact_no2         = $data['contact_no2'] ?? null;
$email_id            = $data['email_id'] ?? null;
$address             = $data['address'];
$requirement         = $data['requirement'];
$requirement_category= $data['requirement_category'];
$source_of_enquiry   = $data['source_of_enquiry'] ?? null;

$enquiry_status_id   = (int)$data['enquiry_status_id'];  // Ensure INT
$follow_up_notes     = $data['follow_up_notes'] ?? null;
$created_by          = $data['created_by'] ?? 'system';
$updated_by          = $data['updated_by'] ?? 'system';

// ✅ Dates (already YYYY-MM-DD)
$enquiry_date            = !empty($data['enquiry_date']) ? $data['enquiry_date'] : null;
$follow_up_date          = !empty($data['follow_up_date']) ? $data['follow_up_date'] : null;
$delivered_date          = !empty($data['delivered_date']) ? $data['delivered_date'] : null;
$requested_delivery_date = !empty($data['requested_delivery_date']) ? $data['requested_delivery_date'] : null;
$amc_date                = !empty($data['amc_date']) ? $data['amc_date'] : null;

if ($is_update) {
    // ✅ Check if enquiry_id exists before updating
    $check = $conn->prepare("SELECT id FROM enquiries WHERE enquiry_id=?");
    $check->bind_param("s", $enquiry_id);
    $check->execute();
    $check->store_result();
    if ($check->num_rows === 0) {
        echo json_encode([
            'status' => 'error',
            'message' => "No enquiry found with ID $enquiry_id"
        ]);
        exit;
    }
    $check->close();

    // ✅ UPDATE existing enquiry
    $sql = "UPDATE enquiries SET 
        client_name=?, contact_person_name=?, contact_no1=?, contact_no2=?, email_id=?,
        address=?, requirement=?, requirement_category=?, source_of_enquiry=?,
        enquiry_date=?, enquiry_status_id=?, follow_up_date=?, follow_up_notes=?,
        delivered_date=?, requested_delivery_date=?, amc_date=?,
        updated_by=?, updated_at=NOW()
        WHERE enquiry_id=?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssisssssss",
        $client_name, $contact_person_name, $contact_no1, $contact_no2, $email_id,
        $address, $requirement, $requirement_category, $source_of_enquiry,
        $enquiry_date, $enquiry_status_id, $follow_up_date, $follow_up_notes,
        $delivered_date, $requested_delivery_date, $amc_date,
        $updated_by, $enquiry_id
    );

    if ($stmt->execute()) {
        // ✅ Affected rows check
        if ($stmt->affected_rows >= 0) {
            // ✅ Get internal enquiry ID for follow-up insertion
            $result = $conn->query("SELECT id FROM enquiries WHERE enquiry_id = '$enquiry_id'");
            $row = $result->fetch_assoc();
            $internal_enquiry_id = $row['id'];

            // ✅ Insert into follow-up table if needed
            if (!empty($follow_up_date) || !empty($follow_up_notes)) {
                $stmt2 = $conn->prepare("INSERT INTO enquiry_followups (enquiry_id, follow_up_date, follow_up_notes) VALUES (?, ?, ?)");
                $stmt2->bind_param("iss", $internal_enquiry_id, $follow_up_date, $follow_up_notes);
                $stmt2->execute();
                $stmt2->close();
            }

            echo json_encode([
                'status' => 'success',
                'message' => 'Enquiry updated successfully',
                'affected_rows' => $stmt->affected_rows
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'No changes made (values may be same)'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update enquiry',
            'db_error' => $stmt->error
        ]);
    }
    $stmt->close();

} else {
    // ✅ INSERT new enquiry
    $sql = "INSERT INTO enquiries (
        client_name, contact_person_name, contact_no1, contact_no2, email_id,
        address, requirement, requirement_category, source_of_enquiry,
        enquiry_date, enquiry_status_id, follow_up_date, follow_up_notes,
        delivered_date, requested_delivery_date, amc_date,
        created_by, updated_by
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "ssssssssssisssssss",
        $client_name, $contact_person_name, $contact_no1, $contact_no2, $email_id,
        $address, $requirement, $requirement_category, $source_of_enquiry,
        $enquiry_date, $enquiry_status_id, $follow_up_date, $follow_up_notes,
        $delivered_date, $requested_delivery_date, $amc_date,
        $created_by, $updated_by
    );

    if ($stmt->execute()) {
        $last_id = $conn->insert_id;
        $stmt->close();

        // ✅ Generate EQ ID
        $generated_enquiry_id = 'EQ' . str_pad($last_id, 3, '0', STR_PAD_LEFT);
        $conn->query("UPDATE enquiries SET enquiry_id = '$generated_enquiry_id' WHERE id = $last_id");

        // ✅ Insert into follow-up table if applicable
        if (!empty($follow_up_date) || !empty($follow_up_notes)) {
            $stmt2 = $conn->prepare("INSERT INTO enquiry_followups (enquiry_id, follow_up_date, follow_up_notes) VALUES (?, ?, ?)");
            $stmt2->bind_param("iss", $last_id, $follow_up_date, $follow_up_notes);
            $stmt2->execute();
            $stmt2->close();
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Enquiry created successfully',
            'enquiry_id' => $generated_enquiry_id
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create enquiry',
            'db_error' => $stmt->error
        ]);
        $stmt->close();
    }
}

$conn->close();
?>
