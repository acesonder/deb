<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

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

// Get analytics data
$conn = getDBConnection();

// Get last 30 days of BP data
$bp_data = [];
$stmt = $conn->prepare("
    SELECT DATE(log_date) as date, 
           AVG(systolic_bp) as avg_systolic, 
           AVG(diastolic_bp) as avg_diastolic,
           MAX(systolic_bp) as max_systolic,
           MIN(systolic_bp) as min_systolic
    FROM health_logs 
    WHERE user_id = ? AND log_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND systolic_bp IS NOT NULL AND diastolic_bp IS NOT NULL
    GROUP BY DATE(log_date)
    ORDER BY date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $bp_data[] = $row;
}
$stmt->close();

// Get summary statistics
$stmt = $conn->prepare("
    SELECT 
        AVG(systolic_bp) as avg_systolic,
        AVG(diastolic_bp) as avg_diastolic,
        MAX(systolic_bp) as max_systolic,
        MAX(diastolic_bp) as max_diastolic,
        MIN(systolic_bp) as min_systolic,
        MIN(diastolic_bp) as min_diastolic,
        AVG(heart_rate) as avg_hr,
        COUNT(*) as total_logs
    FROM health_logs
    WHERE user_id = ? AND log_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stats = $result->fetch_assoc();
$stmt->close();

// Get mood distribution
$stmt = $conn->prepare("
    SELECT mood, COUNT(*) as count
    FROM health_logs
    WHERE user_id = ? AND mood IS NOT NULL AND log_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY mood
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$mood_data = [];
while ($row = $result->fetch_assoc()) {
    $mood_data[$row['mood']] = $row['count'];
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .analytics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }
        
        .analytics-card {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .analytics-card h3 {
            color: var(--primary-color);
            margin-bottom: var(--spacing-md);
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-sm) 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .stat-row:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: var(--text-secondary);
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .chart-container {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .chart-placeholder {
            background: var(--bg-primary);
            padding: var(--spacing-xl);
            border-radius: var(--radius-md);
            text-align: center;
            color: var(--text-secondary);
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .trend-indicator {
            display: inline-block;
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            font-size: 0.875rem;
            font-weight: 600;
            margin-left: var(--spacing-sm);
        }
        
        .trend-good {
            background: #e6f9f5;
            color: #00765a;
        }
        
        .trend-warning {
            background: #fff4e6;
            color: #996300;
        }
        
        .trend-critical {
            background: #ffe6e6;
            color: #c41e3a;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: var(--spacing-md);
        }
        
        .data-table th,
        .data-table td {
            padding: var(--spacing-sm);
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .data-table th {
            background: var(--bg-primary);
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container">
        <aside class="sidebar">
            <?php include 'includes/sidebar.php'; ?>
        </aside>
        
        <main class="main-content">
            <div class="dashboard-header">
                <h1>📈 Health Analytics</h1>
                <p class="dashboard-subtitle">30-Day Health Trends and Patterns</p>
            </div>
            
            <?php if ($stats['total_logs'] == 0): ?>
                <div class="alert alert-info">
                    No health data available for analysis yet. Start logging your health data to see trends and patterns.
                </div>
            <?php else: ?>
            
            <!-- Summary Statistics -->
            <div class="analytics-grid">
                <div class="analytics-card">
                    <h3>🩺 Blood Pressure Summary</h3>
                    <div class="stat-row">
                        <span class="stat-label">Average</span>
                        <span class="stat-number">
                            <?php echo round($stats['avg_systolic']); ?>/<?php echo round($stats['avg_diastolic']); ?>
                            <?php
                            $avg_sys = round($stats['avg_systolic']);
                            $avg_dia = round($stats['avg_diastolic']);
                            if ($avg_sys >= 140 || $avg_dia >= 90) {
                                echo '<span class="trend-indicator trend-critical">High</span>';
                            } elseif ($avg_sys >= 130 || $avg_dia >= 80) {
                                echo '<span class="trend-indicator trend-warning">Elevated</span>';
                            } else {
                                echo '<span class="trend-indicator trend-good">Normal</span>';
                            }
                            ?>
                        </span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Highest</span>
                        <span class="stat-number" style="font-size: 1.125rem;">
                            <?php echo $stats['max_systolic']; ?>/<?php echo $stats['max_diastolic']; ?>
                        </span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Lowest</span>
                        <span class="stat-number" style="font-size: 1.125rem;">
                            <?php echo $stats['min_systolic']; ?>/<?php echo $stats['min_diastolic']; ?>
                        </span>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3>❤️ Heart Rate Summary</h3>
                    <div class="stat-row">
                        <span class="stat-label">Average Heart Rate</span>
                        <span class="stat-number">
                            <?php echo $stats['avg_hr'] ? round($stats['avg_hr']) : 'N/A'; ?>
                            <?php if ($stats['avg_hr']): ?>
                                <span style="font-size: 1rem; color: var(--text-secondary);">bpm</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Total Logs</span>
                        <span class="stat-number"><?php echo $stats['total_logs']; ?></span>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3>😊 Mood Distribution</h3>
                    <?php if (count($mood_data) > 0): ?>
                        <?php foreach ($mood_data as $mood => $count): ?>
                            <div class="stat-row">
                                <span class="stat-label">
                                    <?php
                                    $mood_emojis = [
                                        'excellent' => '😄 Excellent',
                                        'good' => '🙂 Good',
                                        'fair' => '😐 Fair',
                                        'poor' => '😟 Poor',
                                        'critical' => '😰 Critical'
                                    ];
                                    echo $mood_emojis[$mood] ?? ucfirst($mood);
                                    ?>
                                </span>
                                <span style="font-weight: 600;"><?php echo $count; ?> days</span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: var(--text-secondary);">No mood data recorded</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Blood Pressure Trend Chart -->
            <div class="chart-container">
                <h2>Blood Pressure Trend (Last 30 Days)</h2>
                <?php if (count($bp_data) > 0): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Average BP</th>
                                <th>Max Systolic</th>
                                <th>Min Systolic</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_reverse($bp_data) as $data): ?>
                                <tr>
                                    <td><?php echo date('M j', strtotime($data['date'])); ?></td>
                                    <td>
                                        <strong><?php echo round($data['avg_systolic']); ?>/<?php echo round($data['avg_diastolic']); ?></strong> mmHg
                                    </td>
                                    <td><?php echo $data['max_systolic']; ?> mmHg</td>
                                    <td><?php echo $data['min_systolic']; ?> mmHg</td>
                                    <td>
                                        <?php
                                        $sys = round($data['avg_systolic']);
                                        $dia = round($data['avg_diastolic']);
                                        if ($sys >= 140 || $dia >= 90) {
                                            echo '<span class="trend-indicator trend-critical">High</span>';
                                        } elseif ($sys >= 130 || $dia >= 80) {
                                            echo '<span class="trend-indicator trend-warning">Elevated</span>';
                                        } else {
                                            echo '<span class="trend-indicator trend-good">Normal</span>';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div class="chart-placeholder" style="margin-top: var(--spacing-lg);">
                        <p style="font-size: 3rem; margin-bottom: var(--spacing-md);">📊</p>
                        <p><strong>Interactive Chart Coming Soon!</strong></p>
                        <p>Future update will include visual charts using Chart.js or similar library</p>
                    </div>
                <?php else: ?>
                    <div class="chart-placeholder">
                        <p style="font-size: 3rem; margin-bottom: var(--spacing-md);">📊</p>
                        <p>No blood pressure data available for the last 30 days</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Insights and Recommendations -->
            <div class="analytics-card">
                <h2>💡 Insights & Recommendations</h2>
                <div style="color: var(--text-secondary); line-height: 1.8;">
                    <?php
                    $insights = [];
                    
                    if ($stats['avg_systolic'] >= 140 || $stats['avg_diastolic'] >= 90) {
                        $insights[] = '⚠️ Your average blood pressure is in the high range. Consult with your doctor about treatment options.';
                    } elseif ($stats['avg_systolic'] >= 130 || $stats['avg_diastolic'] >= 80) {
                        $insights[] = '⚠️ Your blood pressure is elevated. Consider lifestyle changes and monitor closely.';
                    } else {
                        $insights[] = '✅ Your average blood pressure is in the normal range. Keep up the good work!';
                    }
                    
                    if ($stats['max_systolic'] >= 180 || $stats['max_diastolic'] >= 120) {
                        $insights[] = '🚨 You\'ve had critical blood pressure readings. Seek immediate medical attention if symptoms occur.';
                    }
                    
                    if ($stats['total_logs'] < 15) {
                        $insights[] = '📝 Log your health data more consistently for better trend analysis.';
                    } else {
                        $insights[] = '✅ Great job logging your health data regularly!';
                    }
                    
                    if (count($mood_data) > 0 && isset($mood_data['poor']) || isset($mood_data['critical'])) {
                        $insights[] = '😟 Your mood has been affected recently. Consider discussing stress management with your doctor.';
                    }
                    
                    foreach ($insights as $insight) {
                        echo "<p style='margin-bottom: var(--spacing-sm);'>$insight</p>";
                    }
                    ?>
                </div>
            </div>
            
            <?php endif; ?>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
