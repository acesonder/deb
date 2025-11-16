<?php
/**
 * Quick Log Templates
 * Pre-defined templates for common health logging scenarios
 */

require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$user_id = getCurrentUserId();
$role = getCurrentUserRole();
$full_name = $_SESSION['full_name'];

// Only patients can log health data
if ($role !== 'patient' && $role !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Handle quick log submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['template_type'])) {
    require_once __DIR__ . '/../includes/logger.php';
    
    $template_type = $_POST['template_type'];
    $conn = getDBConnection();
    
    // Prepare data based on template type
    $systolic_bp = !empty($_POST['systolic_bp']) ? $_POST['systolic_bp'] : null;
    $diastolic_bp = !empty($_POST['diastolic_bp']) ? $_POST['diastolic_bp'] : null;
    $heart_rate = !empty($_POST['heart_rate']) ? $_POST['heart_rate'] : null;
    $weight = !empty($_POST['weight']) ? $_POST['weight'] : null;
    $temperature = !empty($_POST['temperature']) ? $_POST['temperature'] : null;
    $sleep_hours = !empty($_POST['sleep_hours']) ? $_POST['sleep_hours'] : null;
    $activity_level = !empty($_POST['activity_level']) ? $_POST['activity_level'] : null;
    $stress_level = !empty($_POST['stress_level']) ? $_POST['stress_level'] : null;
    $mood = !empty($_POST['mood']) ? $_POST['mood'] : null;
    $symptoms = !empty($_POST['symptoms']) ? trim($_POST['symptoms']) : null;
    $medications = !empty($_POST['medications']) ? trim($_POST['medications']) : null;
    $notes = !empty($_POST['notes']) ? trim($_POST['notes']) : "Quick log using template: $template_type";
    
    // Validate: At least one field must be filled (excluding template_type)
    $has_data = $systolic_bp || $diastolic_bp || $heart_rate || $weight || $temperature || 
                $sleep_hours || $activity_level || $stress_level || $mood || $symptoms || $medications;
    
    if (!$has_data) {
        $error_message = 'Please fill in at least one field before submitting.';
        logValidationError('quick-log', ['error' => 'All fields blank', 'template' => $template_type], $user_id);
    } else {
    
    $stmt = $conn->prepare("
        INSERT INTO health_logs 
        (user_id, log_date, systolic_bp, diastolic_bp, heart_rate, weight, temperature, 
         sleep_hours, activity_level, stress_level, mood, symptoms, medications, notes)
        VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $stmt->bind_param(
        "iiidddssisss",
        $user_id,
        $systolic_bp,
        $diastolic_bp,
        $heart_rate,
        $weight,
        $temperature,
        $sleep_hours,
        $activity_level,
        $stress_level,
        $mood,
        $symptoms,
        $medications,
        $notes
    );
    
    if ($stmt->execute()) {
        // Check for critical BP and create alert if necessary
        if ($systolic_bp >= 180 || $diastolic_bp >= 120) {
            $alert_msg = "Critical blood pressure reading: $systolic_bp/$diastolic_bp mmHg";
            $alert_stmt = $conn->prepare("
                INSERT INTO alerts (user_id, alert_type, alert_message, severity)
                VALUES (?, 'critical_bp', ?, 'critical')
            ");
            $alert_stmt->bind_param("is", $user_id, $alert_msg);
            $alert_stmt->execute();
            $alert_stmt->close();
            logInfo('critical_alert', "Critical BP alert created via quick-log", $user_id);
        }
        
        $success_message = "Health data logged successfully!";
        logFormSubmission('quick-log', true, $user_id, ['template' => $template_type]);
    } else {
        $error_message = "Error logging data. Please try again.";
        logFormSubmission('quick-log', false, $user_id, ['database_error' => $conn->error, 'template' => $template_type]);
    }
    
    $stmt->close();
    $conn->close();
    }
}

// Get recent logs for reference
$conn = getDBConnection();
$stmt = $conn->prepare("
    SELECT systolic_bp, diastolic_bp, heart_rate, weight
    FROM health_logs
    WHERE user_id = ?
    ORDER BY log_date DESC
    LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$last_log = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Log - Deb's Health Tracker</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .templates-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-xl);
        }
        
        .template-card {
            background: var(--bg-card);
            padding: var(--spacing-lg);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .template-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }
        
        .template-icon {
            font-size: 3rem;
            margin-bottom: var(--spacing-md);
        }
        
        .template-title {
            color: var(--primary-color);
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: var(--spacing-sm);
        }
        
        .template-desc {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: var(--spacing-md);
        }
        
        .template-fields {
            display: flex;
            flex-wrap: wrap;
            gap: 4px;
        }
        
        .field-tag {
            background: var(--bg-primary);
            color: var(--text-secondary);
            padding: 4px 8px;
            border-radius: var(--radius-sm);
            font-size: 0.75rem;
        }
        
        .quick-form {
            display: none;
            background: var(--bg-card);
            padding: var(--spacing-xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            margin-bottom: var(--spacing-xl);
        }
        
        .quick-form.active {
            display: block;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: var(--spacing-md);
        }
        
        .recent-values {
            background: #e3f2fd;
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-lg);
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
                <h1>⚡ Quick Log Templates</h1>
                <p class="dashboard-subtitle">Fast and easy health data logging</p>
            </div>
            
            <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    ✅ <?php echo $success_message; ?>
                    <a href="dashboard.php" style="margin-left: 1rem;">Go to Dashboard</a>
                </div>
            <?php endif; ?>
            
            <?php if (isset($error_message)): ?>
                <div class="alert alert-error">
                    ❌ <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($last_log): ?>
            <div class="recent-values">
                <strong>📊 Your Last Reading:</strong>
                BP: <?php echo $last_log['systolic_bp']; ?>/<?php echo $last_log['diastolic_bp']; ?> mmHg
                <?php if ($last_log['heart_rate']): ?>
                    | HR: <?php echo $last_log['heart_rate']; ?> bpm
                <?php endif; ?>
                <?php if ($last_log['weight']): ?>
                    | Weight: <?php echo $last_log['weight']; ?> lbs
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <div class="templates-grid">
                <!-- Morning Routine Template -->
                <div class="template-card" onclick="showQuickForm('morning')">
                    <div class="template-icon">🌅</div>
                    <div class="template-title">Morning Routine</div>
                    <div class="template-desc">Log your morning vitals and start the day right</div>
                    <div class="template-fields">
                        <span class="field-tag">BP</span>
                        <span class="field-tag">HR</span>
                        <span class="field-tag">Weight</span>
                        <span class="field-tag">Sleep</span>
                        <span class="field-tag">Mood</span>
                    </div>
                </div>
                
                <!-- Evening Check Template -->
                <div class="template-card" onclick="showQuickForm('evening')">
                    <div class="template-icon">🌙</div>
                    <div class="template-title">Evening Check</div>
                    <div class="template-desc">End-of-day health check and reflection</div>
                    <div class="template-fields">
                        <span class="field-tag">BP</span>
                        <span class="field-tag">HR</span>
                        <span class="field-tag">Stress</span>
                        <span class="field-tag">Activity</span>
                        <span class="field-tag">Mood</span>
                    </div>
                </div>
                
                <!-- Quick BP Only Template -->
                <div class="template-card" onclick="showQuickForm('bp-only')">
                    <div class="template-icon">🩺</div>
                    <div class="template-title">BP Only</div>
                    <div class="template-desc">Quick blood pressure reading</div>
                    <div class="template-fields">
                        <span class="field-tag">BP</span>
                        <span class="field-tag">HR</span>
                    </div>
                </div>
                
                <!-- Medication Check Template -->
                <div class="template-card" onclick="showQuickForm('medication')">
                    <div class="template-icon">💊</div>
                    <div class="template-title">Medication Log</div>
                    <div class="template-desc">Log medications taken and vitals</div>
                    <div class="template-fields">
                        <span class="field-tag">BP</span>
                        <span class="field-tag">Medications</span>
                        <span class="field-tag">Symptoms</span>
                    </div>
                </div>
                
                <!-- Symptom Tracking Template -->
                <div class="template-card" onclick="showQuickForm('symptoms')">
                    <div class="template-icon">🤒</div>
                    <div class="template-title">Symptom Check</div>
                    <div class="template-desc">Log symptoms and related vitals</div>
                    <div class="template-fields">
                        <span class="field-tag">BP</span>
                        <span class="field-tag">Temperature</span>
                        <span class="field-tag">Symptoms</span>
                        <span class="field-tag">Notes</span>
                    </div>
                </div>
                
                <!-- Post-Exercise Template -->
                <div class="template-card" onclick="showQuickForm('exercise')">
                    <div class="template-icon">🏃</div>
                    <div class="template-title">Post-Exercise</div>
                    <div class="template-desc">Track vitals after physical activity</div>
                    <div class="template-fields">
                        <span class="field-tag">BP</span>
                        <span class="field-tag">HR</span>
                        <span class="field-tag">Activity</span>
                        <span class="field-tag">Notes</span>
                    </div>
                </div>
            </div>
            
            <!-- Quick Form (Hidden by default) -->
            <div id="quickForm" class="quick-form">
                <h2 id="formTitle">Quick Log</h2>
                <button onclick="hideQuickForm()" style="float: right; margin-top: -40px;" class="btn-secondary">✕ Close</button>
                
                <form method="POST" action="quick-log.php">
                    <input type="hidden" name="template_type" id="templateType">
                    
                    <div class="form-grid" id="formFields">
                        <!-- Fields will be dynamically added based on template -->
                    </div>
                    
                    <div class="form-actions" style="margin-top: var(--spacing-lg);">
                        <button type="submit" class="btn-primary">📝 Log Health Data</button>
                        <button type="button" onclick="hideQuickForm()" class="btn-secondary">Cancel</button>
                    </div>
                </form>
            </div>
            
            <div style="text-align: center; margin-top: var(--spacing-xl); color: var(--text-secondary);">
                <p>Need more options? <a href="log-health.php">Use Full Logging Form</a></p>
            </div>
        </main>
    </div>
    
    <script src="js/main.js"></script>
    <script>
        const templates = {
            'morning': {
                title: '🌅 Morning Routine',
                fields: ['systolic_bp', 'diastolic_bp', 'heart_rate', 'weight', 'sleep_hours', 'mood']
            },
            'evening': {
                title: '🌙 Evening Check',
                fields: ['systolic_bp', 'diastolic_bp', 'heart_rate', 'stress_level', 'activity_level', 'mood']
            },
            'bp-only': {
                title: '🩺 BP Only',
                fields: ['systolic_bp', 'diastolic_bp', 'heart_rate']
            },
            'medication': {
                title: '💊 Medication Log',
                fields: ['systolic_bp', 'diastolic_bp', 'medications', 'symptoms']
            },
            'symptoms': {
                title: '🤒 Symptom Check',
                fields: ['systolic_bp', 'diastolic_bp', 'temperature', 'symptoms', 'notes']
            },
            'exercise': {
                title: '🏃 Post-Exercise',
                fields: ['systolic_bp', 'diastolic_bp', 'heart_rate', 'activity_level', 'notes']
            }
        };
        
        const fieldConfig = {
            'systolic_bp': { label: 'Systolic BP', type: 'number', placeholder: '120', required: false },
            'diastolic_bp': { label: 'Diastolic BP', type: 'number', placeholder: '80', required: false },
            'heart_rate': { label: 'Heart Rate (bpm)', type: 'number', placeholder: '70' },
            'weight': { label: 'Weight (lbs)', type: 'number', step: '0.1', placeholder: '150' },
            'temperature': { label: 'Temperature (°F)', type: 'number', step: '0.1', placeholder: '98.6' },
            'sleep_hours': { label: 'Sleep (hours)', type: 'number', step: '0.5', placeholder: '7' },
            'stress_level': { label: 'Stress Level (1-10)', type: 'number', min: '1', max: '10', placeholder: '5' },
            'activity_level': { 
                label: 'Activity Level', 
                type: 'select', 
                options: ['sedentary', 'light', 'moderate', 'active', 'very_active']
            },
            'mood': { 
                label: 'Mood', 
                type: 'select', 
                options: ['excellent', 'good', 'fair', 'poor', 'critical']
            },
            'symptoms': { label: 'Symptoms', type: 'textarea', placeholder: 'Describe any symptoms...' },
            'medications': { label: 'Medications Taken', type: 'textarea', placeholder: 'List medications...' },
            'notes': { label: 'Notes', type: 'textarea', placeholder: 'Additional notes...' }
        };
        
        function showQuickForm(templateId) {
            const template = templates[templateId];
            const form = document.getElementById('quickForm');
            const formTitle = document.getElementById('formTitle');
            const formFields = document.getElementById('formFields');
            const templateType = document.getElementById('templateType');
            
            formTitle.textContent = template.title;
            templateType.value = templateId;
            formFields.innerHTML = '';
            
            // Build form fields based on template
            template.fields.forEach(fieldName => {
                const config = fieldConfig[fieldName];
                const fieldDiv = document.createElement('div');
                fieldDiv.className = 'form-group';
                
                const label = document.createElement('label');
                label.textContent = config.label;
                label.setAttribute('for', fieldName);
                fieldDiv.appendChild(label);
                
                if (config.type === 'select') {
                    const select = document.createElement('select');
                    select.name = fieldName;
                    select.id = fieldName;
                    select.className = 'form-input';
                    
                    const defaultOption = document.createElement('option');
                    defaultOption.value = '';
                    defaultOption.textContent = 'Select...';
                    select.appendChild(defaultOption);
                    
                    config.options.forEach(opt => {
                        const option = document.createElement('option');
                        option.value = opt;
                        option.textContent = opt.charAt(0).toUpperCase() + opt.slice(1).replace('_', ' ');
                        select.appendChild(option);
                    });
                    
                    fieldDiv.appendChild(select);
                } else if (config.type === 'textarea') {
                    const textarea = document.createElement('textarea');
                    textarea.name = fieldName;
                    textarea.id = fieldName;
                    textarea.className = 'form-input';
                    textarea.placeholder = config.placeholder || '';
                    textarea.rows = 3;
                    fieldDiv.appendChild(textarea);
                } else {
                    const input = document.createElement('input');
                    input.type = config.type;
                    input.name = fieldName;
                    input.id = fieldName;
                    input.className = 'form-input';
                    input.placeholder = config.placeholder || '';
                    if (config.required) input.required = true;
                    if (config.step) input.step = config.step;
                    if (config.min) input.min = config.min;
                    if (config.max) input.max = config.max;
                    fieldDiv.appendChild(input);
                }
                
                formFields.appendChild(fieldDiv);
            });
            
            form.classList.add('active');
            form.scrollIntoView({ behavior: 'smooth' });
        }
        
        function hideQuickForm() {
            document.getElementById('quickForm').classList.remove('active');
        }
    </script>
</body>
</html>
