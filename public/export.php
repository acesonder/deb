<?php
/**
 * Data Export Functionality
 * Exports health data to CSV, Excel, and other formats
 */

require_once __DIR__ . '/../includes/auth.php';
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

$format = $_GET['format'] ?? 'csv';
$date_range = $_GET['range'] ?? '30'; // days

// Get health logs data
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT 
        log_date,
        systolic_bp,
        diastolic_bp,
        heart_rate,
        weight,
        temperature,
        sleep_hours,
        activity_level,
        stress_level,
        mood,
        symptoms,
        medications,
        notes
    FROM health_logs
    WHERE user_id = ? AND log_date >= DATE_SUB(NOW(), INTERVAL ? DAY)
    ORDER BY log_date DESC
");
$stmt->bind_param("ii", $user_id, $date_range);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while ($row = $result->fetch_assoc()) {
    $data[] = $row;
}
$stmt->close();
$conn->close();

// Export based on format
if ($format === 'csv') {
    exportToCSV($data);
} elseif ($format === 'excel') {
    exportToExcel($data);
} else {
    die('Invalid export format');
}

function exportToCSV($data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="health_data_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    
    // CSV Headers
    fputcsv($output, [
        'Date',
        'Systolic BP',
        'Diastolic BP',
        'Heart Rate',
        'Weight',
        'Temperature',
        'Sleep Hours',
        'Activity Level',
        'Stress Level',
        'Mood',
        'Symptoms',
        'Medications',
        'Notes'
    ]);
    
    // CSV Data
    foreach ($data as $row) {
        fputcsv($output, [
            $row['log_date'],
            $row['systolic_bp'] ?? '',
            $row['diastolic_bp'] ?? '',
            $row['heart_rate'] ?? '',
            $row['weight'] ?? '',
            $row['temperature'] ?? '',
            $row['sleep_hours'] ?? '',
            $row['activity_level'] ?? '',
            $row['stress_level'] ?? '',
            $row['mood'] ?? '',
            $row['symptoms'] ?? '',
            $row['medications'] ?? '',
            $row['notes'] ?? ''
        ]);
    }
    
    fclose($output);
    exit;
}

function exportToExcel($data) {
    // Generate Excel-compatible XML format (SpreadsheetML)
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="health_data_' . date('Y-m-d') . '.xls"');
    
    echo '<?xml version="1.0"?>' . "\n";
    echo '<?mso-application progid="Excel.Sheet"?>' . "\n";
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"' . "\n";
    echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">' . "\n";
    echo '<Worksheet ss:Name="Health Data">' . "\n";
    echo '<Table>' . "\n";
    
    // Header Row
    echo '<Row>' . "\n";
    $headers = [
        'Date', 'Systolic BP', 'Diastolic BP', 'Heart Rate', 'Weight', 
        'Temperature', 'Sleep Hours', 'Activity Level', 'Stress Level', 
        'Mood', 'Symptoms', 'Medications', 'Notes'
    ];
    foreach ($headers as $header) {
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($header) . '</Data></Cell>' . "\n";
    }
    echo '</Row>' . "\n";
    
    // Data Rows
    foreach ($data as $row) {
        echo '<Row>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['log_date']) . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="Number">' . ($row['systolic_bp'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="Number">' . ($row['diastolic_bp'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="Number">' . ($row['heart_rate'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="Number">' . ($row['weight'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="Number">' . ($row['temperature'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="Number">' . ($row['sleep_hours'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['activity_level'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="Number">' . ($row['stress_level'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['mood'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['symptoms'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['medications'] ?? '') . '</Data></Cell>' . "\n";
        echo '<Cell><Data ss:Type="String">' . htmlspecialchars($row['notes'] ?? '') . '</Data></Cell>' . "\n";
        echo '</Row>' . "\n";
    }
    
    echo '</Table>' . "\n";
    echo '</Worksheet>' . "\n";
    echo '</Workbook>' . "\n";
    
    exit;
}
?>
