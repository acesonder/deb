<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once __DIR__ . '/../includes/logger.php';
    
    $conn = getDBConnection();
    
    // Get form data
    $log_date = $_POST['log_date'] ?? date('Y-m-d H:i:s');
    $systolic_bp = !empty($_POST['systolic_bp']) ? $_POST['systolic_bp'] : null;
    $diastolic_bp = !empty($_POST['diastolic_bp']) ? $_POST['diastolic_bp'] : null;
    $heart_rate = !empty($_POST['heart_rate']) ? $_POST['heart_rate'] : null;
    $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
    $temperature = !empty($_POST['temperature']) ? $_POST['temperature'] : null;
    $symptoms = !empty($_POST['symptoms']) ? trim($_POST['symptoms']) : null;
    $medications = !empty($_POST['medications']) ? trim($_POST['medications']) : null;
    $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
    $mood = $_POST['mood'] ?? null;
    $activity_level = $_POST['activity_level'] ?? null;
    $sleep_hours = !empty($_POST['sleep_hours']) ? $_POST['sleep_hours'] : null;
    $stress_level = !empty($_POST['stress_level']) ? $_POST['stress_level'] : null;
    
    // Validate: At least one field must be filled
    $has_data = $systolic_bp || $diastolic_bp || $heart_rate || $weight || $temperature || 
                $symptoms || $medications || $notes || $mood || $activity_level || 
                $sleep_hours || $stress_level;
    
    if (!$has_data) {
        $error = 'Please fill in at least one field before submitting.';
        logValidationError('log-health', ['error' => 'All fields blank'], $user_id);
    } else {
    
    // Insert health log
    $stmt = $conn->prepare("
        INSERT INTO health_logs 
        (user_id, log_date, systolic_bp, diastolic_bp, heart_rate, weight, temperature, 
         symptoms, medications, notes, mood, activity_level, sleep_hours, stress_level)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("isiiiddsssssdi", 
        $user_id, $log_date, $systolic_bp, $diastolic_bp, $heart_rate, $weight, $temperature,
        $symptoms, $medications, $notes, $mood, $activity_level, $sleep_hours, $stress_level
    );
    
    if ($stmt->execute()) {
        $success = 'Health data logged successfully!';
        logFormSubmission('log-health', true, $user_id);
        
        // Check if blood pressure is critical and create alert
        if ($systolic_bp && $diastolic_bp && ($systolic_bp >= 180 || $diastolic_bp >= 120)) {
            $alert_msg = "Critical blood pressure reading: {$systolic_bp}/{$diastolic_bp} mmHg. Please seek immediate medical attention.";
            $alert_stmt = $conn->prepare("INSERT INTO alerts (user_id, alert_type, alert_message, severity) VALUES (?, 'critical_bp', ?, 'critical')");
            $alert_stmt->bind_param("is", $user_id, $alert_msg);
            $alert_stmt->execute();
            $alert_stmt->close();
            logInfo('critical_alert', "Critical BP alert created for user", $user_id);
        }
    } else {
        $error = 'Error logging health data. Please try again.';
        logFormSubmission('log-health', false, $user_id, ['database_error' => $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Health Data - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: var(--spacing-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .form-section {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .form-section h3 {
            margin-bottom: var(--spacing-md);
            color: var(--primary-color);
        }
        
        .bp-inputs {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            gap: var(--spacing-sm);
            align-items: end;
        }
        
        .bp-separator {
            font-size: 1.5rem;
            font-weight: 700;
            padding-bottom: 0.75rem;
            color: var(--text-secondary);
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
                <h1>Log Health Data</h1>
                <p class="dashboard-subtitle">Record your daily health metrics</p>
            </div>
            
            <?php if ($success): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="health-log-form">
                <!-- Date and Time -->
                <div class="form-section">
                    <h3>📅 Date & Time</h3>
                    <div class="form-group">
                        <label for="log_date">Date & Time</label>
                        <input type="datetime-local" id="log_date" name="log_date" 
                               value="<?php echo date('Y-m-d\TH:i'); ?>">
                        <small style="color: var(--text-secondary);">All fields are optional - fill out what you can</small>
                    </div>
                </div>
                
                <!-- Vital Signs -->
                <div class="form-section">
                    <h3>💗 Vital Signs</h3>
                    
                    <div class="form-group">
                        <label>Blood Pressure (mmHg)</label>
                        <div class="bp-inputs">
                            <div>
                                <input type="number" name="systolic_bp" placeholder="Systolic (top)" 
                                       min="70" max="250" aria-label="Systolic blood pressure">
                            </div>
                            <div class="bp-separator">/</div>
                            <div>
                                <input type="number" name="diastolic_bp" placeholder="Diastolic (bottom)" 
                                       min="40" max="150" aria-label="Diastolic blood pressure">
                            </div>
                        </div>
                        <small style="color: var(--text-secondary);">Normal: &lt;120/80 mmHg</small>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="heart_rate">Heart Rate (bpm)</label>
                            <input type="number" id="heart_rate" name="heart_rate" 
                                   min="40" max="200" placeholder="e.g., 72">
                            <small style="color: var(--text-secondary);">Normal: 60-100 bpm</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="temperature">Temperature (°F)</label>
                            <input type="number" id="temperature" name="temperature" 
                                   step="0.1" min="95" max="110" placeholder="e.g., 98.6">
                        </div>
                    </div>
                </div>
                
                <!-- Physical Metrics -->
                <div class="form-section">
                    <h3>⚖️ Physical Metrics</h3>
                    <div class="form-group">
                        <label for="weight">Weight (lbs)</label>
                        <input type="number" id="weight" name="weight" step="0.1" 
                               min="50" max="500" placeholder="e.g., 150.5">
                    </div>
                </div>
                
                <!-- Daily Activities -->
                <div class="form-section">
                    <h3>🏃 Daily Activities</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="sleep_hours">Hours of Sleep</label>
                            <input type="number" id="sleep_hours" name="sleep_hours" 
                                   step="0.5" min="0" max="24" placeholder="e.g., 7.5">
                        </div>
                        
                        <div class="form-group">
                            <label for="activity_level">Activity Level</label>
                            <select id="activity_level" name="activity_level">
                                <option value="">Select level...</option>
                                <option value="sedentary">Sedentary</option>
                                <option value="light">Light</option>
                                <option value="moderate">Moderate</option>
                                <option value="active">Active</option>
                                <option value="very_active">Very Active</option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Mental Health -->
                <div class="form-section">
                    <h3>😊 Mental Well-being</h3>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="mood">Mood</label>
                            <select id="mood" name="mood">
                                <option value="">Select mood...</option>
                                <option value="excellent">😄 Excellent</option>
                                <option value="good">🙂 Good</option>
                                <option value="fair">😐 Fair</option>
                                <option value="poor">😟 Poor</option>
                                <option value="critical">😰 Critical</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="stress_level">Stress Level (1-10)</label>
                            <input type="number" id="stress_level" name="stress_level" 
                                   min="1" max="10" placeholder="1 = Low, 10 = High">
                        </div>
                    </div>
                </div>
                
                <!-- Symptoms & Notes -->
                <div class="form-section">
                    <h3>📝 Symptoms & Notes</h3>
                    
                    <div class="form-group">
                        <label for="symptoms">Symptoms</label>
                        <textarea id="symptoms" name="symptoms" rows="3" 
                                  placeholder="Describe any symptoms you're experiencing (headache, dizziness, chest pain, etc.)"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="medications">Medications Taken Today</label>
                        <textarea id="medications" name="medications" rows="2" 
                                  placeholder="List medications and dosages"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Additional Notes</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  placeholder="Any other relevant information"></textarea>
                    </div>
                </div>
                
                <div class="form-actions" style="display: flex; gap: var(--spacing-md);">
                    <button type="submit" class="btn btn-primary">Save Health Log</button>
                    <a href="dashboard.php" class="btn" style="background: var(--text-light); color: white;">Cancel</a>
                </div>
            </form>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
