<?php
include 'assets/conn.php'; // Ensure database connection

header('Content-Type: application/json'); // Ensure JSON output

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit;
}

// Validate hall_id
if (!isset($_POST['hall_id']) || empty($_POST['hall_id'])) {
    echo json_encode(["status" => "error", "message" => "Hall ID is missing"]);
    exit;
}

$hall_id = $_POST['hall_id'];
$features = $_POST['features'] ?? []; // Prevent undefined variable

// Map feature names to database fields
$featureMap = [
    'AC' => 'ac',
    'Projector' => 'projector',
    'WiFi' => 'wifi',
    'Podium' => 'podium',
    'Computer' => 'computer',
    'Lift' => 'lift',
    'Ramp' => 'ramp',
    'AudioSystem' => 'audio_system',
    'Whiteboard' => 'white_board',
    'Blackboard' => 'blackboard',
    'Smartboard' => 'smart_board'
];

// Prepare update fields and values for all features
$updateFields = [];
$updateValues = [];

// Update only the selected features
foreach ($featureMap as $featureName => $dbField) {
    $value = isset($features[$featureName]) ? $features[$featureName] : 'No';
    $updateFields[] = "$dbField = ?";
    $updateValues[] = $value;
}


// If no features are selected, return without updating
if (empty($updateFields)) {
    echo json_encode(["status" => "error", "message" => "No features selected for update"]);
    exit;
}

// Build the SQL query dynamically for selected features only
$updateFieldsString = implode(", ", $updateFields);
$query = "UPDATE hall_details SET " . implode(", ", $updateFields) . " WHERE hall_id = ?";

// Prepare the statement
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(["status" => "error", "message" => "Database query failed: " . $conn->error]);
    exit;
}

// Bind parameters dynamically
$updateValues[] = $hall_id; // Add hall_id at the end
$types = str_repeat("s", count($updateValues) - 1) . "i"; // "s" for features, "i" for hall_id

$stmt->bind_param($types, ...$updateValues);

// Execute query
if ($stmt->execute()) {
    echo json_encode(["status" => "success"]);
} else {
    echo json_encode(["status" => "error", "message" => $stmt->error]);
}

// Close resources
$stmt->close();
$conn->close();
exit;
?>