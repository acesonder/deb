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

// Get heart rate data
$hr_data = [];
$stmt = $conn->prepare("
    SELECT DATE(log_date) as date, 
           AVG(heart_rate) as avg_hr
    FROM health_logs 
    WHERE user_id = ? AND log_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND heart_rate IS NOT NULL
    GROUP BY DATE(log_date)
    ORDER BY date ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $hr_data[] = $row;
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
        AVG(stress_level) as avg_stress,
        AVG(sleep_hours) as avg_sleep,
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

// Get correlation data (BP vs Stress)
$correlation_data = [];
$stmt = $conn->prepare("
    SELECT stress_level, AVG(systolic_bp) as avg_systolic
    FROM health_logs
    WHERE user_id = ? AND log_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND stress_level IS NOT NULL AND systolic_bp IS NOT NULL
    GROUP BY stress_level
    ORDER BY stress_level ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $correlation_data[] = $row;
}
$stmt->close();

// Get week-over-week comparison data (last 8 weeks)
$week_data = [];
$stmt = $conn->prepare("
    SELECT 
        WEEK(log_date) as week_num,
        YEAR(log_date) as year_num,
        CONCAT('Week ', WEEK(log_date)) as week_label,
        AVG(systolic_bp) as avg_systolic,
        AVG(diastolic_bp) as avg_diastolic
    FROM health_logs
    WHERE user_id = ? AND log_date >= DATE_SUB(NOW(), INTERVAL 56 DAY)
          AND systolic_bp IS NOT NULL
    GROUP BY YEAR(log_date), WEEK(log_date)
    ORDER BY year_num DESC, week_num DESC
    LIMIT 8
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $week_data[] = $row;
}
$week_data = array_reverse($week_data);
$stmt->close();

// Get sleep vs symptoms correlation
$sleep_data = [];
$stmt = $conn->prepare("
    SELECT 
        sleep_hours,
        COUNT(CASE WHEN symptoms IS NOT NULL AND symptoms != '' THEN 1 END) as symptom_count,
        AVG(systolic_bp) as avg_bp
    FROM health_logs
    WHERE user_id = ? AND log_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND sleep_hours IS NOT NULL
    GROUP BY sleep_hours
    ORDER BY sleep_hours ASC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $sleep_data[] = $row;
}
$stmt->close();

// Predictive Analytics - Simple trend calculation
$bp_trend = 'stable';
$trend_percentage = 0;
if (count($bp_data) >= 7) {
    $recent_avg = 0;
    $older_avg = 0;
    $recent_count = 0;
    $older_count = 0;
    
    foreach ($bp_data as $index => $data) {
        if ($index < count($bp_data) / 2) {
            $older_avg += $data['avg_systolic'];
            $older_count++;
        } else {
            $recent_avg += $data['avg_systolic'];
            $recent_count++;
        }
    }
    
    if ($older_count > 0 && $recent_count > 0) {
        $older_avg /= $older_count;
        $recent_avg /= $recent_count;
        $trend_percentage = (($recent_avg - $older_avg) / $older_avg) * 100;
        
        if ($trend_percentage > 5) {
            $bp_trend = 'increasing';
        } elseif ($trend_percentage < -5) {
            $bp_trend = 'decreasing';
        }
    }
}

// Anomaly Detection - Identify unusual readings
$anomalies = [];
$stmt = $conn->prepare("
    SELECT log_date, systolic_bp, diastolic_bp, heart_rate, symptoms
    FROM health_logs
    WHERE user_id = ? AND log_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
          AND (
              systolic_bp > ? + 20 
              OR systolic_bp < ? - 20
              OR diastolic_bp > ? + 15
              OR diastolic_bp < ? - 15
          )
    ORDER BY log_date DESC
    LIMIT 10
");
$avg_sys = $stats['avg_systolic'] ?? 120;
$avg_dia = $stats['avg_diastolic'] ?? 80;
$stmt->bind_param("idddd", $user_id, $avg_sys, $avg_sys, $avg_dia, $avg_dia);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $anomalies[] = $row;
}
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Analytics - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
        
        .chart-container {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            min-height: 400px;
        }
        
        .chart-wrapper {
            position: relative;
            height: 350px;
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
        
        .prediction-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
        }
        
        .anomaly-item {
            background: #fff4e6;
            padding: var(--spacing-md);
            border-left: 4px solid #ff9800;
            margin-bottom: var(--spacing-sm);
            border-radius: var(--radius-sm);
        }
        
        .export-buttons {
            display: flex;
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
            flex-wrap: wrap;
        }
        
        .export-btn {
            padding: 10px 20px;
            border-radius: var(--radius-md);
            border: none;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .export-pdf {
            background: #e74c3c;
            color: white;
        }
        
        .export-csv {
            background: #27ae60;
            color: white;
        }
        
        .export-excel {
            background: #2ecc71;
            color: white;
        }
        
        .export-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
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
                <h1>📊 Advanced Health Analytics</h1>
                <p class="dashboard-subtitle">Comprehensive Health Trends, Predictions & Insights</p>
            </div>
            
            <!-- Export Options -->
            <div class="export-buttons">
                <button class="export-btn export-pdf" onclick="exportToPDF()">📄 Export to PDF</button>
                <button class="export-btn export-csv" onclick="exportToCSV()">📊 Export to CSV</button>
                <button class="export-btn export-excel" onclick="exportToExcel()">📗 Export to Excel</button>
                <button class="export-btn" onclick="window.print()" style="background: #3498db; color: white;">🖨️ Print Report</button>
            </div>
            
            <?php if ($stats['total_logs'] == 0): ?>
                <div class="alert alert-info">
                    No health data available for analysis yet. Start logging your health data to see trends and patterns.
                </div>
            <?php else: ?>
            
            <!-- Predictive Analytics -->
            <?php if (count($bp_data) >= 7): ?>
            <div class="prediction-box">
                <h2 style="color: white; margin-bottom: var(--spacing-md);">🔮 Predictive Analytics & Trends</h2>
                <div style="font-size: 1.125rem;">
                    <p><strong>Blood Pressure Trend:</strong> 
                        <?php if ($bp_trend == 'increasing'): ?>
                            📈 Increasing by <?php echo number_format(abs($trend_percentage), 1); ?>%
                            <span style="display: block; margin-top: 8px; font-size: 0.9rem;">
                                ⚠️ Your blood pressure shows an upward trend. Consider lifestyle modifications and consult your doctor.
                            </span>
                        <?php elseif ($bp_trend == 'decreasing'): ?>
                            📉 Decreasing by <?php echo number_format(abs($trend_percentage), 1); ?>%
                            <span style="display: block; margin-top: 8px; font-size: 0.9rem;">
                                ✅ Great! Your blood pressure is trending downward. Keep up your healthy habits!
                            </span>
                        <?php else: ?>
                            ➡️ Stable (±<?php echo number_format(abs($trend_percentage), 1); ?>%)
                            <span style="display: block; margin-top: 8px; font-size: 0.9rem;">
                                ✅ Your blood pressure is stable. Continue monitoring regularly.
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>
            
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
                    <div class="stat-row">
                        <span class="stat-label">Avg Sleep</span>
                        <span class="stat-number" style="font-size: 1.125rem;">
                            <?php echo $stats['avg_sleep'] ? number_format($stats['avg_sleep'], 1) : 'N/A'; ?>
                            <?php if ($stats['avg_sleep']): ?>
                                <span style="font-size: 0.875rem; color: var(--text-secondary);">hrs</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
                
                <div class="analytics-card">
                    <h3>😊 Wellness Metrics</h3>
                    <div class="stat-row">
                        <span class="stat-label">Avg Stress Level</span>
                        <span class="stat-number" style="font-size: 1.125rem;">
                            <?php echo $stats['avg_stress'] ? number_format($stats['avg_stress'], 1) : 'N/A'; ?>
                            <?php if ($stats['avg_stress']): ?>
                                <span style="font-size: 0.875rem; color: var(--text-secondary);">/10</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Data Points</span>
                        <span class="stat-number" style="font-size: 1.125rem;"><?php echo $stats['total_logs']; ?></span>
                    </div>
                    <div class="stat-row">
                        <span class="stat-label">Consistency</span>
                        <span class="stat-number" style="font-size: 1.125rem;">
                            <?php 
                            $consistency = min(100, ($stats['total_logs'] / 30) * 100);
                            echo round($consistency); ?>%
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Blood Pressure Trend Chart -->
            <?php if (count($bp_data) > 0): ?>
            <div class="chart-container">
                <h2>📈 Blood Pressure Trend (Last 30 Days)</h2>
                <div class="chart-wrapper">
                    <canvas id="bpChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Heart Rate Chart -->
            <?php if (count($hr_data) > 0): ?>
            <div class="chart-container">
                <h2>💓 Heart Rate Over Time</h2>
                <div class="chart-wrapper">
                    <canvas id="hrChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Mood Distribution -->
            <?php if (count($mood_data) > 0): ?>
            <div class="chart-container">
                <h2>😊 Mood Distribution</h2>
                <div class="chart-wrapper">
                    <canvas id="moodChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Week over Week Comparison -->
            <?php if (count($week_data) > 0): ?>
            <div class="chart-container">
                <h2>📊 Week-over-Week Comparison</h2>
                <div class="chart-wrapper">
                    <canvas id="weekComparisonChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Correlation Analysis -->
            <?php if (count($correlation_data) > 0): ?>
            <div class="chart-container">
                <h2>🔗 Correlation: Blood Pressure vs Stress Level</h2>
                <div class="chart-wrapper">
                    <canvas id="correlationChart"></canvas>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Anomaly Detection -->
            <?php if (count($anomalies) > 0): ?>
            <div class="analytics-card">
                <h2>🚨 Anomaly Detection - Unusual Readings</h2>
                <p style="color: var(--text-secondary); margin-bottom: var(--spacing-md);">
                    These readings significantly differ from your average values:
                </p>
                <?php foreach ($anomalies as $anomaly): ?>
                    <div class="anomaly-item">
                        <strong><?php echo date('M j, Y', strtotime($anomaly['log_date'])); ?></strong> - 
                        BP: <?php echo $anomaly['systolic_bp']; ?>/<?php echo $anomaly['diastolic_bp']; ?> mmHg
                        <?php if ($anomaly['heart_rate']): ?>
                            | HR: <?php echo $anomaly['heart_rate']; ?> bpm
                        <?php endif; ?>
                        <?php if ($anomaly['symptoms']): ?>
                            <br><small>Symptoms: <?php echo htmlspecialchars(substr($anomaly['symptoms'], 0, 100)); ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
            
            <!-- Personalized Recommendations -->
            <div class="analytics-card">
                <h2>💡 Personalized Health Recommendations</h2>
                <div style="color: var(--text-secondary); line-height: 1.8;">
                    <?php
                    $recommendations = [];
                    
                    // BP recommendations
                    if ($stats['avg_systolic'] >= 140 || $stats['avg_diastolic'] >= 90) {
                        $recommendations[] = '⚠️ <strong>High Blood Pressure:</strong> Your average BP is in the high range. Consult your doctor about treatment options and lifestyle modifications.';
                    } elseif ($stats['avg_systolic'] >= 130 || $stats['avg_diastolic'] >= 80) {
                        $recommendations[] = '⚠️ <strong>Elevated Blood Pressure:</strong> Consider reducing sodium intake, increasing physical activity, and managing stress.';
                    } else {
                        $recommendations[] = '✅ <strong>Blood Pressure:</strong> Your BP is in the normal range. Maintain your healthy lifestyle!';
                    }
                    
                    // Stress recommendations
                    if ($stats['avg_stress'] && $stats['avg_stress'] > 7) {
                        $recommendations[] = '🧘 <strong>High Stress Levels:</strong> Try stress reduction techniques like meditation, deep breathing, or yoga. Consider discussing with your healthcare provider.';
                    }
                    
                    // Sleep recommendations
                    if ($stats['avg_sleep'] && $stats['avg_sleep'] < 6) {
                        $recommendations[] = '😴 <strong>Insufficient Sleep:</strong> Aim for 7-9 hours of sleep per night. Poor sleep can affect blood pressure and overall health.';
                    } elseif ($stats['avg_sleep'] && $stats['avg_sleep'] >= 7 && $stats['avg_sleep'] <= 9) {
                        $recommendations[] = '✅ <strong>Good Sleep Habits:</strong> You\'re getting adequate sleep. Keep maintaining this healthy pattern!';
                    }
                    
                    // Logging consistency
                    if ($stats['total_logs'] < 15) {
                        $recommendations[] = '📝 <strong>Improve Logging:</strong> Log your health data more consistently (daily if possible) for better trend analysis and insights.';
                    } else {
                        $recommendations[] = '✅ <strong>Excellent Logging:</strong> Great job tracking your health regularly! This data is valuable for you and your healthcare team.';
                    }
                    
                    // Trend-based recommendations
                    if ($bp_trend == 'increasing') {
                        $recommendations[] = '📈 <strong>Rising Trend Alert:</strong> Your BP is trending upward. Review your diet, exercise, stress levels, and medication adherence. Consult your doctor.';
                    } elseif ($bp_trend == 'decreasing') {
                        $recommendations[] = '🎉 <strong>Positive Trend:</strong> Your BP is improving! Whatever you\'re doing is working. Keep it up!';
                    }
                    
                    // Anomaly-based recommendations
                    if (count($anomalies) > 3) {
                        $recommendations[] = '🚨 <strong>Unusual Readings Detected:</strong> You have several readings that differ significantly from your average. Discuss these with your doctor to rule out any concerns.';
                    }
                    
                    foreach ($recommendations as $recommendation) {
                        echo "<p style='margin-bottom: var(--spacing-md);'>$recommendation</p>";
                    }
                    ?>
                </div>
            </div>
            
            <!-- Cardiovascular Risk Assessment -->
            <div class="analytics-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                <h2 style="color: white;">❤️ Cardiovascular Risk Assessment</h2>
                <div style="line-height: 1.8;">
                    <?php
                    $risk_score = 0;
                    $risk_factors = [];
                    
                    if ($stats['avg_systolic'] >= 140) {
                        $risk_score += 3;
                        $risk_factors[] = 'High blood pressure (Stage 2)';
                    } elseif ($stats['avg_systolic'] >= 130) {
                        $risk_score += 2;
                        $risk_factors[] = 'High blood pressure (Stage 1)';
                    }
                    
                    if ($stats['avg_stress'] && $stats['avg_stress'] > 7) {
                        $risk_score += 1;
                        $risk_factors[] = 'High stress levels';
                    }
                    
                    if ($stats['avg_sleep'] && $stats['avg_sleep'] < 6) {
                        $risk_score += 1;
                        $risk_factors[] = 'Insufficient sleep';
                    }
                    
                    if (count($anomalies) > 5) {
                        $risk_score += 1;
                        $risk_factors[] = 'Frequent unusual readings';
                    }
                    
                    $risk_level = 'Low';
                    $risk_message = 'Your cardiovascular risk appears to be low based on the tracked metrics. Continue your healthy habits!';
                    
                    if ($risk_score >= 4) {
                        $risk_level = 'High';
                        $risk_message = 'You have multiple risk factors. Please consult with your healthcare provider for a comprehensive assessment and treatment plan.';
                    } elseif ($risk_score >= 2) {
                        $risk_level = 'Moderate';
                        $risk_message = 'You have some risk factors. Consider lifestyle modifications and discuss with your doctor.';
                    }
                    ?>
                    <p style="font-size: 1.25rem; margin-bottom: var(--spacing-md);"><strong>Risk Level: <?php echo $risk_level; ?></strong></p>
                    <p style="margin-bottom: var(--spacing-md);"><?php echo $risk_message; ?></p>
                    
                    <?php if (count($risk_factors) > 0): ?>
                        <p style="margin-bottom: var(--spacing-sm);"><strong>Identified Risk Factors:</strong></p>
                        <ul style="margin-left: var(--spacing-lg);">
                            <?php foreach ($risk_factors as $factor): ?>
                                <li><?php echo $factor; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                    
                    <p style="margin-top: var(--spacing-md); font-size: 0.875rem; opacity: 0.9;">
                        <em>⚠️ This assessment is based solely on tracked health metrics and is not a substitute for professional medical evaluation.</em>
                    </p>
                </div>
            </div>
            
            <?php endif; ?>
        </main>
    </div>
    
    <script src="js/main.js"></script>
    <script src="js/analytics.js"></script>
    <script>
        // Initialize charts with PHP data
        const bpData = <?php echo json_encode($bp_data); ?>;
        const hrData = <?php echo json_encode($hr_data); ?>;
        const moodData = <?php echo json_encode($mood_data); ?>;
        const correlationData = <?php echo json_encode($correlation_data); ?>;
        const weekData = <?php echo json_encode($week_data); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            if (bpData && bpData.length > 0) {
                createBPChart(bpData);
            }
            
            if (hrData && hrData.length > 0) {
                createHeartRateChart(hrData);
            }
            
            if (moodData && Object.keys(moodData).length > 0) {
                createMoodChart(moodData);
            }
            
            if (correlationData && correlationData.length > 0) {
                createCorrelationChart(correlationData);
            }
            
            if (weekData && weekData.length > 0) {
                createWeekComparisonChart(weekData);
            }
        });
        
        // Export functions
        function exportToPDF() {
            alert('PDF export feature will generate a comprehensive health report. This feature requires server-side PDF generation library. Implementation pending.');
            // In production, this would call a server endpoint that generates PDF using a library like TCPDF or FPDF
        }
        
        function exportToCSV() {
            window.location.href = 'export.php?format=csv';
        }
        
        function exportToExcel() {
            window.location.href = 'export.php?format=excel';
        }
    </script>
</body>
</html>
