<?php
require_once __DIR__ . '/../../includes/auth.php';
header('Content-Type: application/json');

requireLogin();

$user_id = getCurrentUserId();
$conn = getDBConnection();

$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM alerts 
    WHERE user_id = ? AND is_read = FALSE
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
