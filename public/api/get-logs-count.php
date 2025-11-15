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

// Count logs from the past 7 days
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM health_logs 
    WHERE user_id = ? AND log_date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

echo json_encode([
    'success' => true,
    'count' => $row['count']
]);

$stmt->close();
$conn->close();
?>
