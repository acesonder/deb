<?php
/**
 * Medication Effectiveness Tracking
 * Analyzes correlation between medications and health metrics
 */

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

$conn = getDBConnection();

// Get active medications
$stmt = $conn->prepare("
    SELECT * FROM medications
    WHERE user_id = ? AND is_active = TRUE
    ORDER BY start_date DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$active_medications = [];
while ($row = $result->fetch_assoc()) {
    $active_medications[] = $row;
}
$stmt->close();

// Analyze BP trends before and after medication start
$medication_analysis = [];

foreach ($active_medications as $med) {
    $med_name = $med['medication_name'];
    $start_date = $med['start_date'];
    
    // Get average BP 30 days before medication start
    $stmt = $conn->prepare("
        SELECT AVG(systolic_bp) as avg_sys, AVG(diastolic_bp) as avg_dia, COUNT(*) as count
        FROM health_logs
        WHERE user_id = ? 
        AND log_date >= DATE_SUB(?, INTERVAL 30 DAY)
        AND log_date < ?
        AND systolic_bp IS NOT NULL
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $start_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $before_data = $result->fetch_assoc();
    $stmt->close();
    
    // Get average BP 30 days after medication start
    $stmt = $conn->prepare("
        SELECT AVG(systolic_bp) as avg_sys, AVG(diastolic_bp) as avg_dia, COUNT(*) as count
        FROM health_logs
        WHERE user_id = ? 
        AND log_date >= ?
        AND log_date < DATE_ADD(?, INTERVAL 30 DAY)
        AND systolic_bp IS NOT NULL
    ");
    $stmt->bind_param("iss", $user_id, $start_date, $start_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $after_data = $result->fetch_assoc();
    $stmt->close();
    
    // Calculate effectiveness
    $effectiveness = 'insufficient_data';
    $change_sys = 0;
    $change_dia = 0;
    
    if ($before_data['count'] >= 3 && $after_data['count'] >= 3) {
        $change_sys = $before_data['avg_sys'] - $after_data['avg_sys'];
        $change_dia = $before_data['avg_dia'] - $after_data['avg_dia'];
        
        if ($change_sys >= 10 || $change_dia >= 5) {
            $effectiveness = 'highly_effective';
        } elseif ($change_sys >= 5 || $change_dia >= 3) {
            $effectiveness = 'moderately_effective';
        } elseif ($change_sys >= 0 && $change_dia >= 0) {
            $effectiveness = 'slightly_effective';
        } else {
            $effectiveness = 'not_effective';
        }
    }
    
    $medication_analysis[] = [
        'medication' => $med,
        'before' => $before_data,
        'after' => $after_data,
        'change_sys' => $change_sys,
        'change_dia' => $change_dia,
        'effectiveness' => $effectiveness
    ];
}

// Symptom-Medication Correlation
$symptom_correlation = [];

// Get common symptoms when NOT taking specific medications
$stmt = $conn->prepare("
    SELECT symptoms, COUNT(*) as count
    FROM health_logs
    WHERE user_id = ? 
    AND log_date >= DATE_SUB(NOW(), INTERVAL 60 DAY)
    AND symptoms IS NOT NULL 
    AND symptoms != ''
    GROUP BY symptoms
    ORDER BY count DESC
    LIMIT 10
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $symptom_correlation[] = $row;
}
$stmt->close();

// Get average stress level with and without medications
$stmt = $conn->prepare("
    SELECT 
        AVG(CASE WHEN medications IS NOT NULL AND medications != '' THEN stress_level END) as with_meds,
        AVG(CASE WHEN medications IS NULL OR medications = '' THEN stress_level END) as without_meds
    FROM health_logs
    WHERE user_id = ? 
    AND log_date >= DATE_SUB(NOW(), INTERVAL 60 DAY)
    AND stress_level IS NOT NULL
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$stress_correlation = $result->fetch_assoc();
$stmt->close();

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medication Effectiveness - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        .effectiveness-card {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .effectiveness-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: var(--radius-sm);
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .highly-effective {
            background: #e6f9f5;
            color: #00765a;
        }
        
        .moderately-effective {
            background: #e3f2fd;
            color: #0277bd;
        }
        
        .slightly-effective {
            background: #fff4e6;
            color: #996300;
        }
        
        .not-effective {
            background: #ffe6e6;
            color: #c41e3a;
        }
        
        .insufficient-data {
            background: #f5f5f5;
            color: #757575;
        }
        
        .change-indicator {
            font-size: 1.5rem;
            font-weight: 700;
            margin: var(--spacing-md) 0;
        }
        
        .positive {
            color: #00765a;
        }
        
        .negative {
            color: #c41e3a;
        }
        
        .chart-container {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
            min-height: 300px;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: var(--spacing-md);
            margin: var(--spacing-md) 0;
        }
        
        .stat-box {
            background: var(--bg-primary);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            text-align: center;
        }
        
        .symptom-list {
            list-style: none;
            padding: 0;
        }
        
        .symptom-item {
            background: var(--bg-primary);
            padding: var(--spacing-sm) var(--spacing-md);
            border-radius: var(--radius-sm);
            margin-bottom: var(--spacing-xs);
            display: flex;
            justify-content: space-between;
            align-items: center;
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
                <h1>💊 Medication Effectiveness</h1>
                <p class="dashboard-subtitle">Track how medications affect your health</p>
            </div>
            
            <?php if (count($active_medications) == 0): ?>
                <div class="alert alert-info">
                    No active medications found. <a href="medications.php">Add medications</a> to track their effectiveness.
                </div>
            <?php else: ?>
            
            <!-- Medication Effectiveness Analysis -->
            <section style="margin-bottom: var(--spacing-xl);">
                <h2 style="margin-bottom: var(--spacing-lg);">📊 Medication Impact Analysis</h2>
                
                <?php foreach ($medication_analysis as $analysis): ?>
                    <div class="effectiveness-card">
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: var(--spacing-md);">
                            <div>
                                <h3><?php echo htmlspecialchars($analysis['medication']['medication_name']); ?></h3>
                                <p style="color: var(--text-secondary); margin-top: 4px;">
                                    <?php echo htmlspecialchars($analysis['medication']['dosage']); ?> - 
                                    <?php echo htmlspecialchars($analysis['medication']['frequency']); ?>
                                </p>
                                <p style="color: var(--text-secondary); font-size: 0.875rem; margin-top: 4px;">
                                    Started: <?php echo date('M j, Y', strtotime($analysis['medication']['start_date'])); ?>
                                </p>
                            </div>
                            <span class="effectiveness-badge <?php echo $analysis['effectiveness']; ?>">
                                <?php
                                $labels = [
                                    'highly_effective' => '🌟 Highly Effective',
                                    'moderately_effective' => '✓ Moderately Effective',
                                    'slightly_effective' => '~ Slightly Effective',
                                    'not_effective' => '✗ Not Effective',
                                    'insufficient_data' => 'ℹ️ Insufficient Data'
                                ];
                                echo $labels[$analysis['effectiveness']];
                                ?>
                            </span>
                        </div>
                        
                        <?php if ($analysis['effectiveness'] !== 'insufficient_data'): ?>
                            <div class="stats-row">
                                <div class="stat-box">
                                    <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 4px;">Before</div>
                                    <div style="font-size: 1.25rem; font-weight: 700;">
                                        <?php echo round($analysis['before']['avg_sys']); ?>/<?php echo round($analysis['before']['avg_dia']); ?>
                                    </div>
                                </div>
                                <div class="stat-box">
                                    <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 4px;">After</div>
                                    <div style="font-size: 1.25rem; font-weight: 700;">
                                        <?php echo round($analysis['after']['avg_sys']); ?>/<?php echo round($analysis['after']['avg_dia']); ?>
                                    </div>
                                </div>
                                <div class="stat-box">
                                    <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 4px;">Change</div>
                                    <div class="change-indicator <?php echo ($analysis['change_sys'] > 0 ? 'positive' : 'negative'); ?>">
                                        <?php echo $analysis['change_sys'] > 0 ? '↓' : '↑'; ?>
                                        <?php echo abs(round($analysis['change_sys'])); ?>/<?php echo abs(round($analysis['change_dia'])); ?>
                                    </div>
                                </div>
                            </div>
                            
                            <p style="margin-top: var(--spacing-md); color: var(--text-secondary);">
                                <?php
                                if ($analysis['effectiveness'] === 'highly_effective') {
                                    echo "✅ This medication shows significant positive impact on your blood pressure. Continue as prescribed.";
                                } elseif ($analysis['effectiveness'] === 'moderately_effective') {
                                    echo "✓ This medication shows moderate positive impact. Discuss with your doctor if adjustments are needed.";
                                } elseif ($analysis['effectiveness'] === 'slightly_effective') {
                                    echo "~ This medication shows minimal impact. Consider discussing alternatives with your doctor.";
                                } else {
                                    echo "⚠️ This medication shows no improvement or worsening. Consult your doctor immediately.";
                                }
                                ?>
                            </p>
                        <?php else: ?>
                            <p style="color: var(--text-secondary);">
                                Not enough data to analyze effectiveness. Continue logging your health data regularly.
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </section>
            
            <!-- Symptom Correlation -->
            <?php if (count($symptom_correlation) > 0): ?>
            <section class="effectiveness-card">
                <h2 style="margin-bottom: var(--spacing-md);">🔍 Common Symptoms Recorded</h2>
                <p style="color: var(--text-secondary); margin-bottom: var(--spacing-lg);">
                    Most frequently reported symptoms in the past 60 days:
                </p>
                
                <ul class="symptom-list">
                    <?php foreach ($symptom_correlation as $symptom): ?>
                        <li class="symptom-item">
                            <span><?php echo htmlspecialchars($symptom['symptoms']); ?></span>
                            <span style="background: var(--primary-color); color: white; padding: 4px 12px; border-radius: var(--radius-sm); font-weight: 600;">
                                <?php echo $symptom['count']; ?> times
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>
            
            <!-- Stress Level Correlation -->
            <?php if ($stress_correlation['with_meds'] && $stress_correlation['without_meds']): ?>
            <section class="effectiveness-card">
                <h2 style="margin-bottom: var(--spacing-md);">🧘 Stress Level Correlation</h2>
                
                <div class="stats-row">
                    <div class="stat-box">
                        <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 4px;">With Medications</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--primary-color);">
                            <?php echo number_format($stress_correlation['with_meds'], 1); ?>/10
                        </div>
                    </div>
                    <div class="stat-box">
                        <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 4px;">Without Medications</div>
                        <div style="font-size: 2rem; font-weight: 700; color: var(--primary-color);">
                            <?php echo number_format($stress_correlation['without_meds'], 1); ?>/10
                        </div>
                    </div>
                    <div class="stat-box">
                        <div style="font-size: 0.875rem; color: var(--text-secondary); margin-bottom: 4px;">Difference</div>
                        <div class="change-indicator <?php echo ($stress_correlation['with_meds'] < $stress_correlation['without_meds'] ? 'positive' : 'negative'); ?>">
                            <?php 
                            $stress_diff = $stress_correlation['with_meds'] - $stress_correlation['without_meds'];
                            echo $stress_diff < 0 ? '↓ ' . abs(number_format($stress_diff, 1)) : '↑ ' . number_format($stress_diff, 1);
                            ?>
                        </div>
                    </div>
                </div>
                
                <p style="margin-top: var(--spacing-md); color: var(--text-secondary);">
                    <?php if ($stress_correlation['with_meds'] < $stress_correlation['without_meds']): ?>
                        ✅ Your stress levels are lower when taking medications, suggesting they may have a positive effect on your overall well-being.
                    <?php elseif ($stress_correlation['with_meds'] > $stress_correlation['without_meds']): ?>
                        ⚠️ Your stress levels are higher when taking medications. Consider discussing side effects with your doctor.
                    <?php else: ?>
                        ℹ️ Medications show no significant impact on stress levels.
                    <?php endif; ?>
                </p>
            </section>
            <?php endif; ?>
            
            <!-- Recommendations -->
            <div class="effectiveness-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h2 style="color: white; margin-bottom: var(--spacing-md);">💡 Recommendations</h2>
                <ul style="line-height: 2; margin-left: var(--spacing-lg);">
                    <li>Log your health data daily for accurate medication effectiveness tracking</li>
                    <li>Record all medications taken in your health logs</li>
                    <li>Note any side effects or unusual symptoms</li>
                    <li>Share this analysis with your healthcare provider</li>
                    <li>Never adjust medications without consulting your doctor</li>
                </ul>
            </div>
            
            <?php endif; ?>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
