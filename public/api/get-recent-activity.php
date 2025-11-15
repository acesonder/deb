<?php
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();

// For viewers, get the patient they have access to
if ($role === 'viewer') {
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT patient_id FROM access_permissions WHERE viewer_id = ? AND is_active = TRUE LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $user_id = $row['patient_id'];
    }
    $stmt->close();
    $conn->close();
}

$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT log_date, systolic_bp, diastolic_bp, heart_rate, symptoms 
    FROM health_logs 
    WHERE user_id = ? 
    ORDER BY log_date DESC 
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}

echo json_encode([
    'success' => true,
    'data' => $data
]);

$stmt->close();
$conn->close();
?>
