<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");

require 'db.php';

// ✅ Helper function to safely normalize date
function normalizeDate($date) {
    if (empty($date) || $date == "0000-00-00") return null;
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d ? $d->format('Y-m-d') : null;
}

// ✅ Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

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

$client_name = $data['client_name'];
$contact_person_name = $data['contact_person_name'];
$contact_no1 = $data['contact_no1'];
$contact_no2 = $data['contact_no2'] ?? null;
$email_id = $data['email_id'] ?? null;
$address = $data['address'];
$requirement = $data['requirement'];
$requirement_category = $data['requirement_category'];
$source_of_enquiry = $data['source_of_enquiry'] ?? null;

// ✅ Normalize all dates
$enquiry_date = normalizeDate($data['enquiry_date'] ?? null);
$follow_up_date = normalizeDate($data['follow_up_date'] ?? null);
$delivered_date = normalizeDate($data['delivered_date'] ?? null);
$requested_delivery_date = normalizeDate($data['requested_delivery_date'] ?? null);
$amc_date = normalizeDate($data['amc_date'] ?? null);

$enquiry_status_id = $data['enquiry_status_id'];
$follow_up_notes = $data['follow_up_notes'] ?? null;
$created_by = $data['created_by'] ?? 'system';
$updated_by = $data['updated_by'] ?? 'system';

if ($is_update) {
    // ✅ UPDATE existing enquiry
    $sql = "UPDATE enquiries SET 
        client_name=?, contact_person_name=?, contact_no1=?, contact_no2=?, email_id=?,
        address=?, requirement=?, requirement_category=?, source_of_enquiry=?,
        enquiry_date=?, enquiry_status_id=?, follow_up_date=?, follow_up_notes=?,
        delivered_date=?, requested_delivery_date=?, amc_date=?,
        updated_by=?, updated_at=NOW()
        WHERE enquiry_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param(
        "sssssssssissssssss",
        $client_name, $contact_person_name, $contact_no1, $contact_no2, $email_id,
        $address, $requirement, $requirement_category, $source_of_enquiry,
        $enquiry_date, $enquiry_status_id, $follow_up_date, $follow_up_notes,
        $delivered_date, $requested_delivery_date, $amc_date,
        $updated_by, $enquiry_id
    );

    $success = $stmt->execute();
    $stmt->close();

    if ($success) {
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
            'message' => 'Enquiry updated successfully'
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to update enquiry'
        ]);
    }

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
        "sssssssssissssssss",
        $client_name, $contact_person_name, $contact_no1, $contact_no2, $email_id,
        $address, $requirement, $requirement_category, $source_of_enquiry,
        $enquiry_date, $enquiry_status_id, $follow_up_date, $follow_up_notes,
        $delivered_date, $requested_delivery_date, $amc_date,
        $created_by, $updated_by
    );

    $success = $stmt->execute();
    $last_id = $conn->insert_id;
    $stmt->close();

    if ($success) {
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
            'message' => 'Failed to create enquiry'
        ]);
    }
}

$conn->close();
?>
