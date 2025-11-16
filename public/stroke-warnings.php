<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

$success = '';
$error = '';

// Handle new stroke warning submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'log_warning') {
    require_once __DIR__ . '/../includes/logger.php';
    $conn = getDBConnection();
    
    $warning_date = $_POST['warning_date'] ?? date('Y-m-d H:i:s');
    $face_drooping = isset($_POST['face_drooping']) ? 1 : 0;
    $arm_weakness = isset($_POST['arm_weakness']) ? 1 : 0;
    $speech_difficulty = isset($_POST['speech_difficulty']) ? 1 : 0;
    $sudden_confusion = isset($_POST['sudden_confusion']) ? 1 : 0;
    $vision_problems = isset($_POST['vision_problems']) ? 1 : 0;
    $severe_headache = isset($_POST['severe_headache']) ? 1 : 0;
    $dizziness = isset($_POST['dizziness']) ? 1 : 0;
    $loss_of_balance = isset($_POST['loss_of_balance']) ? 1 : 0;
    $severity = $_POST['severity'] ?? 'mild';
    $action_taken = !empty($_POST['action_taken']) ? trim($_POST['action_taken']) : null;
    $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : null;
    
    // Validate: At least one symptom must be checked
    $has_symptoms = $face_drooping || $arm_weakness || $speech_difficulty || 
                    $sudden_confusion || $vision_problems || $severe_headache || 
                    $dizziness || $loss_of_balance;
    
    if (!$has_symptoms) {
        $error = 'Please select at least one symptom before submitting.';
        logValidationError('stroke-warnings', ['error' => 'No symptoms selected'], $user_id);
    } else {
    
    // Calculate severity based on FAST symptoms
    $critical_symptoms = $face_drooping + $arm_weakness + $speech_difficulty;
    if ($critical_symptoms >= 2) {
        $severity = 'severe';
    } elseif ($critical_symptoms == 1) {
        $severity = 'moderate';
    }
    
    $stmt = $conn->prepare("
        INSERT INTO stroke_warnings 
        (user_id, warning_date, face_drooping, arm_weakness, speech_difficulty, 
         sudden_confusion, vision_problems, severe_headache, dizziness, loss_of_balance,
         severity, action_taken, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param("isiiiiiiiisss", 
        $user_id, $warning_date, $face_drooping, $arm_weakness, $speech_difficulty,
        $sudden_confusion, $vision_problems, $severe_headache, $dizziness, $loss_of_balance,
        $severity, $action_taken, $notes
    );
    
    if ($stmt->execute()) {
        $success = 'Stroke warning recorded successfully.';
        logFormSubmission('stroke-warnings', true, $user_id, ['severity' => $severity, 'symptoms_count' => $critical_symptoms]);
        
        // Create critical alert if severity is severe
        if ($severity === 'severe') {
            $alert_msg = "CRITICAL: Severe stroke warning signs detected. Seek immediate medical attention!";
            $alert_stmt = $conn->prepare("INSERT INTO alerts (user_id, alert_type, alert_message, severity) VALUES (?, 'warning_signs', ?, 'critical')");
            $alert_stmt->bind_param("is", $user_id, $alert_msg);
            $alert_stmt->execute();
            $alert_stmt->close();
            logCritical('stroke_warning', "Severe stroke warning detected for user", $user_id);
        }
    } else {
        $error = 'Error recording stroke warning. Please try again.';
        logFormSubmission('stroke-warnings', false, $user_id, ['database_error' => $conn->error]);
    }
    
    $stmt->close();
    $conn->close();
    }
}

// Get stroke warning history
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT * FROM stroke_warnings 
    WHERE user_id = ? 
    ORDER BY warning_date DESC 
    LIMIT 20
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$warnings = [];
while ($row = $result->fetch_assoc()) {
    $warnings[] = $row;
}
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stroke Warnings - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .fast-guide {
            background: linear-gradient(135deg, #ff6b6b 0%, #ffa502 100%);
            color: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
        }
        
        .fast-guide h2 {
            color: white;
            margin-bottom: var(--spacing-md);
        }
        
        .fast-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
            margin-top: var(--spacing-md);
        }
        
        .fast-item {
            background: rgba(255, 255, 255, 0.2);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
        }
        
        .fast-item h3 {
            color: white;
            font-size: 1.25rem;
            margin-bottom: var(--spacing-xs);
        }
        
        .warning-form {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .symptom-checkboxes {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: var(--spacing-md);
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-sm);
            background: var(--bg-primary);
            border-radius: var(--radius-md);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .checkbox-label:hover {
            background: var(--primary-light);
            color: white;
        }
        
        .checkbox-label input {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }
        
        .warning-history {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .warning-item {
            border-left: 4px solid var(--warning);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-md);
            background: var(--bg-primary);
            border-radius: var(--radius-sm);
        }
        
        .warning-item.severe {
            border-left-color: var(--danger);
        }
        
        .warning-item.moderate {
            border-left-color: var(--warning);
        }
        
        .warning-item.mild {
            border-left-color: var(--info);
        }
        
        .emergency-banner {
            background: var(--danger);
            color: white;
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            margin-bottom: var(--spacing-lg);
            text-align: center;
        }
        
        .emergency-banner h2 {
            color: white;
            font-size: 2rem;
            margin-bottom: var(--spacing-sm);
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
                <h1>⚠️ Stroke Warning Signs</h1>
                <p class="dashboard-subtitle">Monitor and track potential stroke symptoms</p>
            </div>
            
            <div class="emergency-banner">
                <h2>🚨 EMERGENCY: Call 911 Immediately</h2>
                <p>If you experience ANY stroke symptoms, especially FAST symptoms, call 911 right away. Time is critical!</p>
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
            
            <!-- FAST Guide -->
            <div class="fast-guide">
                <h2>Remember FAST: The Stroke Warning Signs</h2>
                <div class="fast-items">
                    <div class="fast-item">
                        <h3>F - Face</h3>
                        <p>Ask the person to smile. Does one side of the face droop?</p>
                    </div>
                    <div class="fast-item">
                        <h3>A - Arms</h3>
                        <p>Ask the person to raise both arms. Does one arm drift downward?</p>
                    </div>
                    <div class="fast-item">
                        <h3>S - Speech</h3>
                        <p>Ask the person to repeat a simple phrase. Is speech slurred or strange?</p>
                    </div>
                    <div class="fast-item">
                        <h3>T - Time</h3>
                        <p>If you see any of these signs, call 911 immediately!</p>
                    </div>
                </div>
            </div>
            
            <!-- Warning Logging Form -->
            <div class="warning-form">
                <h2>Log Stroke Warning Signs</h2>
                <p style="color: var(--text-secondary); margin-bottom: var(--spacing-lg);">
                    Record any symptoms you experience. This helps track patterns and identify risks.
                </p>
                
                <form method="POST" action="">
                    <input type="hidden" name="action" value="log_warning">
                    
                    <div class="form-group">
                        <label for="warning_date">Date & Time</label>
                        <input type="datetime-local" id="warning_date" name="warning_date" 
                               value="<?php echo date('Y-m-d\TH:i'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Symptoms (Check all that apply)</label>
                        <div class="symptom-checkboxes">
                            <label class="checkbox-label" style="border: 2px solid var(--danger);">
                                <input type="checkbox" name="face_drooping" id="face_drooping">
                                <span><strong>Face Drooping</strong> (FAST)</span>
                            </label>
                            <label class="checkbox-label" style="border: 2px solid var(--danger);">
                                <input type="checkbox" name="arm_weakness" id="arm_weakness">
                                <span><strong>Arm Weakness</strong> (FAST)</span>
                            </label>
                            <label class="checkbox-label" style="border: 2px solid var(--danger);">
                                <input type="checkbox" name="speech_difficulty" id="speech_difficulty">
                                <span><strong>Speech Difficulty</strong> (FAST)</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="sudden_confusion" id="sudden_confusion">
                                <span>Sudden Confusion</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="vision_problems" id="vision_problems">
                                <span>Vision Problems</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="severe_headache" id="severe_headache">
                                <span>Severe Headache</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="dizziness" id="dizziness">
                                <span>Dizziness</span>
                            </label>
                            <label class="checkbox-label">
                                <input type="checkbox" name="loss_of_balance" id="loss_of_balance">
                                <span>Loss of Balance</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="action_taken">Action Taken</label>
                        <textarea id="action_taken" name="action_taken" rows="2" 
                                  placeholder="What did you do? (called 911, took medication, rested, etc.)"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Additional Notes</label>
                        <textarea id="notes" name="notes" rows="3" 
                                  placeholder="Any other relevant information"></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-danger">Record Warning</button>
                </form>
            </div>
            
            <!-- Warning History -->
            <div class="warning-history">
                <h2>Warning History</h2>
                <?php if (count($warnings) > 0): ?>
                    <?php foreach ($warnings as $warning): ?>
                        <div class="warning-item <?php echo $warning['severity']; ?>">
                            <div style="display: flex; justify-content: space-between; margin-bottom: var(--spacing-sm);">
                                <strong><?php echo date('M j, Y g:i A', strtotime($warning['warning_date'])); ?></strong>
                                <span class="badge" style="background: <?php 
                                    echo $warning['severity'] === 'severe' ? 'var(--danger)' : 
                                        ($warning['severity'] === 'moderate' ? 'var(--warning)' : 'var(--info)'); 
                                ?>">
                                    <?php echo strtoupper($warning['severity']); ?>
                                </span>
                            </div>
                            <div style="color: var(--text-secondary); margin-bottom: var(--spacing-xs);">
                                <strong>Symptoms:</strong>
                                <?php
                                $symptoms = [];
                                if ($warning['face_drooping']) $symptoms[] = 'Face Drooping';
                                if ($warning['arm_weakness']) $symptoms[] = 'Arm Weakness';
                                if ($warning['speech_difficulty']) $symptoms[] = 'Speech Difficulty';
                                if ($warning['sudden_confusion']) $symptoms[] = 'Sudden Confusion';
                                if ($warning['vision_problems']) $symptoms[] = 'Vision Problems';
                                if ($warning['severe_headache']) $symptoms[] = 'Severe Headache';
                                if ($warning['dizziness']) $symptoms[] = 'Dizziness';
                                if ($warning['loss_of_balance']) $symptoms[] = 'Loss of Balance';
                                echo implode(', ', $symptoms);
                                ?>
                            </div>
                            <?php if ($warning['action_taken']): ?>
                                <div style="color: var(--text-secondary);">
                                    <strong>Action:</strong> <?php echo htmlspecialchars($warning['action_taken']); ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($warning['notes']): ?>
                                <div style="color: var(--text-secondary);">
                                    <strong>Notes:</strong> <?php echo htmlspecialchars($warning['notes']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color: var(--text-secondary); text-align: center; padding: var(--spacing-lg);">
                        No stroke warnings recorded. Great! Keep monitoring your health.
                    </p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>
